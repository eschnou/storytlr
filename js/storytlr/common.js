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
 *  JAVASCRIPT DIALOGS
 */

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