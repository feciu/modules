<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Formo_Driver_Textarea_Core class.
 * 
 * @package  Formo
 */
class Formo_Driver_Ckeditor_Core extends Formo_Driver {

	protected $view = 'ckeditor';
	
	public function html()
	{
		$this->decorator
			->set('tag', 'textarea')
			->set('text', $this->field->val())
			->attr('name', $this->name());
	}

}