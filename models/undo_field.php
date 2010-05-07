<?php  
/**
 * MULTIPLE UNDO - UndoField Model class file
 *
 * @author Jason Galuten <jason@galuten.com>
 * @copyright Copyright (c) 2010, Jason Galuten
 * @version 0.1
 */
class UndoField extends MultipleUndoAppModel { 

    var $name = 'UndoField'; 
    var $belongsTo = array(
    	'Undo' => array(
    		'className' => 'MultipleUndo.Undo'
    	),
    	'UndoTable' => array(
    		'className' => 'MultipleUndo.UndoTable'
    	)
    ); 

} 
?>