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
 *  JAVASCRIPT FOR TIMELINE
 */
 
 function show_wait(source, item) {
	$('item_wait_' + source + '_' + item).show();
}

function hide_wait(source, item) {
	$('item_wait_' + source + '_' + item).hide();
}

function toggle_add_comment(source, item) {
	var element = 'add_comment_' + source + '_' + item;
	
	if (!$(element).visible()) {
		show_wait(source, item);
		new Ajax.Updater(element , 	'comments/form/source/' + source + '/item/' + item, 
						{ method: 'get' ,
						  onComplete: function(req) {hide_wait(source, item); $(element).show(); location.hash="#" + element;}
						});
	}
	else {
		$(element).hide();
	}
}

function toggle_show_item(source, item) {
	var element = 'item_' + source + '_' + item;
	var action  = 'toggle_show_item_a_' + source + '_' + item;
	var img		= 'toggle_show_item_img_' + source + '_' + item;

	if ($(element).hasClassName('hidden')) {
		$(element).removeClassName('hidden');	
		$(action).writeAttribute('title', 'Make private');
		$(img).writeAttribute('src', 'images/lock.gif');
		new Ajax.Request('timeline/show/', { 
						  method: 'post',
						  parameters: {source: source, item: item} 
						  });
	}
	else {
		$(element).addClassName('hidden');	
		$(action).writeAttribute('title', 'Make public');
		$(img).writeAttribute('src', 'images/lock_open.gif');
		new Ajax.Request('timeline/hide/', { 
						  method: 'post',
						  parameters: {source: source, item: item} 
						  });
	}
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

function submitFormAddComment(source, item) {
        form = new ValidateForm($('form_add_comment_' + source + '_' + item),"comments/add");
        form.errorElem = $('form_add_comment_errors_' + source + '_' + item);
        form.successCallback = function() {callbackAddCommentSuccess(source, item);};
        form.waitElem = $('item_wait_' + source + '_' + item);
        return form.submit();
}

function callbackAddCommentSuccess(source, item) {
	// Clear the form
	$('form_add_comment_' + source + '_' + item).reset();
	
	// Clear the errors
	$('form_add_comment_errors_' + source + '_' + item).update('');
	
	// Hide the form
	$('add_comment_' + source + '_' + item).hide();
	
	// Display the comments
	update_comments(source, item);
}

function cancelFormAddComment(source, item) {
	// Clear the form
	$('form_add_comment_' + source + '_' + item).reset();
	
	// Clear the errors
	$('form_add_comment_errors_' + source + '_' + item).update('');
	
	// Hide the form
	$('add_comment_' + source + '_' + item).hide();
}

function update_comments(source, item) {
		var comments = 'comments_' + source + '_' + item;
		show_wait(source, item);
		new Ajax.Request('comments/index/source/' + source + '/item/' + item + '/timestamp/' + new Date().getTime(), 
						{ method: 'get', 
						  onComplete: function(req) {hide_wait(source, item);},
						  onSuccess: function(req) {$(comments).update(req.responseText);$(comments).show();}
						});
}

function delete_comment(id, source, item) {
	new Ajax.Request('comments/delete', {
  					method: 'post',
  					parameters: {id: id}
  	});
 	
	$('comment_' + id).hide();
}

function delete_item(source, item) {
	new Ajax.Request('admin/post/delete', {
  					method: 'post',
  					parameters: {id: item}
  	});
  	
  	$( 'item_wrapper_' + source + '_' + item).hide();
}

function onLoginClick() {
	if ($('adminbar').visible()) {
		Effect.BlindUp('adminbar', { duration: 0.4} );
	}
	else {
		Effect.BlindDown('adminbar', { duration: 0.4}  );
	}
}

function onAddthisClick(url, title)
{
	var aturl ='http://www.addthis.com/bookmark.php';
	aturl+='?v=10';
	aturl+='&url='+encodeURIComponent(url);
	aturl+='&title='+encodeURIComponent(title);
	window.open(aturl,'addthis','scrollbars=yes,menubar=no,width=620,height=620,resizable=yes,toolbar=no,location=no,status=no,screenX=200,screenY=100,left=200,top=100');
} 