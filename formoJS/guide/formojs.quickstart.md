# Getting Started #

There are 4 basic steps to adding FormoJS to a page:

1.	[Extracting the form creation](#extracting)
2.	[Designating the form provider](#designating)
3.	[Injecting the form](#injecting)
4.	[Adding the libraries](#libraries)

## Extracting the form ## {#extracting}

FormoJS needs access to the same form object as the original request, but from a new javascript request.
To do this we first extract the form object construction to another method.

	class Controller_Example extends Controller {
	
		public function action_index() {
			$form = Formo::form()
				->add('username')
				->add('email');
		}
	}

becomes:

	class Controller_Example extends Controller {
		
		public function action_index() {
			/* ... */
		}
	
		public static function build_form() {
			return Formo::form()
				->add('username')
				->add('email');
		}
	}

## Designating the form provider ## {#designating}

Now we must indicate which method provides the form(s) to the action handler

There are 3 ways of achieving this:

	class Controller_Example extends Controller {
		
		// Either this form
		protected $forms = array('Controller_Example::action_index' => 'build_form');
		
		// Or this
		protected $forms = array('action_index' => 'build_form');
		
		// Or the method annotation syntax below
		/**
		 * @formProvider build_form
		 */
		public function action_index() {
			
		}
		
		/* ... */

## Injecting the form ## {#injecting}

You may either manually call `build_form()` from the action handler to obtain an instance of the form object, or you may extend `FormoJS_Controller`.
FormoJS_Controller overrides Controller::before() to setup the form, and add it to the `forms` instance variable.

Below is an example of how this may be achieved

	class Controller_Example extends FormoJS_Controller {
		
		/**
		 * @formProvider build_form
		 */
		public function action_index() {
			$form = $this->forms[__METHOD__];
			// Or
			$form = self::build_form();
		}
	
		/* ... */

## Adding the libraries ## {#libraries}

Finally you must include the FormoJS javascript libraries in your page.
Remember that the framework libraries (eg. Mootools or jQuery) should be included **before** the FormoJS libraries.

	<head>
		<title>My Page Title</title>
		<!-- Framework libraries should be referenced here -->
		<?php echo FormoJS::libs(); ?>
		<!-- ... -->
	</head>