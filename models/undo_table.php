<?php  
/**
 * MULTIPLE UNDO - UndoTable Model class file
 *
 * @author Jason Galuten <jason@galuten.com>
 * @copyright Copyright (c) 2010, Jason Galuten
 * @version 0.1
 */
class UndoTable extends MultipleUndoAppModel { 

    var $name = 'UndoTable'; 
    var $belongsTo = array(
    	'Undo' => array(
    		'className' => 'MultipleUndo.Undo'
    	)
    ); 
    var $hasMany = array(
    	'UndoField' => array(
    		'className' => 'MultipleUndo.UndoField'
    	)
    ); 

} 
?>