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
 *  JAVASCRIPT FOR BOOKMARKLET
 */
 
 var txt;
 
if (window.getSelection) {
	txt = window.getSelection();
} else if (document.getSelection) {
    txt = document.getSelection();
} else if (document.selection) {
	txt = document.selection.createRange().text;
} else {
	txt = '';
}

window.open("http://storytlr.com/admin/post/?bookmarklet=true&v=1&s="+encodeURIComponent(txt)+"&u="+encodeURIComponent(location.href)+"&t="+encodeURIComponent(document.title),"storytlr","toolbar=no,resizable=no,status=no,location=no,directories=no,width=640,height=600");

void(0);
	
	
javascript:var%20b=document.body;var%20GR________bookmarklet_domain='http://www.google.com';if(b&&!document.xmlVersion){void(z=document.createElement('script'));void(z.src='http://www.google.com/reader/ui/link-bookmarklet.js');void(b.appendChild(z));}else{}