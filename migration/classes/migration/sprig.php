<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Sprig migration driver.
 *
 * @package		Migration
 * @author		Oliver Morgan
 * @uses		DBForge
 * @copyright	(c) 2009 Oliver Morgan
 * @license		MIT
 */
class Migration_Sprig extends Migration {
	
	/**
	 * The sprig model object.
	 * 
	 * @var	Sprig
	 */
	protected $_model;
	
	protected function _model($model)
	{
		// If the model is given as a string
		if (is_string($model))
		{
			// Return the sprig object
			return Sprig::factory($model);
		}
		// If the model is an object instance of Sprig
		elseif (is_object($model) AND $model instanceof Sprig)
		{
			// Then return the model as is.
			return $model;
		}
		else
		{
			// Default route indicates failure.
			throw new Kohana_Exception('Invalid sprig model :model given to sprig migration driver.', array(
				':model'	=> (string) $model
			));
		}
	}
	
	protected function _tables()
	{
		// Prepare an array to hold tables
		$tables = array();
		
		// Create a new database table with name and database
		$table = new Database_Table($this->_model->table(), $this->_db);
		
		// Get the model's primary keys as an array
		$model_pks = is_array($this->_model->pk()) ? $this->_model->pk() : array($this->_model->pk());
		
		// Loop through each field within the model
		foreach ($this->_model->fields() as $field)
		{
			// Check if the field implaments the migratable field interface
			if ($field instanceof Sprig_Field_Migratable)
			{
				// Loop through each column in the field
				foreach ($field->columns() as $column)
				{
					// Add the column to the table
					$table->add_column($column);
				}
			}
			
			// If the field is in the database
			elseif ($field->in_db)
			{
				// If the field is unique
				if ($field->unique)
				{
					// Add a unique constraint to the table
					$table->add_constraint(
						new Database_Constraint_Unique($field->column)
					);
				}
				
				// Loop through every column in the model
				foreach ($this->_columns($field, $table) as $column)
				{
					// Add the column to the table
					$table->add_column($column);
				}
			}
			
			// We can also process ManyToMany Fields that aren't
			elseif ($field instanceof Sprig_Field_ManyToMany)
			{
				// ManyToMany fields also contain a pivot table
				$pivot = new Database_Table($field->through, $this->_db);
				
				// Get the columns associated with the first half
				$columns = $this->_columns(
					new Sprig_Field_BelongsTo(array(
						'model'	=> $field->model
					)), $pivot);
					
				// Foreach column in the first half
				foreach ($columns as $column)
				{
					// Add it to the pivot table
					$pivot->add_column($column);
				}
				
				// Get the columns associated with the second half
				$columns = $this->_columns(
					new Sprig_Field_BelongsTo(array(
						'model'	=> inflector::singular($this->_model->table())
					)), $pivot);
					
				// Foreach column in the second half
				foreach ($columns as $column)
				{
					// Add it to the pivot table
					$pivot->add_column($column);
				}
				
				// Add a primary key constraint on all fields within the pivot table
				$pivot->add_constraint(new Database_Constraint_Primary(
					array_keys($pivot->columns()), $pivot->name
				));
				
				// Add the pivot table to the list of tables
				$tables[] = $pivot;
			}
		}
		
		// Add the primary key constraints to the table
		$table->add_constraint(
			new Database_Constraint_Primary($model_pks, $table->name)
		);
		
		// Add the table to the list
		$tables[] = $table;
		
		// And return all tables.
		return $tables;
	}
	
	protected function _db()
	{
		// Sprig::db() returns the database name as a string.
		return Database::instance($this->_model->db());
	}
	
	/**
	 * Gets the database columns associated with the field.
	 * 
	 * @param	Sprig_Field	The sprig field.
	 * @param	Database_Table	The parent database table.
	 * @return	array
	 */
	private function _columns(Sprig_Field $field, Database_Table $table)
	{
		// Foreign keys are represented as BelongTo relationships
		if ($field instanceof Sprig_Field_BelongsTo)
		{
			// Get the model the field references
			$references = $this->_model($field->model);
			
			// Get all the primary keys in the referenced model as an array
			$pks = is_array($references->pk()) ? $references->pk() : array($references->pk());
			
			// Prepare a column array
			$columns = array();
			
			// Loop through each primary key
			foreach($pks as $pk)
			{
				// Get the foreign primary key field
				$foreign_field = $references->field($pk);
				
				// Generates a table column for that foreign field
				$column = current($this->_columns($foreign_field, $table));
				
				// Renames the column to the standard foreign key format
				$column->name = Inflector::singular($references->table()).'_'.$pk;
				
				// If the column is an integer
				if ($column instanceof Database_Column_Int)
				{
					// We must disable auto_increment
					$column->auto_increment = FALSE;
				}
				
				// Add the column to the column array
				$columns[] = $column;
			}
			
			// No further processing is needed, so return the columns.
			return $columns;
		}
		
		// Process character fields
		elseif ($field instanceof Sprig_Field_Char)
		{
			// If the character field is a text field
			if ($field instanceof Sprig_Field_Text)
			{
				// Set the column to have a text datatype
				$column = Database_Column::factory('text');
				
				// TODO: Ugly hack, fix it. (Text datatypes dont take parameters).
				unset($column->max_length);
			}
			else
			{
				// Create a new database column
				$column = Database_Column::factory('varchar');
				
				// Set the varchar's max length to a default of 45
				$column->max_length = isset($field->max_length) ? $field->max_length : 64;
			}
		}
		
		// Process integer fields
		elseif ($field instanceof Sprig_Field_Integer)
		{
			// Use the int datatype and create the column
			$column = Database_Column::factory('int');
			
			// If the field is Sprig_Field_Auto then auto_increment is set to true.
			$column->auto_increment = $field instanceof Sprig_Field_Auto;
		}
		
		// Process boolean fields
		elseif ($field instanceof Sprig_Field_Boolean)
		{
			// Very simply, a tinyint is a 0 or a 1
			$column = Database_Column::factory('tinyint');
		}

		// Set the value of the column's name
		$column->name = $field->column;
		
		// Set the column's default value
		$column->default = $field->default;
		
		// The column is nullable if the field is not empty
		$column->nullable = ! $field->empty;
		
		// Return the column as an array
		return array($column);
	}

} // End Migration_Sprig