<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Controller for FormoJS requests
 *
 * @package FormoJS
 */
class Controller_FormoJS extends Controller {

	/**
	 * Action handler for the javascript config file 
	 *
	 * Defines the URL used for validation etc.
	 */
	public function action_config()
	{
		$this->request->headers['Content-Type'] = 'application/javascript';
		$this->request->response = 'if (typeof FormoJS == \'undefined\') FormoJS = {}; FormoJS.validate_url = \''. URL::site('formojs/validate/'). '/\';';
	}
	
	/**
	 * Action handler for static javascript file requests
	 * (Copied from the userguide module)
	 */
	public function action_javascript()
	{
		// Generate and check the ETag for this file
		$this->request->check_cache(sha1($this->request->uri));

		// Get the file path from the request
		$file = $this->request->param('file');

		// Find the file extension
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		// Remove the extension from the filename
		$file = substr($file, 0, -(strlen($ext) + 1));

		if ($file = Kohana::find_file('js/formojs', $file, $ext))
		{
			// Send the file content as the response
			$this->request->response = file_get_contents($file);
		}
		else
		{
			// Return a 404 status
			$this->request->status = 404;
		}

		// Set the proper headers to allow caching
		$this->request->headers['Content-Type']   = File::mime_by_ext($ext);
		$this->request->headers['Content-Length'] = filesize($file);
		$this->request->headers['Last-Modified']  = date('r', filemtime($file));
	}
	

	/**
	 * Action handler for individual field validation
	 *
	 * Returns JSON data describing the result of the validation.
	 */
	public function action_field()
	{
		// Get the original request from the referrer, so we can find which controller and action we should be expecting
		$req = $this->get_request(Request::$referrer);

		if ($req == NULL) {
			$response = array('status' => 'error', 'message' => 'Unable to determine original request.');
		} else {
			try {
				$method = $this->get_form_provider($req);
				
				if ($method == NULL) {
					$response = array('status' => 'error', 'message' => 'Unable to find form provider for request.');
				} else {
					$form = $method->invoke(NULL);
		
					$field_name = $_POST['field'];
					$field = $form->find($field_name);
			
					if ($field == NULL) {
						$response = array('status' => 'error', 'message' => "Field $field_name not found!");
				
					} else {

						$field->load($_POST['value']);
						if ($field->validate(TRUE))
							$response = array('status' => 'valid');
						else
							$response = array('status' => 'invalid', 'message' => UTF8::ucfirst($field->error()));
					}
				}
			} catch (Exception $e) {
				$response = array('status' => 'error', 'message' => $e->getMessage());
			}
		}
		
		$this->request->headers['Content-Type'] = 'application/json';
		$this->request->response = json_encode($response);
	}
	
	
	/**
	 * Determine the original request for a URI
	 *
	 * @param string $uri The URI to simulate a request for.
	 * @return Request The request with a matching route.
	 */
	public function get_request($uri)
	{
		$uri = parse_url($uri, PHP_URL_PATH);
		
		$base_url = parse_url(Kohana::$base_url, PHP_URL_PATH);

		if (strpos($uri, $base_url) === 0)
		{
			// Remove the base URL from the URI
			$uri = substr($uri, strlen($base_url));
		}
		
		try {
			return Request::factory($uri);
		} catch (Kohana_Request_Exception $e) {
			return NULL;
		}
	}
	
	/**
	 * Get the form providing method for a particular request
	 *
	 * @param Request $req The request to follow 
	 * @return ReflectionMethod The form providing method.
	 */
	public function get_form_provider($req)
	{
		// Build the class name
		$class[] = 'controller';
		if ($req->directory != '')
			$class[] = $req->directory;
		$class[] = $req->controller;
		$class = join('_', $class);
		
		// Get the form providing method from the class documentation/markup.
		$provider = FormoJS::get_form_provider($class, 'action_'. $req->action);
		
		if ($provider == NULL)
			return NULL;
		
		// Return a method object
		$method = new ReflectionMethod($class, $provider);
		
		return $method;
	}
}
