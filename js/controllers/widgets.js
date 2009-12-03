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