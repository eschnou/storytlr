function calcHeight()
{
//find the height of the internal page
var the_height=
document.getElementById('inhoud').contentWindow.
document.body.scrollHeight;

//change the height of the iframe
document.getElementById('inhoud').height=
the_height + 5;
alert (the_height);
}