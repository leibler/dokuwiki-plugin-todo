<?php

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/common.php'); //changelog.php=>addLogEntry(), io.php=>io_writeWikiPage(), pageinfo
require_once(DOKU_INC.'inc/auth.php');

#Variables  
$origVal = urldecode($_GET["origVal"]);
$checked = $_GET["checked"];
$path = $_GET["path"];

$ID = $path;
$INFO = pageinfo();
$fileName = $INFO["filepath"];

#Determine Permissions
$permission = auth_quickaclcheck($ID);

if($permission >= AUTH_EDIT){
  #Retrieve File Contents
  $newContents =  file_get_contents($fileName);
  
  #Modify Contents
  $valuePos = strpos($newContents, $origVal);
  $todoPos = strrpos(substr($newContents, 0, $valuePos), "<todo");
  
  $contentChanged = false;

  #Determine position of Action, and adjust <todo> tag as necessary
  while($valuePos !== FALSE && $todoPos !== FALSE){
    #Validation - Check to make sure the tag before this text is not a </todo> (it should be <todo...)
    if(strrpos(substr($newContents, 0, $valuePos), "</todo>") < $todoPos){
      if($checked == 1){
        $newContents = substr_replace($newContents, "<todo #>", $todoPos, ($valuePos - $todoPos));
      }else{
        $newContents = substr_replace($newContents, "<todo>", $todoPos, ($valuePos - $todoPos));
      }
      $contentChanged = true;    
    }
    
    $prevPos = $valuePos;
    $valuePos = strpos($newContents, $origVal, $prevPos+2);
    $todoPos = strrpos(substr($newContents, 0, $valuePos), "<todo");
  }

  if($contentChanged){
    #Save Update (Minor)
    io_writeWikiPage($fileName, $newContents, $path, '');
    addLogEntry(saveOldRevision($path), $path, DOKU_CHANGE_TYPE_MINOR_EDIT, "Checkbox Change", '');
  }

}else{
  echo "You do not have permission to edit this file.\nAccess was denied.";
}

#(Possible) Alternative Method
//Retrieve mtime from file
//Load Data
//Modify Data
//Save Data
//Replace new mtime with previous one

?>