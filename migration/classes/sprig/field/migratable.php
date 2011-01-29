<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Migratable sprig field interface.
 *
 * @package		Migration
 * @author		Oliver Morgan
 * @copyright	(c) 2009 Oliver Morgan
 * @license		MIT
 */
interface Migration_Sprig_Field {
	
	/**
	 * Returns a list of columns represented by this field.
	 * 
	 * @return	array
	 */
	public function columns();
	
} // End Migration_Sprig_Field