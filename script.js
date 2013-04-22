/*
** @date 20130405 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                replace old sack() method with new jQuery method and use post instead of get - see https://www.dokuwiki.org/devel:jqueryfaq \n
** @date 20130407 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                use jQuery for finding the elements \n
*/
function whenCompleted( data ){
	//alert(data);
}

function clickSpan(span, id){
	//Find the checkbox node we need
	var chk;
	var preve = jQuery(span).prev();
	while( preve ) {
		if( preve.is("input") ) {
			chk = preve;
			break;
		}
		preve = preve.prev();
	}
	if( chk.is("input") ) {
		chk.attr('checked', !chk.attr('checked'));
		var strike;
		if( chk.attr('checked') ) {
			strike = true;
		} else {
			strike = false;
		}
		todo( chk, id, strike );
		//chk.checked = !chk.checked;
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

	chk = jQuery(chk);
	var inputTodohiddentext = chk.nextAll("span.todotext").children("input.todohiddentext").first();
	var spanTodoinnertext = chk.nextAll("span.todotext").children("span.todoinnertext").first();

	if( spanTodoinnertext && inputTodohiddentext ) {
		if( chk.attr('checked') ) {
			_postVarChecked = "1";
			spanTodoinnertext.html( "<del>"+decodeURIComponent( inputTodohiddentext.val().replace(/\+/g, " ") )+"</del>" );
		} else {
			_postVarChecked = "0";
			spanTodoinnertext.html( decodeURIComponent( inputTodohiddentext.val().replace(/\+/g, " ") ) );
			
		}
		jQuery.post(
			DOKU_BASE+'lib/plugins/todo/ajax.php',
			{ 
				"origVal": inputTodohiddentext.val().replace(/\+/g, " "), 
				"path": path,
				"checked": _postVarChecked
			},
			whenCompleted
		);
	} else {
		alert("Appropriate javascript element not found.\nReverting checkmark.");
	}

}
