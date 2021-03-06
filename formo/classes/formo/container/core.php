<?php defined('SYSPATH') or die('No direct script access.');

/**
 * This class makes storing and retrieving objects neat
 *
 * @package  Formo
 */
abstract class Formo_Container_Core {

	/**
	 * Class-specific settings
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	protected $_settings = array();

	/**
	 * Where custom vars are stored
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	protected $_customs = array();

	protected $_loaded = array
	(
		'orm'    => FALSE,
		'driver' => FALSE
	);

	/**
	 * Container settings
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $_defaults = array
	(
		'alias'           => NULL,
		'parent'          => FALSE,
		'fields'          => array(),
		'driver_instance' => NULL,
		'label'           => NULL,
		'order'           => FALSE,
		'type'            => NULL,
	);

	/**
	 * Fetch a field or return a driver object
	 *
	 * @access public
	 * @param mixed $variable
	 * @return object or void
	 */
	public function __get($variable)
	{
		return $this->get_field($variable);
	}

	public function __isset($variable)
	{

		if (array_key_exists($variable, $this->_loaded))
			return (bool) $this->_loaded[$variable];
	}

	public function __invoke()
	{
		$args = func_get_args();

		if (func_num_args() === 0)
			return $this->val();

		if (func_num_args() === 1)
			return $this->get($args[0]);

		if (func_num_args() === 2)
			return $this->set($args[0], $args[1]);
	}

	/**
	 * Returns all fields in order
	 *
	 * @access public
	 * @param mixed $field. (default: NULL)
	 * @return array
	 */
	public function fields($field = NULL)
	{
		$unordered = array();
		$ordered = array();

		foreach ($this->get('fields') as $field)
		{
			$alias = $field->alias();
			$ordered[$alias] = $field;
		}

		return $ordered;
	}

	/**
	 * Fetch a field directly within its container
	 *
	 * @access public
	 * @param mixed $search
	 * @param mixed $option. (default: FALSE)
	 * @return object or void
	 */
	public function get_field($search, $option = FALSE)
	{
		if (is_array($search))
		{
			$fields = array();
			foreach ($search as $_search)
			{
				$field = $this->get_field($_search);
				$fields[$field->alias()] = $field;
			}

			return $fields;
		}

		// If $search is an int, search by key
		$find_by = is_string($search) ? 'item' : 'key';

		foreach ($this->_defaults['fields'] as $key => $field)
		{
			if ($find_by == 'key' AND $key === $search)
				return $field;

			if ($find_by == 'item' AND $search == $field->alias())
				return $field;

			if ($option AND $find_by == 'item' AND call_user_func($option, $search) == $field->alias())
				return $field;
		}
	}

	/**
	 * Runs the method through the driver
	 *
	 * @access public
	 * @param mixed $func
	 * @param mixed $args
	 * @return void
	 */
	public function __call($func, $args)
	{
		return call_user_func_array(array($this->driver(), $func), $args);
	}

	/**
	 * Set variables
	 *
	 * @access public
	 * @param mixed $variable
	 * @param mixed $value
	 * @return object
	 */
	public function set($variable, $value, $force_into_field = FALSE)
	{
		// Support array of key => values
		if (is_array($variable))
		{
			foreach ($variable as $_variable => $_value)
			{
				$this->set($_variable, $_value);
			}

			return $this;
		}
		
		// Allow the driver to alter the variable beforehand
		// but obviously it can't happen when setting the driver instance
		if ($variable != 'driver_instance' AND method_exists($this->driver(), "set_$variable"))
		{
			$value = $this->driver()->{'set_'.$variable}($value);
		}

		if (array_key_exists($variable, $this->_defaults))
		{
			$this->_defaults[$variable] = $value;
			return $this;
		}

		if (array_key_exists($variable, $this->_settings))
		{
			// First look for variables in $_settings
			$this->_settings[$variable] = $value;
			return $this;
		}

		// Otherwise let the driver do the setting
		$this->driver()->set($variable, $value);

		return $this;
	}

	/**
	 * Load construct options
	 *
	 * @access public
	 * @param mixed $option
	 * @param mixed $value. (default: NULL)
	 * @return object
	 */
	public function load_options($option, $value = NULL)
	{
		// Support array of options
		if (is_array($option))
		{
			foreach ($option as $_option => $_value)
			{
				$this->load_options($_option, $_value);
			}

			return $this;
		}

		// Otherwise just set the variable
		$this->set($option, $value);

		return $this;
	}

	/**
	 * Pass variable by reference
	 *
	 * @access public
	 * @param mixed $variable
	 * @param mixed $key
	 * @param mixed & $value
	 * @return object
	 */
	public function bind($variable, $key, & $value)
	{
		if ($key)
		{
			$this->{$variable}[$key] &= $value;
		}
		else
		{
			$this->{$variable} &= $key;
		}

		return $this;
	}

	/**
	 * Fetch variable(s)
	 *
	 * @access public
	 * @param mixed $variable
	 * @param mixed $default. (default: FALSE)
	 * @return mixed
	 */
	public function get($variable, $default = FALSE)
	{
		$arrays = array('_defaults', '_settings', '_customs');

		foreach ($arrays as $array)
		{
			if (array_key_exists($variable, $this->$array))
			{
				return $this->{$array}[$variable];
			}
		}

		// Otherwise run get through the driver
		return $this->driver()->get($variable, $default);
	}

	/**
	 * Return the model
	 *
	 * @access public
	 * @return void
	 */
	public function model($return_driver = FALSE)
	{
		if (isset($this->orm))
		{
			return ($return_driver === TRUE)
				? $this->orm_driver()
				: $this->orm_driver()->model;
		}

		if ($this->parent() !== FALSE)
			return $this->parent()->model($return_driver);

		return FALSE;
	}

	/**
	 * Create a subform from fields already in the Container object
	 *
	 * @access public
	 * @param mixed $alias
	 * @param mixed $driver
	 * @param mixed array $fields
	 * @param mixed $order. (default: NULL)
	 * @return object
	 */
	public function create_sub($alias, $driver, array $fields, $order = NULL)
	{
		// Create the empty subform object

		$subform = Formo::form($alias, $driver);

		foreach ($fields as $key => $field)
		{
			if (is_string($key) AND ! ctype_digit($key))
			{
				// Pull fields "as" a new alias
				$new_alias = $field;
				$field = $key;
			}

			// Find each field
			$_field = $this->find($field);

			if ( ! $_field)
				// Throw an exception if the field doesn't exist
				throw new Kohana_Exception("Formo_Container: Field $field is not in form");

			if ( ! empty($new_alias))
			{
				// Set the new alias
				$_field->alias($new_alias);
			}

			// Remember the field's original parent
			$last_parent = $_field->parent();

			// Add the field to the new subform
			$subform->append($_field);

			// Remove the field from its original parent
			$last_parent->remove($_field->alias());
		}

		// If the parent has a model, copy it to the new subform
		$subform->set('model', $this->get('model'));

		// Add the order if applicable
		($order AND $subform->set('order', $order));

		// Append the new subform
		$this->append($subform);

		return $this;
	}

	/**
	 * Stores an item in the container
	 *
	 * @access public
	 * @param mixed $field
	 * @return object
	 */
	public function append($field)
	{
		// Set the field's parent
		$field->set('parent', $this);
		$field->set('type', $this->get('type'));
		$this->_defaults['fields'][] = $field;

		// Look for order and process it for ordering this field
		if ($field->get('order') !== FALSE)
		{
			$order = $field->get('order');
			$args = array($field);
			if (is_array($order))
			{
				$args[] = key($order);
				$args[] = current($order);
			}
			else
			{
				$args[] = $order;
			}

			call_user_func_array(array($this, 'order'), $args);
		}
		
		$field->driver()->append();

		return $this;
	}

	/**
	 * Add an item to the beginning of a container
	 *
	 * @access public
	 * @param mixed $item
	 * @return object
	 */
	public function prepend($item)
	{
		$item->_defaults['parent'] = $this;
		$item->set('type', $this->get('type'));

		array_unshift($this->_defaults['fields'], $item);

		return $this;
	}

	/**
	 * Removes a field from its container
	 *
	 * @access public
	 * @param mixed $alias
	 * @return object
	 */
	public function remove($alias)
	{
		// Support an array of fields
		if (is_array($alias))
		{
			foreach ($alias as $_alias)
			{
				$this->remove($_alias);
			}

			return $this;
		}

		foreach ($this->_defaults['fields'] as $key => $item)
		{
			if ($item->alias() == $alias)
			{
				unset($this->_defaults['fields'][$key]);
			}
		}

		return $this;
	}

	/**
	 * Return array of fields with the specified value for each
	 *
	 * @access public
	 * @param mixed $value. (default: NULL)
	 * @return array
	 */
	public function as_array($value = NULL)
	{
		// Create the empty array to fill
		$array = array();
		foreach ($this->_defaults['fields'] as $field)
		{
			$alias = $field->alias();

			// Make concession for grabbing 'value' as that one characteristic
			if ($value == 'value')
			{
				$array[$alias] = $field->val();
				continue;
			}

			// By default, return name => element
			$array[$alias] = ($value !== NULL)
				? $field->get($value)
				: $field;
		}

		return $array;
	}

	/**
	 * Retrieve a field's parent
	 *
	 * @access public
	 * @param mixed $search. (default: NULL)
	 * @return mixed
	 */
	public function parent($search = NULL)
	{
		$this_parent = $this->_defaults['parent'];

		// If not searching, return this parent
		if ($search === NULL)
			return $this_parent;

		// If searching for the topmost parent, return it if this is
		if ($search === Formo::PARENT AND ! $this_parent)
			return $this;

		// If this parent doesn't exist, return FALSE
		if ( ! $this_parent)
			return FALSE;

		// If the parent's alias matches the search term
		if ($this_parent AND $this_parent->alias() == $search)
			return $this_parent;

		// Recursively search for the correct parent
		return $this_parent->parent($search);
	}

	// Convenience method for fetching/setting alias
	public function alias($alias = NULL)
	{
		if (func_num_args() == 0)
			return $this->_defaults['alias'];

		$this->_defaults['alias'] = $alias;
		return $this;
	}

	/**
	 * Look through a container object for a field object by alias
	 *
	 * @access public
	 * @param mixed $alias
	 * @return mixed
	 */
	public function find($alias)
	{
		// If an array wasn't entered, look everywhere
		if ( ! is_array($alias))
		{
			// Whenever a match is found, return it
			if ($field = $this->get_field($alias))
				return $field;

			// Recursively look as deep as necessary
			foreach ($this->_defaults['fields'] as $field)
			{
				if ($found_field = $field->find($alias))
					return $found_field;
			}
		}
		// If an array was entered, follow the exact path of the array
		else
		{
			// Start with the first item
			$field = $this->get_field($alias[0]);
			// Go deeper for each item entered
			for($i=1; $i<count($alias); $i++)
			{
				$field = $field->field($alias[$i]);
			}

			return $field;
		}
	}

	/**
	 * Return the order a field is in
	 *
	 * @access protected
	 * @param mixed $search
	 * @return mixed
	 */
	protected function find_order($search)
	{
		$i = 0;
		foreach ($this->_defaults['fields'] as $field)
		{
			// Return the order if we just found it
			if ($field->alias() == $search)
				return $i;

			$i++;
		}

		// Return false upon failing
		return FALSE;
	}

	/**
	 * Get the key of a specific field
	 *
	 * @access protected
	 * @param mixed $field
	 * @return mixed
	 */
	protected function find_fieldkey($field)
	{
		foreach ($this->_defaults['fields'] as $key => $value)
		{
			if ($value->alias() == $field)
				return $key;
		}

		return FALSE;
	}

	/**
	 * Set the order of a new field
	 *
	 * @access public
	 * @param mixed $field
	 * @param mixed $new_order
	 * @param mixed $relative_field. (default: NULL)
	 * @return object
	 */
	public function order($field, $new_order, $relative_field = NULL)
	{
		// Find the field if necessary
		$field = (is_object($field) === FALSE) ? $this->find($field) : $field;

		// Pull out all the fields
		$fields = $field->parent()->get('fields');

		// Delete the current place
		unset($fields[$this->find_fieldkey($field->alias())]);

		// If the new order is a string, it's a comparative order
		if ( ! ctype_digit($new_order) AND is_string($new_order))
		{
			$position = $this->find_order($relative_field);

			// If the place wasn't found, do nothing
			if ($position === FALSE)
				return $this;

			$new_order = ($new_order == 'after') ? $position + 1 : $position;
		}

		// Make the insertion
		array_splice($fields, $new_order, 0, array($field));
		// Save the new order
		$field->parent()->set('fields', $fields);

		return $this;
	}

	/**
	 * Return or create a new driver instance
	 *
	 * @access public
	 * @param mixed $save_instance. (default: FALSE)
	 * @return Formo_Driver
	 */
	public function driver($save_instance = TRUE)
	{
		// Fetch the current settings
		$driver = $this->get('driver');
		$instance = $this->get('driver_instance');
		$class = 'Formo_Driver_'.ucfirst($driver);

		// If the instance is the correct driver for the field, return it
		if ($instance AND $instance instanceof $class)
			return $instance;

		// Build the class name
		$driver_class_name = Kohana::config('formo')->driver_prefix.UTF8::ucfirst($driver);

		// Create the new instance
		$instance = new $driver_class_name($this);

		if ($save_instance === TRUE)
		{
			// Save the instance if asked to
			$this->set('driver_instance', $instance);
		}

		$this->_loaded['driver'] = TRUE;

		// Return the new driver instance
		return $instance;
	}

	/**
	 * Load an orm driver instance
	 *
	 * @access public
	 * @param mixed $save_instance. (default: FALSE)
	 * @return Formo_ORM object
	 */
	public function orm_driver($save_instance = TRUE)
	{
		if ( ! $this instanceof Formo_Form)
			return $this->parent()->orm_driver(TRUE);

		if ($instance = $this->get('orm_driver_instance'))
			// If the instance exists, return it
			return $instance;

		// Get the driver neame
		$driver = Kohana::config('formo')->orm_driver;

		// Create the new instance
		$instance = new $driver($this);

		if ($save_instance === TRUE)
		{
			// Save the instance if asked to
			$this->set('orm_driver_instance', $instance);
		}

		$this->_loaded['orm'] = TRUE;

		// REturn the new orm driver instance
		return $instance;
	}

	/**
	 * Changes decorator object to new type for all fields within the
	 * field or subform
	 *
	 * @access public
	 * @param mixed $type
	 * @return void
	 */
	public function type($type)
	{
		$this->driver()->decorator($type);
		foreach ($this->fields() as $field)
		{
			$field->decorator($type);
		}

		return $this;
	}

}
