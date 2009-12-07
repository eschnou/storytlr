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
 *  JAVASCRIPT FOR HIDE SHOW STORY
 */
 
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
