<?php
/**
 * ToDo Plugin: Creates a checkbox based todo list
 *
 * Syntax: <todo [@username] [#]>Name of Action</todo> -
 *  Creates a Checkbox with the "Name of Action" as 
 *  the text associated with it. The hash (#, optional)
 *  will cause the checkbox to be checked by default.
 *  The @ sign followed by a username can be used to assign this todo to a user.
 *  examples: 
 *     A todo without user assignment
 *       <todo>Something todo</todo>   
 *     A completed todo without user assignment
 *       <todo #>Completed todo</todo>   
 *     A todo assigned to user User
 *       <todo @leo>Something todo for Leo</todo>   
 *     A completed todo assigned to user User
 *       <todo @leo #>Todo completed for Leo</todo>   
 * 
 * In combination with dokuwiki searchpattern plugin version (at least v20130408),
 * it is a lightweight solution for a task management system based on dokuwiki.
 * use this searchpattern expression for open todos: 
 *     ~~SEARCHPATTERN#'/<todo[^#>]*>.*?<\/todo[\W]*?>/'?? _ToDo ??~~
 * use this searchpattern expression for completed todos: 
 *     ~~SEARCHPATTERN#'/<todo[^#>]*#[^>]*>.*?<\/todo[\W]*?>/'?? _ToDo ??~~
 * do not forget the no-cache option
 *     ~~NOCACHE~~
 *
 * Compatibility:
 *     Release 2013-03-06 "Weatherwax RC1"
 *     Release 2012-10-13 "Adora Belle"
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Babbage <babbage@digitalbrink.com>; Leo Eibler <dokuwiki@sprossenwanne.at>
 */

/**
 * ChangeLog:
 *
** [04/13/2013]: by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at
**               bugfix: config option Strikethrough
 * [04/11/2013]: by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at
 *               bugfix: encoding html code (security risk <todo><script>alert('hi')</script></todo>) - bug reported by Andreas
 *               bugfix: use correct <todo> tag if there are more than 1 in the same line.
 * [04/08/2013]: by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at
 *               migrate changes made by Christian Marg to current version of plugin
 * [04/08/2013]: by Christian Marg <marg@rz.tu-clausthal.de>
 *               changed behaviour - when multiple todo-items have the same text, only the clicked one is checked.
 * [04/08/2013]: by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at
 *               add description / comments and syntax howto about integration with searchpattern
 *               check compatibility with dokuwiki release 2012-10-13 "Adora Belle"
 *               remove getInfo() call because it's done by plugin.info.txt (since dokuwiki 2009-12-25 "Lemming")
 * [04/07/2013]: by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at
 *               add handler method _searchpatternHandler() for dokuwiki searchpattern extension.
 *               add user assignment for todos (with @username syntax in todo tag e.g. <todo @leo>do something</todo>)
 * [04/05/2013]: by Leo Eibler <dokuwiki@sprossenwanne.at> / http://www.eibler.at
 *               upgrade plugin to work with newest version of dokuwiki (tested version Release 2013-03-06 Weatherwax RC1).
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
	/*
    function getInfo(){
		// replaced by plugin.info.txt file
    }
	*/
 
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
			$x = preg_match( '%<todo([^>]*)>%i', $match, $pregmatches );
			if( $x ) {
				if( ($cPos = strpos( $pregmatches[1], '#' )) !== false ) {
					// ok, # means this is checked
					$handler->checked = true;
				}
				if( ($uPos = strpos( $pregmatches[1], '@' )) !== false ) {
					$match2 = substr( $match, $uPos );
					$x = preg_match( '%@([-.\w]+)%i', $match2, $pregmatches );
					if( $x ) {
						$handler->todo_user = $pregmatches[1];
					}
				}
			}
			if( !is_numeric($handler->todo_index) ) {
				$handler->todo_index = 0;
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
			if($this->getConf("Strikethrough") == true) {
				$Strikethrough = 1;
			} else {
				$Strikethrough = 0;
			}
            
            #If we are not displaying links, then the text should also check the checkbox
			if($this->getConf("CheckboxText") == true){
				if($this->getConf("AllowLinks") == true) {
					$span = "<span class=\"todotext\">";
				} else {
					$span = "<span class=\"todotext todohlght\" onclick=\"clickSpan(jQuery(this), '" . addslashes($ID) . "', ".$Strikethrough.")\">";
				}
			} else {
				$span = "<span class=\"todotext\">";
			}
            
            #Make sure there is actually an action to create
			if(trim($match) != ""){
				#Generate Beginning of Checkbox
				// by einhirn <marg@rz.tu-clausthal.de> determine checkbox index by using class 'todocheckbox'
				$begin = "<input type=\"checkbox\" class=\"todocheckbox\" data-index=\"".$handler->todo_index."\" onclick=\"todo(jQuery(this), '" . addslashes($ID) . "', ".$Strikethrough.")\" ".$checked." /> ";
				# a username was assigned to this task
				if( $handler->todo_user ) {
					$begin .= '<span class="todouser">['.htmlspecialchars($handler->todo_user).']</span>';
				}
				$begin .= $span;

				#Generate Hidden Field to Hold Original Title of Action
				$begin .= "<input class=\"todohiddentext\" type=\"hidden\" value=\"" . urlencode($match) . "\" />";

				#Generate Closing Tag
				$end = "</span>";

				$handler->todo_index++;
				#Return the information for renderer
				return array($state, array($begin, $match, $end, $handler->checked));
			}
			break;
          case DOKU_LEXER_EXIT :
            #Delete temporary checked variable
            unset($handler->todo_user);
            unset($handler->checked);
			//unset($handler->todo_index);
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
                  $text .= '<span class="todoinnertext"><del>'.htmlspecialchars($data[1][1]).'</del></span>';
                }else{
                  $text .= '<span class="todoinnertext">'.htmlspecialchars($data[1][1]).'</span>';
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
	
	/*
	** @brief this function can be called by dokuwiki plugin searchpattern to process the todos found by searchpattern.
	** use this searchpattern expression for open todos: ~~SEARCHPATTERN#'/<todo[^#>]*>.*?<\/todo[\W]*?>/'?? _ToDo ??~~
	** use this searchpattern expression for completed todos: ~~SEARCHPATTERN#'/<todo[^#>]*#[^>]*>.*?<\/todo[\W]*?>/'?? _ToDo ??~~
	** this handler method uses the table and layout with css classes from searchpattern plugin
	** @param $type	string type of the request from searchpattern plugin (wholeoutput, intable:whole, intable:prefix, intable:match, intable:count, intable:suffix)
	**             	wholeoutput     = all output is done by THIS plugin (no output will be done by search pattern)
	**             	intable:whole   = the left side of table (page name) is done by searchpattern, the right side of the table will be done by THIS plugin
	**             	intable:prefix  = on the right side of table - THIS plugin will output a prefix header and searchpattern will continue it's default output
	**             	intable:match   = if regex, right side of table - THIS plugin will format the current outputvalue ($value) and output it instead of searchpattern
	**             	intable:count   = if normal, right side of table - THIS plugin will format the current outputvalue ($value) and output it instead of searchpattern
	**             	intable:suffix  = on the right side of table - THIS plugin will output a suffix footer and searchpattern will continue it's default output
	** @param $renderer	object current rendering object (use $renderer->doc .= 'text' to output text)
	** @param $data	array whole data multidemensional array( array( $page => $countOfMatches ), ... )
	** @param $matches	array whole regex matches multidemensional array( array( 0 => '1st Match', 1 => '2nd Match', ... ), ... )
	** @param $page	string id of current page
	** @param $params	array the parameters set by searchpattern (see search pattern documentation)
	** @param $value	string value which should be outputted by searchpattern
	** @return bool true if THIS method is responsible for the output (using $renderer->doc) OR false if searchpattern should output it's default
	*/
	function _searchpatternHandler( $type, &$renderer, $data, $matches, $params=array(), $page=null, $value=null ) {
		if( $this->getConf("Strikethrough") == true ) {
			$Strikethrough = 1;
		} else {
			$Strikethrough = 0;
		}
		if( $this->getConf("AllowLinks") == true ) {
			$AllowLinks = 1;
		} else {
			$AllowLinks = 0;
		}

		$type = strtolower( $type );
		switch( $type ) {
			case 'wholeoutput':
				// matches should hold an array with all <todo>matches</todo> or <todo #>matches</todo>
				if( !is_array($matches) ) {
					return false;
				}
				//file_put_contents( dirname(__FILE__).'/debug.txt', print_r($matches,true), FILE_APPEND );
				//file_put_contents( dirname(__FILE__).'/debug.txt', print_r($params,true), FILE_APPEND );
				$renderer->doc .= '<div class="sp_main">';
				$renderer->doc .= '<table class="inline sp_main_table">';	//create table
				foreach( $matches as $page => $alltodos ) {
					$renderer->doc .= '<tr class="sp_title"><th class="sp_title" colspan="2"><a href="'.wl($page).'">'.$page.'</a></td></tr>';
					foreach( $alltodos as $k => $alltodos2 ) {
						foreach( $alltodos2 as $index => $matchtodo ) {
							$x = preg_match( '%<todo([^>]*)>(.*)</[\W]*todo[\W]*>%i', $matchtodo, $pregmatches );
							$checked = false;
							$todo_user = false;
							if( $x ) {
								if( strpos( $pregmatches[1], '#' ) !== false ) {
									// ok, # means this is checked
									$checked = true;
								}
								if( ($uPos = strpos( $pregmatches[1], '@' )) !== false ) {
									$match2 = substr( $pregmatches[1], $uPos );
									$x = preg_match( '%@([-.\w]+)%i', $match2, $pregusermatch );
									if( $x ) {
										$todo_user = $pregusermatch[1];
									}
								}
								$match = $pregmatches[2];
								$pregmatches[2] = trim($pregmatches[2]);
								if( empty($pregmatches[2]) ) {
									continue;
								}
								$renderer->doc .= '<tr class="sp_result"><td class="sp_page" colspan="2">';
								if( !empty($todo_user) ) {
									$span = '<span class="todouser">['.htmlspecialchars($todo_user).']</span>';
								} else {
									$span = '';
								}
								if($this->getConf("CheckboxText") == true){
									if($this->getConf("AllowLinks") == true) {
										$span .= '<span class="todotext">';
									} else {
										$span .= '<span class="todotext todohlght" onclick="clickSpan(jQuery(this), \''.addslashes($page).'\', '.$Strikethrough.')">';
									}
								} else {
									$span .= '<span class="todotext">';
								}
								// by einhirn <marg@rz.tu-clausthal.de> determine checkbox index by using class 'todocheckbox'
								// leo: but in case of integration with searchpattern there is no chance to find the element with the index
								// because after setting one element to completed the next call will have other index counts in the backend 
								// (1st call changed the backend file, in the frontend it's already loaded)
								// Possible solution:
								//   maybe we only should count the not checked checkboxes so this should be the same
								//   database like the backend file
								$begin = "<input type=\"checkbox\" class=\"todocheckbox\" onclick=\"todo(jQuery(this), '" . addslashes($page) . "', ".$Strikethrough.")\" ".( $checked ? 'checked="checked" ': '' )." /> ";
								$begin .= $span;
              
								#Generate Hidden Field to Hold Original Title of Action
								$begin .= "<input class=\"todohiddentext\" type=\"hidden\" value=\"" . urlencode($match) . "\" />";
								$renderer->doc .= $begin;
								if( $AllowLinks ) {
									#Should we allow Strikethrough or not
									if( $checked && $Strikethrough ){
										$renderer->doc .= $this->_createLink( $renderer, $match, '<del>'.$match.'</del>' );
									} else {
										$renderer->doc .= $this->_createLink( $renderer, $match, $match );
									}
								} else {
									#Should we allow Strikethrough or not
									if( $checked && $Strikethrough ){
										$renderer->doc .= '<span class="todoinnertext"><del>'.htmlspecialchars($match).'</del></span>';
									} else {
										$renderer->doc .= '<span class="todoinnertext">'.htmlspecialchars($match)."</span>";
									}      
								}
								$renderer->doc .= '</span> <br />';
								$renderer->doc .= '</td></tr>';
							}
						}
					}
				}
				$renderer->doc .= '</table>';	//end table
				$renderer->doc .= '</div>';
				// true means, that this handler method does the output (searchpattern plugin has nothing to do)
				return true;
				break;
			case 'intable:whole':
				break;
			case 'intable:prefix':
				//$renderer->doc .= '<b>Start on Page '.$page.'</b>';
				break;
			case 'intable:match':
				//$renderer->doc .= 'regex match on page '.$page.': <pre>'.$value.'</pre>';
				break;
			case 'intable:count':
				//$renderer->doc .= 'normal count on page '.$page.': <pre>'.$value.'</pre>';
				break;
			case 'intable:suffix':
				//$renderer->doc .= '<b>End on Page '.$page.'</b>';
				break;
			default:
				break;
		}
		// false means, that this handler method does not output anything. all should be done by searchpattern plugin
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