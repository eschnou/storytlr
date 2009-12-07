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
 *  JAVASCRIPT FOR STORIES
 */
 
 function toggle_show_item(story) {
	var element = 'item_' + story;
	var action  = 'toggle_show_item_a_' + story;
	var img		= 'toggle_show_item_img_' + story;
	var share	= 'share_actions_' + story;
	
	if ($(element).hasClassName('hidden')) {
		$(element).removeClassName('hidden');	
		$(action).writeAttribute('title', 'Make private');
		$(img).writeAttribute('src', 'images/lock.gif');
		$(share).show();
		new Ajax.Request('admin/story/setPublic/', { 
						  method: 'post',
						  parameters: {story: story} 
						  });
	}
	else {
		$(element).addClassName('hidden');	
		$(action).writeAttribute('title', 'Make public');
		$(img).writeAttribute('src', 'images/lock_open.gif');
		$(share).hide();
		new Ajax.Request('admin/story/setPrivate/', { 
						  method: 'post',
						  parameters: {story: story} 
						  });
	}
}

function toggle_show_embed(story) {
	var element = 'story_embed_' + story;
	$(element).toggle();
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

function delete_item(story) {
	new Ajax.Request('admin/story/delete', {
  					method: 'post',
  					parameters: {id: story}
  	});
  	
  	$( 'item_wrapper_' + story).hide();
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
	aturl+='&pub='+'alardw';
	aturl+='&url='+encodeURIComponent(url);
	aturl+='&title='+encodeURIComponent(title);
	window.open(aturl,'addthis','scrollbars=yes,menubar=no,width=620,height=620,resizable=yes,toolbar=no,location=no,status=no,screenX=200,screenY=100,left=200,top=100');
} 