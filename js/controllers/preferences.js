/*
 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 *  JAVASCRIPT FOR PREFERENCES
 */
 
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