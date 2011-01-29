<?php defined('SYSPATH') or die('No direct script access.');

/**
 * FormoJS abstract controller which has helpers for automatic form providing.
 *
 * @package FormoJS
 */
abstract class FormoJS_Controller extends Controller {
	
	protected $forms = array();
	
	/**
	 * Populates the form array before the controller action is invoked.
	 *
	 * TODO improve this to handle callbacks a lot better.
	 * @return void
	 */
	public function before()
	{
		parent::before();
		
		$method = 'action_' . (empty($this->request->action) ? Route::$default_action : $this->request->action);
		$q_method = get_class($this) .'::'. $method;
		
		if (isset($this->forms[$q_method]) && is_string($this->forms[$q_method]))
		{
			$providing_method = $this->forms[$q_method];
		}
		else if (isset($this->forms[$method]) && is_string($this->forms[$method]))
		{
			$providing_method = $this->forms[$method];
		}
		else
		{
			$providing_method = FormoJS::get_form_provider($this, $method);
		}
		
		if ($providing_method == NULL)
			return;
		
		$this->forms[$q_method] = $this->$providing_method();
	}
}