<?php  
/**
 * MULTIPLE UNDO - Undo Model class file
 *
 * Saves, manages, and performs undos and redos.
 *
 * The ids of the this model (and the foreign keys to this model)
 * are dynamic. 0 represents the undo for the latest transaction,
 * while positive ids represent increasingly older undos. The nudge()
 * method is used to change all of the ids  by 1 when a new undo is created,
 * or when an undo or redo is performed.
 *
 * When an undo is performed, the ids are all decremented, thus the undo
 * just performed becomes -1, and the next possible undo (if it exists)
 * becomes 0. Negative ids are possible redos.
 *
 * When a redo is performed, the ids are all incremented, thus the redo
 * just performed becomes 0 (which makes it the next undo), and the next
 * redo (if it exists) becomes -1.
 * 
 * The getChanges() method always retrieves undo data at id -1
 * (there are database issues with interacting with id 0)
 * so when performing an undo the ids are nudged down before
 * geting the data, and when performing a redo the ids are nudged up
 * after getting the data. In this way, the id being used is always -1.
 *
 * @author Jason Galuten <jason@galuten.com>
 * @copyright Copyright (c) 2010, Jason Galuten
 * @version 0.1
 */
class Undo extends MultipleUndoAppModel { 

    var $name = 'Undo'; 
    var $actsAs = array('Containable'); 
    var $hasMany = array(
    	'UndoField' => array(
    		'className' => 'MultipleUndo.UndoField'
    	),
    	'UndoTable' => array(
    		'className' => 'MultipleUndo.UndoTable'
    	)
    );
    var $undoing = false; 
    var $id = 0; // the current undo is always 0 
    
    /**
     * Do Undo
     * 
     * If there is an id 0 then there is an undo. First nudge
     * the ids down (data in this model is always retrieved at id -1),
     * then get and apply the undo.
     * 
     * Return false if there is no undo to perform.
     */
    function doUndo() { 
        if ($this->findById(0)) { // is there an undo? (if so, the id would be 0) 
            $this->nudge(-1); // decrement undo tables ids so the next undo will be 0 
            $this->apply($this->getChanges(true)); // read the undo and apply it to the database 
            return true; 
        } else { 
            return false; 
        } 
    } 
 
    /**
     * Do Redo
     * 
     * If there is an id -1 then there is an redo. Get and apply
     * the redo and then nudge the ids up (so that the redo is
     * undoable at id 0, etc,)
     * 
     * Return false if there is no redo to perform.
     */    
    function doRedo() { 
        if ($this->findById(-1)) { // is there a redo? (a redo is always negative) 
            $this->apply($this->getChanges()); 
            $this->nudge(1);             
            return true; 
        } else { 
            return false; 
        } 
    } 

    /**
     * Get Changes
     *
     * Reads the current undo/redo data from the 3 Undo models
     * and returns the data to be saved/updated/deleted based on
     * the action specified in the UndoTable entry.
     *
     * UndoTable action: 0 - delete; 1 - create; 2 - update;
     * $reverse_action is used to specify if this is an undo
     * or a redo. When this method is called from doUndo(), the
     * $reverse_action parameter is set to true.
     */
    function getChanges($reverse_action = false) { 
        // for undo, the nudge happens before this function, for redo it's after 
        // so that either way the table id we use is -1 
        $this->id = -1;  
        $this->contain('UndoTable.UndoField'); 
        $data = $this->find('first'); 
        foreach ($data['UndoTable'] as $table_num => $table) { 
            $table_data = Set::combine($table,  
                'UndoField.{n}.field_key',  
                'UndoField.{n}.field_val' 
            ); 
            if ($table['action'] == 2) { // if the action was an update (not create or delete) 
                // save the undo/redo for when we come from the other direction 
                $this->getUndoData($table['name'], $table['record_id']); 
                $this->saveUndo(2, $table['id']); 
            } else { 
                // if ($reverse_action), i.e. called from doRedo(), toggle create (1) and delete (0) 
                $table['action'] ^= $reverse_action; 
            } 
            $tables[$table_num]['model']  = $table['name']; 
            $tables[$table_num]['action'] = $table['action']; 
            $tables[$table_num]['id']     = $table['record_id']; 
            $tables[$table_num]['data']   = $table_data; 
             
        } 
        return $tables; 
    } 

    /**
     * Apply Changes
     *
     * Called from doUndo() and doRedo(), taking the data returned
     * from getChanges(), it performs each database change
     * listed in the array.
     */
    function apply($changes) { 
        foreach ($changes as $table) { 
            $model = ClassRegistry::init($table['model']); 
            // if it's 0, delete, otherwise save (there is no difference 
            // between update and create here because the id of the entry is in the data) 
            if ($table['action'] == 0) { 
                $model->delete($table['id']); 
            } else { 
                $model->create(); 
                $model->save($table['data']); 
            } 
        } 
    } 
     
    /**
     * Get Undo Data
     *
     * Called from the Undoer Behavior in beforeSave and beforeDelete
     * Takes the model name and id of the record being saved, updated,
     * or deleted, and gets the data from the database, before it changes,
     * and stores it in $this->undoData for use in saveUndo().
     */
    function getUndoData($model_name, $id) { 
        $this->undoData =  
            ClassRegistry::init($model_name)->find('first', 
                array ( 
                    'conditions' => array ("{$model_name}.id" => $id), 
                    'recursive' => -1 
                ) 
            ); 
    }             
     
    /**
     * Save Undo
     *
     * Takes $action ($action = 0 (delete) 1 (create) or 2 (update),
     * and performs that action with the model and data given in $this->undoData
     *
     * When called from getChanges(), meaning that this is being done
     * in the middle of performing an undo or redo and the action is 2 (update),
     * it takes $undo_table_id so that the undo data can be cleared and re-entered
     * (making an redo undoable, and an undo redoable)
     */
    function saveUndo($action, $undo_table_id = null) { 
        foreach ($this->undoData as $model_name => $fields) { 
            $id = isset($fields['id']) ? $fields['id'] : ClassRegistry::init($model_name)->getLastInsertId(); 
            $fields['id'] = $id; 
            $undo_table_data = array( 
                'undo_id'   => $this->id, 
                'name'      => $model_name, 
                'action'    => $action, 
                'record_id' => $id 
            ); 
            if ($undo_table_id) { 
                $undo_table_data['id'] = $undo_table_id; 
                $this->UndoField->deleteAll(array( 
                    'UndoField.undo_table_id' => $undo_table_id, 
                    'UndoField.undo_id'       => -1                     
                ));     
                $clear_hanging = false; 
            } else { 
                $clear_hanging = true; 
            } 
            $this->UndoTable->create(); 
            $this->UndoTable->save($undo_table_data); 
            if (!$undo_table_id) $undo_table_id = $this->UndoTable->getLastInsertId(); 
            foreach ($fields as $field_key => $field_val) { 
                $this->UndoField->create(); 
                $this->UndoField->save(array ( 
                    'undo_id'       => $this->id, 
                    'undo_table_id' => $undo_table_id, 
                    'field_key'     => $field_key, 
                    'field_val'     => $field_val 
                )); 
            } 
            break; // Only want to deal with the first model in the array 
        }
        if ($clear_hanging) $this->clearHanging();     
    }     
     
    /**
     * Clear Hanging
     *
     * Any time something happens that makes redoing impossible 
     * (i.e. when some transactions have been undone and a new 
     * transaction is made) this function is called.
     */
    function clearHanging() { 
        $this->deleteAll(array('Undo.id <' => 0)); 
        $this->UndoTable->deleteAll(array('UndoTable.undo_id <' => 0)); 
        $this->UndoField->deleteAll(array('UndoField.undo_id <' => 0)); 
    }     

    /**
     * Nudge
     *
     * increments or decrements all of the ids in the Undo model
     * and the foreign keys in the UndoTable and UndoField models
     *
     * $i should be 1 or -1 
     */
    function nudge($i) { 
        $this->updateAll(array( 
            'Undo.id' => "Undo.id + {$i}" 
        ));      
        $this->UndoTable->updateAll(array( 
            'UndoTable.undo_id' => "UndoTable.undo_id + {$i}" 
        ));      
        $this->UndoField->updateAll(array( 
            'UndoField.undo_id' => "UndoField.undo_id + {$i}" 
        ));      
    } 
     
} 
?>