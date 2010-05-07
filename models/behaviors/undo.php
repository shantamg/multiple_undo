<?php  
/**
 * MULTIPLE UNDO Behavior class file
 *
 * Intercepts database changes and retrieves the data that
 * will change (before it is saved or deleted) and saves it
 * using the Undo, UndoTable, and UndoField models.
 *
 * @author Jason Galuten <jason@galuten.com>
 * @copyright Copyright (c) 2010, Jason Galuten
 * @version 0.1
 */
class UndoBehavior extends ModelBehavior { 

    /**
     * Setup
     *
     * Makes the Undo model available in the behavior at $this->Undo
     * If the model being passed to the behavior is one of our Undo
     * models, we set $model->bypass to TRUE.
     */
    function setup(&$model) { 
        $this->Undo =& ClassRegistry::init('MultipleUndo.Undo');     
        $model->bypass = (in_array($model->name, array('Undo','UndoTable','UndoField'))); 
    } 
    
    /**
     * Before Save
     *
     * If we are between start() and stop() calls ($this->Undo->undoing == true)
     * and this model is not one of our undo models, we get the data that is
     * about to be deleted or updated.
     */      
    function beforeSave(&$model) { 
        if ($this->Undo->undoing && !$model->bypass) { 
            $fields = $model->data[$model->name]; 
            if (array_key_exists('id', $fields)) { 
                $this->Undo->getUndoData($model->name, $fields['id']); 
            } 
        } 
        return true; 
    }     
      
    /**
     * After Save
     *
     * Now that the model save has been performed, we pass the data
     * that was captured in BeforeSave to Undo model to save the undo.
     */   
    function afterSave(&$model, $created) { 
        if ($this->Undo->undoing && !$model->bypass) { 
            if ($created) $this->Undo->undoData = $model->data; 
            $this->Undo->saveUndo(($created xor 1) + 1); // 1 for create or 2 for update 
        } 
    }         
         
    /**
     * Before Delete
     *
     * Before a database record is deleted, we capture that
     * data and pass it to the Undo model to save an undo.
     */
    function beforeDelete(&$model) { 
        if ($this->Undo->undoing && !$model->bypass) { 
            $this->Undo->getUndoData($model->name,$model->id); 
            $this->Undo->saveUndo(0); // 0 for delete 
        } 
        return true; 
    }     
} 
?>