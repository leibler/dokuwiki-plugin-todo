/*
** @date 20130405 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                replace old sack() method with new jQuery method and use post instead of get - see https://www.dokuwiki.org/devel:jqueryfaq \n
*/
function whenCompleted( data ){
	//alert(data);
}

function clickSpan(span, id){
	//Find the checkbox node we need
	var chk;
	var preve = span.previousSibling;
	while(preve){
		if(preve.nodeType == 1) {
			chk = preve;
			break;
		}
		preve = preve.previousSibling;
	}

	if(chk && chk.nodeName == "INPUT") {
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
	} else {
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
		if(nexte.nodeType == 1) {
			span = nexte;
			break;
		}
		nexte = nexte.nextSibling;
	}

	//Verify we found the correct node
	var _postVarChecked;
	if(span && span.nodeName == "SPAN") {
		if(chk.checked == true){
			//alert("true");
			if(strike == 1){
				span.lastChild.innerHTML = "<del>" + decodeURIComponent(span.firstChild.value.replace(/\+/g, " ")) + "</del>";
			}
			_postVarChecked = "1";
		} else {
			//alert("false");
			span.lastChild.innerHTML = decodeURIComponent(span.firstChild.value.replace(/\+/g, " "));
			_postVarChecked = "0";
		}

		//alert(path);
		// by Leo <dokuwiki@sprossenwanne.at> replace sack() with jQuery.post
		jQuery.post(
			DOKU_BASE+'lib/plugins/todo/ajax.php',
			{ 
				"origVal": span.firstChild.value, 
				"path": path,
				"checked": _postVarChecked
			},
			whenCompleted
		);
	} else {
		alert("Appropriate javascript element not found.\nReverting checkmark.");
		chk.checked = !chk.checked;
	}
}
