function clipboard(element) {
	var area = $(element);
	area.focus()
    area.select()
	var range = area.createTextRange();
	range.execCommand("Copy");
}

function showDialog(name) {
	var wrapper   = $('overlay_frame_wrapper');
	var frame     = $('overlay_frame');
	
	if (!wrapper.visible()) {
		wrapper.show();
	    frame.src = '/dialogs/' + name + '/';
	}
	else {
		wrapper.hide();
	}
}