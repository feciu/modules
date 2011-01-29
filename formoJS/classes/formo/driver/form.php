<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Overrides the Formo driver for the Form object to add a CSS class to the <form> tag.
 * 
 * @extends Formo_Driver_Form_Core
 * @package FormoJS
 */
class Formo_Driver_Form extends Formo_Driver_Form_Core {

	// Setup the html object
	public function html()
	{
		$classes = $this->render_field->attr('class');
		array_unshift($classes, 'formo_form');
		
		$this->render_field->set('tag', 'form')
			->attr('method', $this->field->get('method', 'post'))
			->attr('class', $classes);
		
		// If it's not already defined, define the field's action	
		(empty($this->render_field->attr['action']) AND $this->render_field->attr['action'] = '');
	}

}