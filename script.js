/*
** @date 20130405 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                replace old sack() method with new jQuery method and use post instead of get - see https://www.dokuwiki.org/devel:jqueryfaq \n
** @date 20130407 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                use jQuery for finding the elements \n
** @date 20130408 Christian Marg <marg@rz.tu-clausthal.de> \n
**                change only the clicked todo item instead of all items with the same text \n
** @date 20130408 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                migrate changes made by Christian Marg to current version of plugin (use jQuery) \n
** @date 20130410 by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at \n
**                bugfix: encoding html code (security risk <todo><script>alert('hi')</script></todo>) - bug reported by Andreas \n
** @date 20130413 Christian Marg <marg@rz.tu-clausthal.de> \n
**                bugfix: chk.attr('checked') returns checkbox state from html - use chk.is(':checked') - see http://www.unforastero.de/jquery/checkbox-angehakt.php \n
** @date 20130413 by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at \n
**                bugfix: config option Strikethrough \n
*/
function whenCompleted( data ){
	//alert(data);
}

/*
** @brief onclick method for span element
** @param span	the jQuery span element
** @param id	string the page
** @param strike	int strikethrough activated (1) or not (0) - see config option Strikethrough
*/
function clickSpan( span, id, strike ){
	//Find the checkbox node we need
	var chk;
	//var preve = jQuery(span).prev();
	var preve = span.prev();
	while( preve ) {
		if( preve.is("input") ) {
			chk = preve;
			break;
		}
		preve = preve.prev();
	}
	if( chk.is("input") ) {
		chk.attr('checked', !chk.is(':checked'));
		todo( chk, id, strike );
		//chk.checked = !chk.checked;
	} else {
		alert("Appropriate javascript element not found.");
	}

}

/*
** @brief onclick method for input element
** @param chk	the jQuery input element
** @param path	string the page
** @param strike	int strikethrough activated (1) or not (0) - see config option Strikethrough
*/
function todo( chk, path, strike ){

	/*
	** +Checkbox
	** +Span
	** -Hidden
	** -Span
	** --Anchor  
	** ---Del
	*/ 

	//chk = jQuery(chk);
	//console.log( "got chk with data-id='"+chk.attr("data-index")+"' chk.is(':checked')='"+chk.is(':checked')+"'" );
	var inputTodohiddentext = chk.nextAll("span.todotext").children("input.todohiddentext").first();
	var spanTodoinnertext = chk.nextAll("span.todotext").children("span.todoinnertext").first();
	var _postVarIndex;
	if( chk.attr("data-index") !== undefined ) {
		// if the data-index attribute is set, this is a call from the page where the todos are defined
		_postVarIndex = chk.attr("data-index");
	} else {
		// if the data-index attribute is not set, this is a call from searchpattern dokuwiki plugin rendered page
		_postVarIndex = -1;
	}
	if( spanTodoinnertext && inputTodohiddentext ) {
		//if( chk.attr('checked') ) {
		// @date 20130413 Christian bugfix chk.attr('checked') returns checkbox state from html - use chk.is(':checked') - see http://www.unforastero.de/jquery/checkbox-angehakt.php
		if( chk.is(':checked') ) {
			_postVarChecked = "1";
			if( strike ) {
				spanTodoinnertext.html( "<del>"+decodeURIComponent( inputTodohiddentext.val().replace(/\+/g, " ") ).replace(/</g, "&lt;").replace(/>/g, "&gt;")+"</del>" );
			}
		} else {
			_postVarChecked = "0";
			spanTodoinnertext.html( decodeURIComponent( inputTodohiddentext.val().replace(/\+/g, " ") ).replace(/</g, "&lt;").replace(/>/g, "&gt;") );
		}
		jQuery.post(
			DOKU_BASE+'lib/plugins/todo/ajax.php',
			{ 
				"index": _postVarIndex,
				"path": path,
				"checked": _postVarChecked,
				"origVal": inputTodohiddentext.val().replace(/\+/g, " ")
			},
			whenCompleted
		);
	} else {
		alert("Appropriate javascript element not found.\nReverting checkmark.");
	}

}
