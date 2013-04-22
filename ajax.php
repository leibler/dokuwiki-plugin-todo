<?php
/*
** @brief This file is called by ajax if the user clicks on the todo checkbox or the todo text.
** It sets the todo state to completed or reset it to open.
** POST Parameters:
**   origVal	string the original todo text to find the corresponding todo in the file
**   checked	int should the todo set to completed (1) or to open (0)
**   path	string id/path/name of the page
**
** @date 20130405 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                replace old sack() method with new jQuery method and use post instead of get \n
** @date 20130407 Leo Eibler <dokuwiki@sprossenwanne.at> \n
**                add user assignment for todos \n
*/

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php'); //changelog.php=>addLogEntry(), io.php=>io_writeWikiPage(), pageinfo
require_once(DOKU_INC.'inc/auth.php');

#Variables  
if( isset($_POST['origVal']) ) {
	$origVal = urldecode($_POST["origVal"]);
} else
if( isset($_GET['origVal']) ) {
	$origVal = urldecode($_GET["origVal"]);
} else {
	die();
}
if( isset($_POST['checked']) ) {
	$checked = urldecode($_POST["checked"]);
} else
if( isset($_GET['checked']) ) {
	$checked = urldecode($_GET["checked"]);
} else {
	die();
}
if( isset($_POST['path']) ) {
	$path = urldecode($_POST["path"]);
} else
if( isset($_GET['path']) ) {
	$path = urldecode($_GET["path"]);
} else {
	die();
}

$ID = $path;
$INFO = pageinfo();
$fileName = $INFO["filepath"];

#Determine Permissions
$permission = auth_quickaclcheck($ID);

if($permission >= AUTH_EDIT) {
	#Retrieve File Contents
	$newContents =  file_get_contents($fileName);

	#Modify Contents
	$valuePos = strpos($newContents, $origVal);
	$todoPos = strrpos(substr($newContents, 0, $valuePos), "<todo");

	$contentChanged = false;

	#Determine position of Action, and adjust <todo> tag as necessary
	while($valuePos !== FALSE && $todoPos !== FALSE) {
		#Validation - Check to make sure the tag before this text is not a </todo> (it should be <todo...)
		if(strrpos(substr($newContents, 0, $valuePos), "</todo>") < $todoPos){
			$todoTag = substr( $newContents, $todoPos, $valuePos-$todoPos );
			// this should be the tag <todo .....>
				//file_put_contents( realpath(dirname(__FILE__)).'/debug.txt', "user='".$todo_user."'!\n", FILE_APPEND );
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
			$newContents = substr_replace($newContents, $newTag, $todoPos, ($valuePos - $todoPos));
			$contentChanged = true;    
		}

		$prevPos = $valuePos;
		$valuePos = strpos($newContents, $origVal, $prevPos+2);
		$todoPos = strrpos(substr($newContents, 0, $valuePos), "<todo");
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