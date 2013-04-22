<?php
/**
 * ToDo Plugin: Creates a checkbox based todo list
 *
 * Syntax: <todo [#]>Name of Action</todo> -
 *  Creates a Checkbox with the "Name of Action" as 
 *  the text associated with it. The hash (#, optional)
 *  will cause the checkbox to be checked by default.    
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Babbage <babbage@digitalbrink.com>
 */

/**
 * ChangeLog:
 *
 * [08/16/2010]: Fixed another bug where javascript would not decode the action
 *               text properly (replaced unescape with decodeURIComponent).  
 * [04/03/2010]: Fixed a bug where javascript would not decode the action text
 *               properly.  
 * [03/31/2010]: Fixed a bug where checking or unchecking an action whose text
 *               appeared outside of the todo tags, would result in mangling the
 *               code on your page. Also added support for using the ampersand
 *               character (&) and html entities inside of your todo action.        
 * [02/27/2010]: Created an action plugin to insert a ToDo button into the
 *               editor toolbar.  
 * [10/14/2009]: Added the feature so that if you have Links turned off and you
 *               click on the text of an action, it will check that action off.
 *               Thanks to Tero for the suggestion! (Plugin Option: CheckboxText)
 * [10/08/2009]: I am no longer using the short open php tag (<?) for my 
 *               ajax.php file. This was causing some problems for people who had 
 *               short_open_tags=Off in their php.ini file (thanks Marcus!)                  
 * [10/01/2009]: Updated javascript to use .nextSibling instead of .nextElementSibling
 *               to make it compatible with older versions of Firefox and IE.  
 * [09/13/2009]: Replaced ':' with a '-' in the action link so as not to create 
 *               unnecessary namespaces (if the links option is active) 
 * [09/10/2009]: Removed unnecessary function calls (urlencode) in _createLink() function 
 * [09/09/2009]: Added ability for user to choose where Action links point to 
 * [08/30/2009]: Initial Release
 */  

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_todo extends DokuWiki_Syntax_Plugin {
 
   /**
    * Get an associative array with plugin info.
    *
    * <p>
    * The returned array holds the following fields:
    * <dl>
    * <dt>author</dt><dd>Author of the plugin</dd>
    * <dt>email</dt><dd>Email address to contact the author</dd>
    * <dt>date</dt><dd>Last modified date of the plugin in
    * <tt>YYYY-MM-DD</tt> format</dd>
    * <dt>name</dt><dd>Name of the plugin</dd>
    * <dt>desc</dt><dd>Short description of the plugin (Text only)</dd>
    * <dt>url</dt><dd>Website with more information on the plugin
    * (eg. syntax description)</dd>
    * </dl>
    * @param none
    * @return Array Information about this plugin class.
    * @public
    * @static
    */
    function getInfo(){
        return array(
            'author' => 'Babbage',
            'email'  => 'babbage@digitalbrink.com',
            'date'   => '2010-08-16',
            'name'   => 'ToDo',
            'desc'   => 'Create a checkbox based todo list',
            'url'    => 'http://www.dokuwiki.org/plugin:todo',
        );
    }
 
   /**
    * Get the type of syntax this plugin defines.
    *
    * @param none
    * @return String <tt>'substition'</tt> (i.e. 'substitution').
    * @public
    * @static
    */
    function getType(){
        return 'substition';
    }
 
    /**
     * What kind of syntax do we allow (optional)
     */
//    function getAllowedTypes() {
        //return array('formatting', 'substition', 'disabled');;
//    }
 
   /**
    * Define how this plugin is handled regarding paragraphs.
    *
    * <p>
    * This method is important for correct XHTML nesting. It returns
    * one of the following values:
    * </p>
    * <dl>
    * <dt>normal</dt><dd>The plugin can be used inside paragraphs.</dd>
    * <dt>block</dt><dd>Open paragraphs need to be closed before
    * plugin output.</dd>
    * <dt>stack</dt><dd>Special case: Plugin wraps other paragraphs.</dd>
    * </dl>
    * @param none
    * @return String <tt>'block'</tt>.
    * @public
    * @static
    */
//    function getPType(){
//        return 'normal';
//    }
 
   /**
    * Where to sort in?
    *
    * @param none
    * @return Integer <tt>6</tt>.
    * @public
    * @static
    */
    function getSort(){
        return 999;
    }
 
 
   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    */
    function connectTo($mode) {
      $this->Lexer->addEntryPattern('<todo[\s]*?.*?>(?=.*?</todo>)',$mode,'plugin_todo');
    }
 
    function postConnect() {
      $this->Lexer->addExitPattern('</todo>','plugin_todo');
    }
 
 
   /**
    * Handler to prepare matched data for the rendering process.
    *
    * <p>
    * The <tt>$aState</tt> parameter gives the type of pattern
    * which triggered the call to this method:
    * </p>
    * <dl>
    * <dt>DOKU_LEXER_ENTER</dt>
    * <dd>a pattern set by <tt>addEntryPattern()</tt></dd>
    * <dt>DOKU_LEXER_MATCHED</dt>
    * <dd>a pattern set by <tt>addPattern()</tt></dd>
    * <dt>DOKU_LEXER_EXIT</dt>
    * <dd> a pattern set by <tt>addExitPattern()</tt></dd>
    * <dt>DOKU_LEXER_SPECIAL</dt>
    * <dd>a pattern set by <tt>addSpecialPattern()</tt></dd>
    * <dt>DOKU_LEXER_UNMATCHED</dt>
    * <dd>ordinary text encountered within the plugin's syntax mode
    * which doesn't match any pattern.</dd>
    * </dl>
    * @param $aMatch String The text matched by the patterns.
    * @param $aState Integer The lexer state for the match.
    * @param $aPos Integer The character position of the matched text.
    * @param $aHandler Object Reference to the Doku_Handler object.
    * @return Integer The current lexer state for the match.
    * @public
    * @see render()
    * @static
    */
    function handle($match, $state, $pos, &$handler){
        global $ID;
        switch ($state) {
          case DOKU_LEXER_ENTER :
            #Search to see if the '#' is in the todo tag (if so, this means the Action has been completed)
            if(substr( trim( substr($match, 5) ), 0, 1) == '#'){
              #Hold on to the checked value by temporarily storing it in $handler
              $handler->checked = true;
            }
            break;
          case DOKU_LEXER_MATCHED :
            break;
          case DOKU_LEXER_UNMATCHED :
            /*
            ** Structure:
            ** input(checkbox)
            ** <span>
            ** -input(hidden)
            ** -<a> (if links is on) or <span> (if links is off)
            ** --<del> (if strikethrough is on) or --NOTHING--
            ** -</a> or </span>            
            ** </span>            
            */                                                            
          
            #Determine if the checkbox should be checked
            $checked = "";
            if($handler->checked){ $checked = "checked=\"checked\""; }
            
            #Determine if we should apply strikethrough
            if($this->getConf("Strikethrough") == true){
              $Strikethrough = 1;
            }else{
              $Strikethrough = 0;
            }
            
            #If we are not displaying links, then the text should also check the checkbox
            if($this->getConf("CheckboxText") == true){
              if($this->getConf("AllowLinks") == true){
                $span = "<span class=\"todotext\">";
              }else{
                $span = "<span class=\"todotext todohlght\" onClick=\"clickSpan(this, '" . addslashes($ID) . "')\">";
              }
            }else{
              $span = "<span class=\"todotext\">";
            }
            
            #Make sure there is actually an action to create
            if(trim($match) != ""){
              #Generate Beginning of Checkbox
              $begin = "<input type=\"checkbox\" onclick=\"todo(this, '" . addslashes($ID) . "', $Strikethrough)\" $checked /> ";//addslashes($INFO["filepath"]) . "')\" /> <span>";
              $begin .= $span;
              
              #Generate Hidden Field to Hold Original Title of Action
              $begin .= "<input type=\"hidden\" value=\"" . urlencode($match) . "\" />";
              
              #Generate Closing Tag
              $end = "</span>";
              
              #Return the information for renderer
              return array($state, array($begin, $match, $end, $handler->checked));
            }
            break;
          case DOKU_LEXER_EXIT :
            #Delete temporary checked variable
            unset($handler->checked);
            break;
          case DOKU_LEXER_SPECIAL :
            break;
        }
        return array();
    }
 
   /**
    * Handle the actual output creation.
    *
    * <p>
    * The method checks for the given <tt>$aFormat</tt> and returns
    * <tt>FALSE</tt> when a format isn't supported. <tt>$aRenderer</tt>
    * contains a reference to the renderer object which is currently
    * handling the rendering. The contents of <tt>$aData</tt> is the
    * return value of the <tt>handle()</tt> method.
    * </p>
    * @param $aFormat String The output format to generate.
    * @param $aRenderer Object A reference to the renderer object.
    * @param $aData Array The data created by the <tt>handle()</tt>
    * method.
    * @return Boolean <tt>TRUE</tt> if rendered successfully, or
    * <tt>FALSE</tt> otherwise.
    * @public
    * @see handle()
    */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
            if($data[0] == DOKU_LEXER_UNMATCHED){
              # $text variable will hold our output
              $text = $data[1][0];
              
              #Determine if we are to allow Actions to also show up as links 
              if($this->getConf("AllowLinks") == true){
                #Should we allow Strikethrough or not
                if($data[1][3] == true && $this->getConf("Strikethrough") == true){
                  $text .= $this->_createLink($renderer, $data[1][1], "<del>".$data[1][1]."</del>");
                }else{
                  $text .= $this->_createLink($renderer, $data[1][1], $data[1][1]);
                }                
              }else{
                #Should we allow Strikethrough or not
                if($data[1][3] == true && $this->getConf("Strikethrough") == true){
                  $text .= "<span><del>".$data[1][1]."</del></span>";
                }else{
                  $text .= "<span>" . $data[1][1] . "</span>";
                }      
              }
              $text .= $data[1][2];
               
              #Output our result
              $renderer->doc .= $text;   // ptype = 'normal'
              return true;
            }
        }
        return false;
    }
    
    /**
     * Generate links from our Actions if necessary.
     */         
    function _createLink(&$renderer, $url, $name = NULL){
      global $ID;
      
      #Determine URL
      $fullURL = "doku.php?id=";
      
      #Get the ActionNamespace and make sure it ends with a : (if not, add it)
      $actionNamespace = $this->getConf("ActionNamespace");
      if(strlen($actionNamespace) == 0 || substr($actionNamespace, -1) != ':'){
        $actionNamespace .= ":";
      }
      
      #Replace ':' in $url so we don't create unnecessary namespaces
      $url = str_replace(':', '-', $url);
      
      #Resolve what the fullURL should be to the file (fix any relative pages [".:"] $actionNamespace might have)
      $pageName = $actionNamespace . $url;
      $pageExists;
      resolve_pageid(getNS($ID), $pageName, $pageExists);      
      $fullURL .= $pageName;
           
      #Determine Class
      $class = 'wikilink1';
      if(!$pageExists){
        $class='wikilink2';
      }
      
      #Generate Link Structure
      $link['target'] = $conf['target']['wiki'];
      $link['style']  = '';
      $link['pre']    = '';
      $link['suf']    = '';
      $link['more']   = '';
      $link['class']  = $class;
      $link['url']    = $fullURL;
      $link['name']   = $name;
      $link['title']  = $renderer->_xmlEntities($url);
 
      #Return our final (formatted) link
      return $renderer->_formatLink($link);
    }
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>