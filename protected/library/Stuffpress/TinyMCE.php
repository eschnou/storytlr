<?php class Stuffpress_TinyMCE {

	public static function append($view)
	{
		$view->headScript()->appendFile('js/tiny_mce/tiny_mce.js');
		$view->headScript()->appendScript('tinyMCE.init({
													theme : "advanced",
													mode : "specific_textareas",
													editor_selector : "mceEditor",
													plugins : "emotions, inlinepopups",
													theme_advanced_buttons1 : "bold,italic,underline,separator,strikethrough,bullist,numlist,outdent, indent,undo,redo,link,unlink,image, charmap, code",
													theme_advanced_buttons2 : "",
													theme_advanced_buttons3 : "",
													theme_advanced_toolbar_location : "top",
													theme_advanced_toolbar_align : "left",
													extended_valid_elements : "div[id,class,style],iframe[src|width|height|frameborder],object[width|height|type|data],embed[src|type|width|height|wmode|flashvars], param[name|value], a[name|href|target|title|onclick],img[width|class|src|border=0|alt|title|hspace|vspace|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],span[class|align|style], p",
													cleanup: "true",
													convert_urls : false,
													debug : false
												});');	
	}
}