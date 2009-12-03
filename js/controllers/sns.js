function submitFormTwitterConfig() {
        form = new ValidateForm($('formTwitterConfig'),"admin/sns/submit");
        form.errorElem 		= $('error_messages');
        form.successElem 	= $('status_messages');
        form.waitElem 		= $('wait');
        form.successMessage = 'Your changes have been saved';
        return form.submit();
}
