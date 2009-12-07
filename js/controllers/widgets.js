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
 *  JAVASCRIPT FOR WIDGETS
 */
 
 function onAddWidget(prefix) {
	new Ajax.Request('admin/widgets/add', {
  					method: 'post',
  					
  					parameters: {widget: prefix},
  					
  					onSuccess: function(req) {
								$('user_widgets').insert(req.responseText);	
								Sortable.create("user_widgets", {onUpdate:onListOrderChange});							
							},
								
  					onFailure: onFailure.bind(this) });
}

function onListOrderChange() {
	new Ajax.Request('admin/widgets/order', {
					
					method: 'post',
					
					onComplete: function(req){
									new Effect.Highlight("user_widgets",{});
								}, 
					
					parameters:Sortable.serialize("user_widgets")});
}

function onRemoveWidget(id) {
	new Ajax.Request('admin/widgets/delete', {
					
					method: 'post',
					
					parameters: {id: id},
					
					onSuccess: function(req){
									var widget = $('widget_'+id);
									new Effect.Disapear(widget, { duration: 1.0 });
									//widget.remove();
								}
					});
}

function onEditWidget(id) {
	var element = 'widget_edit_' + id;
	var action  = 'button_edit_widget_' + id;
	
	$(element).toggle();
	if ($(element).visible()) {
		$(action).update('Cancel');
	}
	else {
		$('form_widget_' + id).reset();
		$(action).update('Edit');		
	}
}

function onSubmitFormWidget(prefix, id) {
        form = new ValidateForm($('form_widget_' + id), "widgets/" + prefix + "/submit");
        form.errorElem = $('widget_edit_errors_' + id);
		form.successElem = $('widget_edit_status_' + id);
        return form.submit();
}

function onFailure(req) {
	alert("Error - try again or contact support@storytlr.com");
}