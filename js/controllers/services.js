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
 *  JAVASCRIPT FOR SERVICES
 */
 
 var remove_id	= 0;

function onRemove(id, name) {
	remove_id	= id;
	$('confirm_question').update("Are you sure you want to delete your " + name + " account and all associated data and comments ?");
	$('confirm_delete').show();
}

function onCancelDelete() {
	remove_id = 0;
	$('confirm_delete').hide();
	$('confirm_question').update("");
}

function onConfirmDelete() {
	var id 		= remove_id;
	remove_id 	= 0;
	
	new Ajax.Request('admin/services/delete', {
  					method: 'post',
  					parameters: {id: id}
  	});
  	
 	$('confirm_delete').hide();
	$('confirm_question').update("");
 	
 	Effect.Disapear('source_' + id, { duration: 0.4}  );
 	
 	var c	= Number($('sources_count').innerHTML) - 1;
 	
 	if (c>0) {
	 	$('sources_count').update(c);
	}
	else {
	 	$('user_sources').update("You don't have any data source configured yet. Pick a service to start importing your data now into storytlr.");
	}	 	 	
}

function onFormSubmit() {
	$('overlay_status_wrapper').show();
}