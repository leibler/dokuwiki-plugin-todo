<?php
/*
** @brief This file is called by ajax if the user clicks on the todo checkbox or the todo text.
** It sets the todo state to completed or reset it to open.
** POST Parameters:
**   index	int the position of the occurrence of the input element (starting with 0 for first element/todo)
**   checked	int should the todo set to completed (1) or to open (0)
**   path	string id/path/name of the page
**
** @date 20130405 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                replace old sack() method with new jQuery method and use post instead of get \n
** @date 20130407 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                add user assignment for todos \n
** @date 20130408 Christian Marg <marg@rz.tu-clausthal.de> \n
**                change only the clicked todo item instead of all items with the same text \n
**                origVal is not used anymore, we use the index (occurrence) of input element \n
** @date 20130408 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                migrate changes made by Christian Marg to current version of plugin \n
*/

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php'); //changelog.php=>addLogEntry(), io.php=>io_writeWikiPage(), pageinfo
require_once(DOKU_INC.'inc/auth.php');

if( !function_exists( 'strnpos' ) ) {
	function strnpos($haystack, $needle, $occurance, $pos = 0) {
			for ($i = 1; $i <= $occurance; $i++) {
				$pos = strpos($haystack, $needle, $pos) + 1;
			}
			return $pos - 1;
	}
}

#Variables  
// by einhirn <marg@rz.tu-clausthal.de> determine checkbox index by using class 'todocheckbox'
// index = position of occurrence of <input> element (starting with 0 for first element)
if( isset($_POST['index']) ) {
	$index = urldecode($_POST["index"]);
} else
if( isset($_GET['index']) ) {
	$index = urldecode($_GET["index"]);
} else {
	die();
}
// checked = flag if input is checked means todo is complete (1) or not (0)
if( isset($_POST['checked']) ) {
	$checked = urldecode($_POST["checked"]);
} else
if( isset($_GET['checked']) ) {
	$checked = urldecode($_GET["checked"]);
} else {
	die();
}
// path = page ID (name)
if( isset($_POST['path']) ) {
	$path = urldecode($_POST["path"]);
} else
if( isset($_GET['path']) ) {
	$path = urldecode($_GET["path"]);
} else {
	die();
}
// origVal = urlencoded original value (in the case this is called by dokuwiki searchpattern plugin rendered page)
if( isset($_POST['origVal']) ) {
	$origVal = urldecode($_POST["origVal"]);
} else
if( isset($_GET['origVal']) ) {
	$origVal = urldecode($_GET["origVal"]);
} else {
	$origVal = '';
}

$ID = $path;
$INFO = pageinfo();
$fileName = $INFO["filepath"];

#Determine Permissions
$permission = auth_quickaclcheck($ID);

/*
** @brief gets current todo tag and returns a new one depending on checked
** @param $todoTag	string current todo tag e.g. <todo @user>
** @param $checked	int check flag (todo completed=1, todo uncompleted=0)
** @return string new todo completed or uncompleted tag e.g. <todo @user #>
*/
function _todoProcessTag( $todoTag, $checked ) {
	$x = preg_match( '%<todo([^>]*)>%i', $todoTag, $pregmatches );
	$todo_user = '';
	$newTag = '<todo';
	if( $x ) {
		if( ($uPos = strpos( $pregmatches[1], '@' )) !== false ) {
			$match2 = substr( $todoTag, $uPos );
			$x = preg_match( '%@([-.\w]+)%i', $match2, $pregmatches );
			if( $x ) {
				$todo_user = $pregmatches[1];
				$newTag .= ' @'.$todo_user;
			}
		}
	}
	if($checked == 1) {
		$newTag .= ' #';
	}
	$newTag .= '>';
	return $newTag;
}

/*
** @brief Convert a string to a regex so it can be used in PHP "preg_match" function
** from dokuwiki searchpattern plugin
*/
function _todoStr2regex($str){
	$regex = '';	//init
	for($i = 0; $i < strlen($str); $i++){	//for each char in the string
		if(!ctype_alnum($str[$i])){	//if char is not alpha-numeric
			$regex = $regex.'\\';	//escape it with a backslash
		}
		$regex = $regex.$str[$i];	//compose regex
	}
	return $regex;	//return
}

if($permission >= AUTH_EDIT) {
	#Retrieve File Contents
	$newContents =  file_get_contents($fileName);

	$contentChanged = false;
	#Modify Contents
	
	if( $index >= 0 ) {
		$index++;
		// no origVal so we count all todos with the method from Christian Marg 
		// this will happen if we are on the current page with the todos
		$todoPos = strnpos($newContents, '<todo', $index);
		$todoTextPost = strpos( $newContents, '>', $todoPos )+1;
		if( $todoTextPost > $todoPos ) {
			$todoTag = substr( $newContents, $todoPos, $todoTextPost-$todoPos );
			$newTag = _todoProcessTag( $todoTag, $checked );
			$newContents = substr_replace($newContents, $newTag, $todoPos, ($todoTextPost - $todoPos));
			$contentChanged = true;
		}
	} else {
		// this will happen if we are on a dokuwiki searchpattern plugin summary page
		if( $checked ) {
			$pattern = '/(<todo[^#>]*>('._todoStr2regex($origVal).'<\/todo[\W]*?>))/';
		} else {
			$pattern = '/(<todo[^#>]*#[^>]*>('._todoStr2regex($origVal).'<\/todo[\W]*?>))/';
		}
		$x = preg_match_all( $pattern, $newContents, $spMatches, PREG_OFFSET_CAPTURE );
		if( $x && isset($spMatches[0][0]) ) {
			// yes, we found matches and index is in a valid range
			$todoPos = $spMatches[1][0][1];
			$todoTextPost = $spMatches[2][0][1];
			$todoTag = substr( $newContents, $todoPos, $todoTextPost-$todoPos );
			$newTag = _todoProcessTag( $todoTag, $checked );
			$newContents = substr_replace($newContents, $newTag, $todoPos, ($todoTextPost - $todoPos));
			$contentChanged = true;
		}
	}

	if($contentChanged) {
		#Save Update (Minor)
		io_writeWikiPage($fileName, $newContents, $path, '');
		addLogEntry(saveOldRevision($path), $path, DOKU_CHANGE_TYPE_MINOR_EDIT, "Checkbox Change", '');
	}

} else {
	echo "You do not have permission to edit this file.\nAccess was denied.";
}

#(Possible) Alternative Method
//Retrieve mtime from file
//Load Data
//Modify Data
//Save Data
//Replace new mtime with previous one

?>