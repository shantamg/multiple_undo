<?php  
/**
 * MULTIPLE UNDO - Undos Controller class file
 *
 * Contains undo and redo methods.
 *
 * @author Jason Galuten <jason@galuten.com>
 * @copyright Copyright (c) 2010, Jason Galuten
 * @version 0.1
 */
 class UndosController extends MultipleUndoAppController { 

    var $name = 'Undos'; 
    
    /**
     * Undo
     *
     * Attempts to perform an undo, and redirects to the
     * referring page. If there is nothing to undo, a message
     * is flashed.
     */
    function undo() { 
        if(!$this->Undo->doUndo()) { 
            $this->Session->setFlash("Nothing to undo!"); 
        } 
        $this->redirect($this->referer()); 
    } 
     
    /**
     * Redo
     *
     * Attempts to perform a redo, and redirects to the
     * referring page. If there is nothing to redo, a message
     * is flashed.
     */
    function redo() { 
        if(!$this->Undo->doRedo()) { 
            $this->Session->setFlash("Nothing to redo!"); 
        } 
        $this->redirect($this->referer()); 
    } 
     
} 
?>