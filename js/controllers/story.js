function toggle_show_item(story, source, item) {

	var element = 'item_' + source + '_' + item;
	var action  = 'toggle_show_item_a_' + source + '_' + item;
	var img		= 'toggle_show_item_img_' + source + '_' + item;

	if ($(element).hasClassName('hidden')) {
		$(element).removeClassName('hidden');	
		$(action).writeAttribute('title', 'Hide from story');
		$(img).writeAttribute('src', 'images/lock.gif');
		new Ajax.Request('admin/story/showItem/', { 
						  method: 'post',
						  parameters: {story: story, source: source, item: item} 
						  });
	}
	else {
		$(element).addClassName('hidden');	
		$(action).writeAttribute('title', 'Show in story');
		$(img).writeAttribute('src', 'images/lock_open.gif');
		new Ajax.Request('admin/story/hideItem/', { 
						  method: 'post',
						  parameters: {story: story, source: source, item: item} 
						  });
	}
}

function set_cover_picture(story, source, item) {
	
	$('item_wait_'+ source + '_'+ item).show();
	
	new Ajax.Request('admin/story/setCover/', { 
					  method: 'post',
					  parameters: {story: story, source: source, item: item}, 
					  onComplete: function(req) {$('item_wait_'+ source + '_'+ item).hide();
					  							 $('item_done_'+ source + '_'+ item).show();
					  							 new Effect.Fade($('item_done_'+ source + '_'+ item), { delay:2, duration: 1.0}  );
					  							 }
					  });
}

function toggle_button(button, element, hide, show) {
	$(element).toggle();
	if ($(element).visible()) {
		$(button).writeAttribute('title', hide);
		$(button).writeAttribute('src', 'images/button_hide.png');
	}
	else {
		$(button).writeAttribute('title', show);
		$(button).writeAttribute('src', 'images/button_show.png');
	}
}

function onLoginClick() {
	if ($('adminbar').visible()) {
		Effect.BlindUp('adminbar', { duration: 0.4} );
	}
	else {
		Effect.BlindDown('adminbar', { duration: 0.4}  );
	}
}