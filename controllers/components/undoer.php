<?php  
/**
 * MULTIPLE UNDO Component class file
 *
 * Gives any controller the ability to "start"
 * and "stop" the recording of an undo transaction.
 *
 * @author Jason Galuten <jason@galuten.com>
 * @copyright Copyright (c) 2010, Jason Galuten
 * @version 0.1
 */
class UndoerComponent extends Object { 

    /**
     * Initialize
     *
     * Makes the Undo model available in the component at $this->Undo
     */
    function initialize() { 
        $this->Undo =& ClassRegistry::init('MultipleUndo.Undo');
    } 

    /**
     * Wipe
     *
     * Most likely called from the Users controller on login
     * to reset the undo/redo history.
     */
    function wipe() { 
        $this->Undo->query('truncate table undos');         
        $this->Undo->query('truncate table undo_tables');         
        $this->Undo->query('truncate table undo_fields');         
        $this->Undo->undoable = true;     
    } 

    /**
     * Start
     *
     * Called from any controller in order to begin recording
     * database changes for undoing.
     */
    function start() { 
        $this->Undo->undoing = true; 

        // the order of these two lines is important 
        $this->Undo->clearHanging(); 
        $this->Undo->nudge(1); 
    } 

    /**
     * Stop
     *
     * Called from any controller in order to end the recording
     * of this undoable transaction and give the transaction a description.
     */
    function stop($description = null) { 
        $this->Undo->save(array( 
            'Undo' => array( 
                'id' => 0, 
                'description' => $description 
            ) 
        )); 
        $this->Undo->undoing = false; 
    } 
     
} 
?>