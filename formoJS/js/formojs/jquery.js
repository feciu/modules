alert('Sorry, FormoJS jQuery support isn\'t ready yet.');

if (typeof FormoJS == 'undefined')
	FormoJS = {};
	
FormoJS.Form = function(element)
{
	this.element = element;
	this.focus   = null;
	this.fields  = [];
	
	var self = this;
	
	$('input[type!=submit]', element)
		.each(function () {
			var field = this;
			var fieldObj = new FormoJS.Field(field);
			self.fields.push(fieldObj);

			$(field).blur(function (event) {
				self.field_blurred(event, field, fieldObj);
			});
		});
};

FormoJS.Form.prototype.field_blurred = function(event, field, fieldObj)
{
	fieldObj.validate();
};

FormoJS.Field = function(element)
{
	this.element = element;
};
	
FormoJS.Field.prototype.get_block = function()
{
	return $(this.element).parent().parent().parent();
};
	
FormoJS.Field.prototype.validate = function()
{
	var self = this;
	var block = this.get_block();
	
	$.ajax({
		type: 'post',
		dataType: 'json',
		url: FormoJS.validate_url + 'field',
		data: 'field=' + this.element.name + '&value=' + this.element.value,
		
		beforeSend: function () {
			block.removeClass('valid');
			block.removeClass('error');
			block.removeClass('server_error');
			block.addClass('validating');
		},
		error: function (req, textStatus, errorThrown) {
			block.removeClass('validating');
			block.addClass('server_error');
			
			// TODO add more warning next to field
			self.set_message(errorThrown);
		},
		success: function (data, textStatus, req) {
			block.removeClass('validating');
			if (data.status == 'valid')
				block.addClass('valid');
			else if (data.status == 'invalid')
				block.addClass('error');
			else
				block.addClass('server_error');
			
			self.set_message(data.message);
		}
	});
};
	
FormoJS.Field.prototype.set_message = function(message)
{
	$('.error-message', this.get_block()).html(message);
};

$(document).ready(function() {
	if (typeof FormoJS.validate_url == 'undefined')
	{
		if (typeof console != 'undefined')
			console.log('Validation URL not configured.');
		return;
	}
	
	$('.formo_form').each(function (form) {
		new FormoJS.Form(form);
	});
});