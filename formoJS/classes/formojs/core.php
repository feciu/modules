<?php defined('SYSPATH') or die('No direct script access.');

/**
 * The main utility class for FormoJS
 *
 * @package FormoJS
 */
class FormoJS_Core {

	/**
	 * Returns the full name of the form providing method for a given method.
	 *
	 * @return string full name of the form providing method
	 * @throws Exception throws an exception if the form providing method is not static.
	 */
	public static function get_form_provider($class, $method)
	{
		$method = new ReflectionMethod($class, $method);
		preg_match('/@formProvider ([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $method->getDocComment(), $matches);
		
		if (count($matches) == 0)
			return null;
		
		$providing_method = $matches[1];
		
		$m = new ReflectionMethod($class, $providing_method);
		if (! $m->isStatic())
			throw new Exception('The form providing method must be static.');
		
		return $providing_method;
	}

	/**
	 * Emits the HTML script tags necessary for the FormoJS libraries
	 *
	 * @return string HTML script tags to include the relevant libraries.
	 */
	public static function libs()
	{
		if (Kohana::$environment == Kohana::DEVELOPMENT)
			return HTML::script('formojs/config') ."\n". HTML::script('formojs/js/mootools.js');
		else
			return HTML::script('formojs/config') ."\n". HTML::script('formojs/js/mootools-yc.js');
	}
}
