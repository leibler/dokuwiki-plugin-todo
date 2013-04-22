var myAjax = new sack();

function whenCompleted(){
  if(myAjax.response){
    alert(myAjax.response);
  }

	/*if (myAjax.responseStatus){
		var string = "<p>Status Code: " + myAjax.responseStatus[0] + "</p><p>Status Message: " + myAjax.responseStatus[1] + "</p><p>URLString Sent: " + myAjax.URLString + "</p><p>Response: " + myAjax.response + "</p>";
	} else {
		var string = "<p>URLString Sent: " + myAjax.URLString + "</p>";
	}
	alert(string);*/
}

function clickSpan(span, id){
  //Find the checkbox node we need
  var chk;
  var preve = span.previousSibling;
  while(preve){
    if(preve.nodeType == 1){
      chk = preve;
      break;
    }
    preve = preve.previousSibling;
  }
  
  if(chk && chk.nodeName == "INPUT"){
    //Change the checkbox
    chk.checked = !chk.checked;

    //Do we require strikethrough
    var strike;
    if(chk.checked == true){
      strike = true;
    }else{
      strike = false;
    }

    //Call the todo function
    todo(chk, id, strike);
  }else{
	  alert("Appropriate javascript element not found.");
  }
}

function todo(chk, path, strike){

  /*
  ** +Checkbox
  ** +Span
  ** -Hidden
  ** -Span
  ** --Anchor  
  ** ---Del
  */          

  var span;
  var nexte = chk.nextSibling;
  
  //Find the SPAN node we need...
  while(nexte){
    if(nexte.nodeType == 1){
      span = nexte;
      break;
    }
    nexte = nexte.nextSibling;
  }

  //Verify we found the correct node
  if(span && span.nodeName == "SPAN"){
    if(chk.checked == true){
      //alert("true");
      if(strike == 1){
        span.lastChild.innerHTML = "<del>" + decodeURIComponent(span.firstChild.value.replace(/\+/g, " ")) + "</del>";
      }
      myAjax.setVar("checked", "1");
    }else{
      //alert("false");
      span.lastChild.innerHTML = decodeURIComponent(span.firstChild.value.replace(/\+/g, " "));
      myAjax.setVar("checked", "0");
    }
  
    //alert(path);
    myAjax.setVar("origVal", span.firstChild.value);
  	myAjax.setVar("path", path);
  	myAjax.requestFile = DOKU_BASE+'lib/plugins/todo/ajax.php';
  	myAjax.method = "GET";
  	myAjax.onCompletion = whenCompleted;
  	myAjax.runAJAX();
	}else{
	  alert("Appropriate javascript element not found.\nReverting checkmark.");
	  chk.checked = !chk.checked;
  }
}