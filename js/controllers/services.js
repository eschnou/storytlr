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