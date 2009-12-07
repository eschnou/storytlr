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
 *  JAVASCRIPT FOR SETTING THEME
 */
 
 var current_theme;
var cp_title; 			
var cp_subtitle; 		
var cp_sidebar_border; 	
var cp_background; 		
var cp_link; 			
var cp_sidebar_text; 	
var cp_sidebar_header; 	

function toggleThemes() {
	$('theme_chooser').toggle();
}

function toggleCss() {
	if ($('css_enabled').checked) {
		$('css_div').show();
	} else {
		$('css_div').hide();
		submitFormCss();
	}
}

function clearCss() {
	$('css_content').value='';
}

function setColors(colors) {

	// Title
	if (typeof(colors.title) != 'undefined') {
		$('color_title').value = colors.title;
		$('color_title').show();
	} else {
		$('color_title').value = '';
		$('color_title').hide();
	}

	// Subtitle
	if (typeof(colors.subtitle) != 'undefined') {
		$('color_subtitle').value = colors.subtitle;
		$('color_subtitle').show();
	} else {
		$('color_subtitle').value = '';
		$('color_subtitle').hide();
	}	
	
	// Sidebar border
	if (typeof(colors.sidebar_border) != 'undefined') {
		$('color_sidebar_border').value = colors.sidebar_border;
		$('color_sidebar_border').show();
	} else {
		$('color_sidebar_border').value = '';
		$('color_sidebar_border').hide();
	}
	
	// Background
	if (typeof(colors.background) != 'undefined') {
		$('color_background').value = colors.background;
		$('color_background').show();
	} else {
		$('color_background').value = '';
		$('color_background').hide();
	}
		
	// Link
	if (typeof(colors.link) != 'undefined') {
		$('color_link').value = colors.link;
		$('color_link').show();
	} else {
		$('color_link').value = '';
		$('color_link').hide();
	}
	
	// Sidebar text
	if (typeof(colors.sidebar_text) != 'undefined') {
		$('color_sidebar_text').value = colors.sidebar_text;
		$('color_sidebar_text').show();
	} else {
		$('color_sidebar_text').value = '';
		$('color_sidebar_text').hide();
	}
	
	// Sidebar header
	if (typeof(colors.sidebar_header) != 'undefined') {
		$('color_sidebar_header').value = colors.sidebar_header;
		$('color_sidebar_header').show();
	} else {
		$('color_sidebar_header').value = '';
		$('color_sidebar_header').hide();
	}
	
	// Update the swatch
	cp_title.updateSwatch();
	cp_subtitle.updateSwatch();
	cp_sidebar_border.updateSwatch();
	cp_background.updateSwatch();
	cp_link.updateSwatch();
	cp_sidebar_text.updateSwatch();
	cp_sidebar_header.updateSwatch();
}

function resetFormColors() {
	setColors(themes[current_theme]);
}

function submitFormDesign() {
        form = new ValidateForm($('formDesign'),"admin/design/submit");
        form.errorElem 		= $('error_messages');
        form.successElem 	= $('status_messages');
        form.waitElem 		= $('wait');
        form.successMessage = 'Your changes have been saved';
        return form.submit();
}

function submitFormColors() {
        form = new ValidateForm($('formColors'),"admin/design/savecolors");
        form.errorElem 		= $('error_messages');
        form.successElem 	= $('status_messages');
        form.waitElem 		= $('wait');
        form.successMessage = 'Your changes have been saved';
        return form.submit();
}

function submitFormCss() {
        form = new ValidateForm($('formCss'),"admin/design/savecss");
        form.errorElem 		= $('error_messages');
        form.successElem 	= $('status_messages');
        form.waitElem 		= $('wait');
        form.successMessage = 'Your changes have been saved';
        return form.submit();
}

function onRemoveBackground() {
	new Ajax.Request('admin/design/clearimage', {
  					method: 'post', 
  					parameters: {image: 'background'},
  					onSuccess: removeBackgroundSuccess.bind(this)});
}

function removeBackgroundSuccess(req) {
	var errors = req.responseText.evalJSON();
	if(!errors) {
		$('form_design_background_image').hide();
	}
	else {
		alert("Error - try again or contact support@storytlr.com");
	}
}

function onRemoveHeader() {
	new Ajax.Request('admin/design/clearimage', {
  					method: 'post', 
  					parameters: {image: 'header'},
  					onSuccess: removeHeaderSuccess.bind(this)});
}

function removeHeaderSuccess(req) {
	var errors = req.responseText.evalJSON();
	if(!errors) {
		$('form_design_header_image').hide();
	}
	else {
		alert("Error - try again or contact support@storytlr.com");
	}
}

function onSelectTheme(theme) {

	current_theme = theme;

	if (theme == 'xcustom') {
		$('colors_panel').hide();
	} else {
		$('colors_panel').show();
		setColors(themes[theme]);
	}
	
	new Ajax.Request('admin/design/savetheme', {
  					method: 'post',
  					
  					parameters: {theme: theme},
  					
  					onSuccess: function(req) {
								var errors = req.responseText.evalJSON();
								if(!errors) {
									$('theme_selected').writeAttribute('src', 'themes/' + theme + '/screenshot.png');
								}
								else {
									alert("Error - try again or contact support@storytlr.com");
								}
							},
								
  					onFailure: onFailure.bind(this) });
}

function onFailure(req) {
	alert("Error - try again or contact support@storytlr.com");
}
