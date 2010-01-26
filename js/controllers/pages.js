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
 *  JAVASCRIPT FOR PAGES
 */
 
 function onListOrderChange() {
	new Ajax.Request('admin/pages/order', {
					
					method: 'post',
					
					onComplete: function(req){
									new Effect.Highlight("user_pages",{});
								}, 
					
					parameters:Sortable.serialize("user_pages")});
}

function onRemovePage(id) {
	new Ajax.Request('admin/pages/delete', {
					
					method: 'post',
					
					parameters: {id: id},
					
					onSuccess: function(req){
									var page = $('page_'+id);
									new Effect.Disapear(page, { duration: 1.0 });
									//page.remove();
								}
					});
}

function onFormSubmit() {
		tinyMCE.triggerSave(); 
        form = new ValidateForm($('form_page'), "admin/pages/save");
        form.errorElem = $('error_messages');
		form.successCallback = function() {window.location = 'admin/pages'};
        return form.submit();
}

function onFailure(req) {
	alert("Error - try again or contact support@storytlr.com");
}
