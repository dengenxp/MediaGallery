
var popbackground = "#252525"; //specify backcolor or background image for pop window
var windowtitle = "Image Window";  //pop window title

function detectexist(obj) {
  return (typeof obj != "undefined");
}

function jkpopimage(imgpath, popwidth, popheight, textdescription) {

  function getpos() {
    leftpos = (detectexist(window.screenLeft)) ? screenLeft + document.body.clientWidth/2  - popwidth/2  : detectexist(window.screenX) ? screenX + innerWidth/2  - popwidth/2  : 0;
    toppos  = (detectexist(window.screenTop))  ? screenTop  + document.body.clientHeight/2 - popheight/2 : detectexist(window.screenY) ? screenY + innerHeight/2 - popheight/2 : 0;
    if (window.opera) {
      leftpos -= screenLeft;
      toppos -= screenTop;
    }
  }

  getpos();
  var winattributes = 'width=' + popwidth + ',height=' + popheight + ',resizable=yes,left=' + leftpos + ',top=' + toppos;
  var bodyattribute = (popbackground.indexOf(".") != -1) ? 'background="' + popbackground + '"' : 'bgcolor="' + popbackground + '"';
  if (typeof jkpopwin=="undefined" || jkpopwin.closed) {
    jkpopwin = window.open("","",winattributes);
  } else {
    //getpos(); //uncomment these 2 lines if you wish subsequent popups to be centered too
    //jkpopwin.moveTo(leftpos, toppos);
    jkpopwin.resizeTo(popwidth, popheight+30);
  }
  jkpopwin.document.open();
  jkpopwin.document.write('<html><title>'+windowtitle+'</title><body '+bodyattribute+'><img src="'+imgpath+'" style="margin-bottom: 0.5em"><br{xhtml}>'+textdescription+'</body></html>');
  jkpopwin.document.close();
  jkpopwin.focus();
}

function showVideo(URL, height, width) {
    day = new Date();
    id = day.getTime();
    eval("page" + id + " = window.open(URL, '" + id + "', 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=" + width + ",height=" + height + "');");
}
