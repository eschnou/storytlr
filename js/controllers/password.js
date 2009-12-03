function submitFormPassword() {
        form = new ValidateForm($('formPassword'),"admin/password/submit");
        form.errorElem 		= $('error_messages');
        form.successElem 	= $('status_messages');
        form.waitElem 		= $('wait');
        form.successMessage = 'Your new password has been saved';
        return form.submit();
}