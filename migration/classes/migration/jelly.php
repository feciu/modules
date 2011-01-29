<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Jelly migration driver.
 *
 * The code draws heavily from Migration_Sprig module, written by
 * Oliver Morgan.
 *
 *
 * @package		Migration
 * @author		Oliver Morgan, Raoul Lättemäe
 * @uses		DBForge
 * @copyright   (c) 2009 Oliver Morgan
 * @copyright	(c) 2010 Raoul Lättemäe
 * @license		MIT
 */
class Migration_Jelly extends Migration {

	/**
	 * The Jelly model object.
	 *
	 * @var	Jelly
	 */
	protected $_model;

	protected function _model($model)
	{
            
		// If the model is given as a string
		if (is_string($model))
		{
                   // print_r($model);die;
			// Return the Jelly object
			return Jelly::meta($model);
		}
		// If the model is an object instance of Jelly
		elseif (is_object($model) AND $model instanceof Jelly)
		{
			// Then return the model as is.
			return $model;
		}
		else
		{
			// Default route indicates failure.
			throw new Kohana_Exception('Invalid Jelly model :model given to Jelly migration driver.', array(
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
		$model_pks = is_array($this->_model->primary_key()) ? $this->_model->primary_key() : array($this->_model->primary_key());

		// Loop through each field within the model
		foreach ($this->_model->fields() as $field)
		{
			// Check if the field implaments the migratable field interface
			if ($field instanceof Jelly_Field_Migratable)
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
			elseif ($field instanceof Jelly_Field_ManyToMany)
			{
				// ManyToMany fields also contain a pivot table
				$pivot = new Database_Table($field->through['model'], $this->_db);

				// Get fields
				$columns = $field->through['columns'];

				foreach ($columns as $field) {
					// Chekt if the field names are defaults
					if (strstr($field, ':'))
					{
						list($model, $field) = explode(':', $field);

						// Append the : back onto $field, it's key for recognizing the alias below
						$field = ':'.$field;

						// We should be able to find a valid meta object here
						if (FALSE == ($meta = Jelly::meta($model)))
						{
							throw new Kohana_Exception('Meta data for :model was not found while trying to resolve :field', array(
								':model' => $model,
								':field' => $field));
						}
						$field = $meta->foreign_key();
					}
					
					$column = Database_Column::factory('int');
					$column->auto_increment = FALSE;
					$column->name = $field;
					$cols[] = $column;
				}

				// Add to pivot
				foreach ($cols as $column)
				{
					// Add it to the pivot table
					$pivot->add_column($column);
				}

				// Add a primary key constraint on all fields within the pivot table
				$pivot->add_constraint(new Database_Constraint_Primary(
					array_keys($pivot->columns()), $pivot->name
				));
				
				/**
				 * @todo It would be more than appropriate to add a contstraint in a following 
				 * form into a database:
							ALTER TABLE `roles_users`
						  ADD CONSTRAINT `roles_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
				 * 
				 */

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
               
		// Returns the database name as a string.
		return Database::instance($this->_model->db());
	}

	/**
	 * Gets the database columns associated with the field.
	 *
	 * @param	Jelly_Field	The Jelly field.
	 * @param	Database_Table	The parent database table.
	 * @return	array
	 */
	private function _columns(Jelly_Field $field, Database_Table $table)
	{
		// print_r($field);

		// Break If the column is not present in database
		if ( ! $field->in_db)
		{
			return;
		}

		//
		switch (TRUE) 
		{
			// Strings
			case ($field instanceof Jelly_Field_String):
			case ($field instanceof Jelly_Field_File): // Saves file upload path
			case ($field instanceof Jelly_Field_Enum): 	// Handles lists.
				// all relationships below are extending the String field
				//
				// case ($field instanceof Jelly_Field_Slug):
				// case ($field instanceof Jelly_Field_Email):
				// case ($field instanceof Jelly_Field_Password):
				//
				// Password is 40 char long Sha-1

				// Create a new database column
				$column = Database_Column::factory('varchar');
				// Set the varchar's max length to a default of 64
				
				// @todo More appropriate would be to check length before...
				
				if(($field instanceof Jelly_Field_Email) OR ($field instanceof Jelly_Field_Slug))
				{
					$column->max_length = 255;
				} 
				elseif ($field instanceof Jelly_Field_Password)
				{
					$column->max_length = 255;
				} 
				
				// @todo What is Jelly rules syntax? Is it in array or not?
				elseif (empty($field->rules['max_length'][0]))
				{
					$column->max_length = 255;
				} else {
					$column->max_length = $field->rules['max_length'][0];
				}
				break;

				// Integers
			case ($field instanceof Jelly_Field_Integer):
				// Use the int datatype and create the column
				$column = Database_Column::factory('int');
				// If the field is Jelly_Field_Auto then auto_increment is set to true.
				break;
				// Booleans
			case ($field instanceof Jelly_Field_Boolean):
				$column = Database_Column::factory('bool');
				break;
				// Primary key -- can be an integer or string, but we presume its int
			case ($field instanceof Jelly_Field_Primary):
				$column = Database_Column::factory('int');
				$column->auto_increment = TRUE;
				$column->unique = TRUE;
				// $column->primary = TRUE;
				break;
				// Texts
			case ($field instanceof Jelly_Field_Text):
                        case ($field instanceof Jelly_Field_Ckeditor):
			case ($field instanceof Jelly_Field_Serialized): // Handles serialized data
				if (isset($field->rules['max_length'][0]))
				{
					$lenght = $field->rules['max_length'][0];
					switch (TRUE)
					{
						case $lenght <= 65535:
							$column = Database_Column::factory('text');
							break;
						case $lenght <= 16777215:
							$column = Database_Column::factory('mediumtext');
							break;
						case $lenght <= 4294967295:
							$column = Database_Column::factory('longtext');
							break;
					}
				} 
				else 
				{
					$column = Database_Column::factory('text');
				}
				// TODO: Ugly hack, fix it. (Text datatypes dont take parameters).
				unset($column->max_length);
				break;
				// Timestamps
			case ($field instanceof Jelly_Field_Timestamp):
				$column = Database_Column::factory('timestamp');
				break;
				// Floats
			case ($field instanceof Jelly_Field_Float):
				$column = Database_Column::factory('float');
				// Relationships
				// case ($field instanceof Jelly_Field_Relationship):
				// all relationships below are extending this field:
				//
				// case ($field instanceof Jelly_Field_ManyToMany):
				// case ($field instanceof Jelly_Field_HasMany):
				// case ($field instanceof Jelly_Field_HasOne):
			case ($field instanceof Jelly_Field_BelongsTo):
				$column = Database_Column::factory('int');
				$column->auto_increment = FALSE;
				$column->name = $field->column;
				if (!empty($field->default)) $column->default = $field->default;
				if (!empty($field->null)) $column->nullable = (bool)$field->null;
				
				/**
				 * @todo It would be appropriate to add a contstraint :
							ALTER TABLE `roles_users`
						  ADD CONSTRAINT `roles_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
				 * 
				 */
				
				return array($column);
				break;
			default:
				break;
		}

		// Set the remaining values of the column
		$column->name = $field->name;
		if (!empty($field->default)) $column->default = $field->default;
		if (!empty($field->null)) $column->nullable = (bool)$field->null;
		if (!empty($field->unique)) $column->unique = $field->unique;
		if (!empty($field->auto_increment)) $column->auto_increment  = $field->auto_increment;
		// The column is nullable if the field is not empty
		// $column->nullable = $field->null;// ! $field->empty;
		// $column->unique = $field->unique;

		// Return the column as an array
		return array($column);
	}

} // End Migration_Jelly
