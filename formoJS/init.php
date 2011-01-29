<?php defined('SYSPATH') or die('No direct script access.');

// Route for validating fields or forms.
Route::set('formojs/validate', 'formojs/validate/<action>',
	array('field' => '(field)'))
	->defaults(array(
		'controller' => 'FormoJS',
	));

// Route for loading config from
Route::set('formojs/config', 'formojs/config')
	->defaults(array(
		'controller' => 'FormoJS',
		'action'     => 'config',
	));


// Route for static javascript files
// Copied from the userguide module
Route::set('formojs/js', 'formojs/js(/<file>)', array('file' => '.+'))
	->defaults(array(
		'controller' => 'FormoJS',
		'action'     => 'javascript',
		'file'       => NULL,
	));
