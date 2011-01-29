if (typeof FormoJS == 'undefined')
	FormoJS = {};

FormoJS.Form = new Class({
	// The DOM element for the form
	element: null,
	// TODO future feature
	// // Current focus within this form
	// focus:   null,
	// Fields within this form
	fields: [],
	
	initialize: function (element)
	{
		this.element = element;
		// look for fields within this form
		element.getElements('p.field')
			.each(function (block) {
				var classes =  block.get('class').split(' ');
			
				var field = null;
				switch (classes[1])
				{
					case 'text':
					default:
						field = new FormoJS.Field(block, this);
				}
				
				this.fields.push(field);
			}, this);
	}
});

FormoJS.Field = new Class({
	elem_block: null,
	elem_input: null,
	form:    null,
	request: null,
	data:    null,

	initialize: function (block, form)
	{
		this.elem_block = block;
		this.elem_input = block.getElement('input');
		this.form = form;
		
		this.elem_input.addEvent('change', this.field_blurred.bindWithEvent(this));
		this.elem_input.addEvent('keypress', this.field_keypress.bindWithEvent(this));
		
		// TODO future feature
		// field.startFocusTracking(form);
	},
	
	// Delay timer on keypresses
	keypress_timer: null,
	
	field_blurred: function (event)
	{
		// Clear any remaining timer events on this field
		if (this.keypress_timer != null)
			$clear(this.keypress_timer);
		
		this.validate();
	},
	
	field_keypress: function (event)
	{
		// Clear the timer if it was already started
		if (this.keypress_timer != null)
			$clear(this.keypress_timer);
		
		// Start a new timer
		this.keypress_timer = (function () {
			this.validate();
		}).bind(this).delay(1500);
	},
	
	validate: function ()
	{
		var block = this.elem_block;
		var self = this;
		
		var data = 'field=' + this.elem_input.name + '&value=' + this.elem_input.value;

		// Don't validate the same data twice
		if (data == this.data)
			return;
		
		// Cancel any currently running validation requests
		if (this.request != null)
			this.request.cancel();

		this.data = data;
		
		this.request = new Request.JSON({
			url: FormoJS.validate_url + 'field',
			data: data,
			
			onRequest: function ()
			{
				block.removeClass('valid');
				block.removeClass('error');
				block.removeClass('server_error');
				block.addClass('validating');
			},
			onComplete: function()
			{
				block.removeClass('validating');
				self.request = null;
			},
			onFailure: function ()
			{
				block.addClass('server_error');
				
				// TODO add more warning next to field
				self.set_message(this.xhr.responseText);
			},
			onSuccess: function (responseJSON, responseText)
			{
				if (responseJSON.status == 'valid')
					block.addClass('valid');
				else if (responseJSON.status == 'invalid')
					block.addClass('error');
				else
					block.addClass('server_error');
				
				self.set_message(responseJSON.message);
			}
		});
		// TODO see if this is needed/works when the client has referrers turned off
		this.request.setHeader('Referer', String(document.location));
		this.request.send();
	},
	
	set_message: function (message)
	{
		this.elem_block.getElement('.error-message').set('html', message);
	}
});

// Disabled for a future feature
// FormoJS.FocusTracker = {
// 	startFocusTracking: function(form)
// 	{
// 		this.store('hasFocus', false);
// 		this.addEvent('focus', function() { this.store('hasFocus', true ); form.focus = this; });
// 		this.addEvent('blur' , function() { this.store('hasFocus', false); form.focus = null; });
// 	},	
// 	hasFocus: function()
// 	{
// 		return this.retrieve('hasFocus');
// 	}
// };
// Element.implement(FormoJS.FocusTracker);

// We can't start until the DOM is available
window.addEvent('domready', function() {
	// Check we have the config.
	if (typeof FormoJS.validate_url == 'undefined')
	{
		// Log to the console if it's available.
		if (typeof console != 'undefined')
			console.log('Validation URL not configured.');
		return;
	}
	
	// Find and initialise all the forms
	$$('.formo_form').each(function (form) {
		new FormoJS.Form(form);
	});
});