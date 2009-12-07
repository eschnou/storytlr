//
//  In my case I want to load them onload, this is how you do it!
// 
Event.observe(window, 'load', loadAccordions, false);

//
//	Set up all accordions
//
function loadAccordions() {
	var bottomAccordion = new accordion('accordion_archive');
	
	// Open first one
	bottomAccordion.activate($$('#accordion_archive .accordion_toggle')[0]);
}

	