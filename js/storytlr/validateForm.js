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
 *  JAVASCRIPT VALIDATE FORM
 */
 
 var ValidateForm = Class.create();

ValidateForm.prototype = {
	form: '',
	url: '',
	additionalParams: '',
	waitElem: '',
	errorElem: '',
	successElem: '',
	successMessage: 'Success !',
	errors: '',
	errorCallback: null,
	successCallback: null,
	
	initialize: function(form,url,additionalParams) {
		this.form = $(form);
		this.url = url;
	},

	submit: function(additionalparams) {
		if (this.waitElem) this.waitElem.show();
		if (this.errorElem) this.errorElem.update('');
		if (this.successElem) this.successElem.update('');		
		if(!additionalparams) additionalparams = '';
		var params = Form.serialize(this.form) + additionalparams;
		var myAjax = new Ajax.Request(this.url,{method:'post', parameters: params, onSuccess: this.processForm.bind(this)});
		return false;	
	},

	processForm: function(req) {
		if (this.waitElem) this.waitElem.hide();
		try {
			var errors = req.responseText.evalJSON();
			this.errors = errors;
		}
		catch(e) {
			return;
		};
		
		if(!errors) {
			if (this.successElem) this.showSuccess(this.successMessage);
			if (this.successCallback) this.successCallback();
		} else {
				this.showErrors(errors);
				if(this.callback) this.errorCallback();
		}
	},
	
	showErrors: function(errors) {
		var html = '<ul>';
		for (var index = 0; index < errors.length; ++index) {
  			html += '<li>' + errors[index] + '</li>';
		};
		html += '</ul>';
		this.errorElem.innerHTML = html;
		this.errorElem.show();
	},
	
	showSuccess: function(message) {
		var html = '<ul>';
  		html += '<li>' + message + '</li>';
		html += '</ul>';
		this.successElem.innerHTML = html;
		this.successElem.show();	
	}
}