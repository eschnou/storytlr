function onLoad() {
	initTimezones();
}

function submitFormSettings() {
        form = new ValidateForm($('formSettings'),"admin/preferences/submit");
        form.errorElem 		= $('error_messages');
        form.successElem 	= $('status_messages');
        form.waitElem 		= $('wait');
        form.successMessage = 'Your changes have been saved';
        return form.submit();
}

function initTimezones() {
	for(var index=0; index<zones.length; index++){
	   $('timezoneid').insert("<option value='" + index + "'>" + zones[index] + "</option>");
	}
	
	$('timezoneid').selectedIndex = timezone;
}

function toggleTwitter() {
	if ($('twitter_notify').checked) {
		$('twitter_config').show();
	} else {
		$('twitter_config').hide();
		$('twitter_password').value = '';
	}
}