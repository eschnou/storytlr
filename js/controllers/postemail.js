function updateName(value) {
	$('secret_link').innerHTML = value;
}

function submitFormPostEmail() {
        form = new ValidateForm($('formPostEmail'),"admin/postemail/submit");
        form.errorElem 		= $('error_messages');
        form.successElem 	= $('status_messages');
        form.waitElem 		= $('wait');
        form.successMessage = 'Your changes have been saved';
        return form.submit();
}