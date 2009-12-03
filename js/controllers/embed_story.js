function showStory(id) {
	var mask   = document.getElementById('storytlr_mask_' + id);
	var story  = document.getElementById('storytlr_container_' + id);
	
	mask.style.display = "block";
	story.style.display = "block";
}

function hideStory(id) {
	var mask   = document.getElementById('storytlr_mask_' + id);
	var story  = document.getElementById('storytlr_container_' + id);
	
	mask.style.display = "none";
	story.style.display = "none";
}
