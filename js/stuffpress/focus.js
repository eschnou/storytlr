function setFocus(){
 var flag=false;
 for(z=0;z<document.forms.length;z++){
  var form = document.forms[z];
  var elements = form.elements;
  for (var i=0;i<elements.length;i++){
    var element = elements[i];
    if(element.type == 'text' &&
      !element.readOnly &&
      !element.disabled){
      element.focus();
	  flag=true;
     break;
    }
  }
  if(flag)break;
 }
}