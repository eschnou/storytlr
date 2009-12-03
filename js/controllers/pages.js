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
        form = new ValidateForm($('form_page'), "/admin/pages/save");
        form.errorElem = $('error_messages');
		form.successCallback = function() {window.location = '/admin/pages'};
        return form.submit();
}

function onFailure(req) {
	alert("Error - try again or contact support@storytlr.com");
}