// javascript:var%20txt;if%20(window.getSelection)%20{txt%20=%20window.getSelection();}%20else%20if%20(document.getSelection)%20{txt%20=%20document.getSelection();}%20else%20if%20(document.selection)%20{txt%20=%20document.selection.createRange().text;}%20else%20{txt%20=%20%27%27;}window.open(%22http://storytlr.com/admin/post/?bookmarklet=true&v=1&s=%22+encodeURIComponent(txt)+%22&u=%22+encodeURIComponent(location.href)+%22&t=%22+encodeURIComponent(document.title),%22storytlr%22,%22toolbar=no,resizable=no,status=no,location=no,directories=no,width=640,height=600%22);void(0);
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