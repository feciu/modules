<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Migratable model interface.
 *
 * @package		Migration
 * @author		Oliver Morgan
 * @uses		DBForge
 * @copyright	(c) 2009 Oliver Morgan
 * @license		MIT
 */
interface Model_Migratable {
	
	/**
	 * Gets the database object.
	 * 
	 * @return Database
	 */
	public function db();
	
	/**
	 * Generates and returns the list of tables to be migrated.
	 * 
	 * @return array
	 */
	public function migration_tables();
	
} // End Model_Migratable