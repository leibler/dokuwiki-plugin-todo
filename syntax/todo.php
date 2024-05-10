<?php
/**
 * ToDo Plugin: Creates a checkbox based todo list
 *
 * Syntax: <todo [...options...] [#]>Name of Action</todo> -
 *  Creates a Checkbox with the "Name of Action" as
 *  the text associated with it. The hash (#, optional)
 *  will cause the checkbox to be checked by default.
 *  See https://www.dokuwiki.org/plugin:todo#usage_and_examples
 *   for possible options and examples.
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Babbage <babbage@digitalbrink.com>; Leo Eibler <dokuwiki@sprossenwanne.at>
 */

if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_todo_todo extends DokuWiki_Syntax_Plugin {

    const TODO_UNCHECK_ALL = '~~TODO:UNCHECKALL~~';

    /**
     * Get the type of syntax this plugin defines.
     *
     * @return String
     */
    public function getType() {
        return 'substition';
    }

    /**
     * Paragraph Type
     *
     * 'normal' - The plugin can be used inside paragraphs
     * 'block'  - Open paragraphs need to be closed before plugin output
     * 'stack'  - Special case. Plugin wraps other paragraphs.
     */
    function getPType(){
        return 'normal';
    }

    /**
     * Where to sort in?
     *
     * @return Integer
     */
    public function getSort() {
        return 999;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param $mode String The desired rendermode.
     * @return void
     * @see render()
     */
    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('<todo[\s]*?.*?>(?=.*?</todo>)', $mode, 'plugin_todo_todo');
        $this->Lexer->addSpecialPattern(self::TODO_UNCHECK_ALL, $mode, 'plugin_todo_todo');
        $this->Lexer->addSpecialPattern('~~NOTODO~~', $mode, 'plugin_todo_todo');
    }

    public function postConnect() {
        $this->Lexer->addExitPattern('</todo>', 'plugin_todo_todo');
    }

    /**
     * Handler to prepare matched data for the rendering process.
     *
     * @param $match    string  The text matched by the patterns.
     * @param $state    int     The lexer state for the match.
     * @param $pos      int     The character position of the matched text.
     * @param $handler Doku_Handler  Reference to the Doku_Handler object.
     * @return array  An empty array for most cases, except:
                        - DOKU_LEXER_EXIT:  An array containing the current lexer state
                                            and information about the just lexed todo. 
                        - DOKU_LEXER_SPECIAL:   For the special pattern of the Uncheck-All-Button, an
                                                array containing the current lexer state and the matched text.
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        switch($state) {
            case DOKU_LEXER_ENTER :
                #Search to see if the '#' is in the todotag (if so, this means the Action has been completed)
                $x = preg_match('%<todo([^>]*)>%i', $match, $tododata);
                if($x) {
                    $handler->todoargs =  $this->parseTodoArgs($tododata[1]);
                }
                if(!isset($handler->todo_index) || !is_numeric($handler->todo_index)) {
                    $handler->todo_index = 0;
                }
                $handler->todo_user = '';
                $handler->checked = '';
                $handler->todotitle = '';
                break;
            case DOKU_LEXER_MATCHED :
                break;
            case DOKU_LEXER_UNMATCHED :
                /**
                 * Structure:
                 * input(checkbox)
                 * <span>
                 * -<a> (if links is on) or <span> (if links is off)
                 * --<del> (if strikethrough is on) or --NOTHING--
                 * -</a> or </span>
                 * </span>
                 */
                $handler->todotitle = $match;
                break;
            case DOKU_LEXER_EXIT :
                $data = array_merge(array ($state, 'todotitle' => $handler->todotitle, 'todoindex' => $handler->todo_index, 'todouser' => $handler->todo_user, 'checked' => $handler->checked), $handler->todoargs);
                $handler->todo_index++;
                #Delete temporary checked variable
                unset($handler->todo_user);
                unset($handler->checked);
                unset($handler->todoargs);
                unset($handler->todotitle);
                return $data;
            case DOKU_LEXER_SPECIAL :
                if($match == self::TODO_UNCHECK_ALL) {
                    return array_merge(array($state, 'match' => $match));
                }
                break;
        }
        return array();
    }

    /**
     * Handle the actual output creation.
     *
     * @param  $mode     String        The output format to generate.
     * @param $renderer Doku_Renderer A reference to the renderer object.
     * @param  $data     Array         The data created by the <tt>handle()</tt> method.
     * @return Boolean true: if rendered successfully, or false: otherwise.
     */
    public function render($mode, Doku_Renderer $renderer, $data) {
        global $ID;

        if(empty($data)) {
            return false;
        }

        $state = $data[0];

        if($mode == 'xhtml') {
            /** @var $renderer Doku_Renderer_xhtml */
            switch($state) {
               case DOKU_LEXER_EXIT :
                    #Output our result
                    $renderer->doc .= $this->createTodoItem($renderer, $ID, array_merge($data, array('checkbox'=>'yes')));
                    return true;
                case DOKU_LEXER_SPECIAL :
                    if(isset($data['match']) && $data['match'] == self::TODO_UNCHECK_ALL) {
                        $renderer->doc .= '<button type="button" class="todouncheckall">Uncheck all todos</button>';
                    }
                    return true;
            }
        }
        return false;
    }

    /**
     * Parse the arguments of todotag
     *
     * @param string $todoargs
     * @return array(bool, false|string) with checked and user
     */
    protected function parseTodoArgs($todoargs) {
        $data['checked'] = false;
        unset($data['start']);
        unset($data['due']);
        unset($data['completeddate']);
        $data['showdate'] = $this->getConf("ShowdateTag");
        $data['username'] = $this->getConf("Username");
        $data['priority'] = 0;
        $options = explode(' ', $todoargs);
        foreach($options as $option) {
            $option = trim($option);
            if(empty($option)) continue;
            if($option[0] == '@') {
                $data['todousers'][] = substr($option, 1); //fill todousers array
                if(!isset($data['todouser'])) $data['todouser'] = substr($option, 1); //set the first/main todouser
            }
            elseif($option[0] == '#') {
                $data['checked'] = true;
                @list($completeduser, $completeddate) = explode(':', $option, 2);
                $data['completeduser'] = substr($completeduser, 1);
                if(date('Y-m-d', strtotime($completeddate)) == $completeddate) {
                    $data['completeddate'] = new DateTime($completeddate);
                }
            }
            elseif($option[0] == '!') {
                $plen = strlen($option);
                $excl_count = substr_count($option, "!");
                if (($plen == $excl_count) && ($excl_count >= 0)) {
                    $data['priority'] = $excl_count;
                }
            }
            else {
                @list($key, $value) = explode(':', $option, 2);
                switch($key) {
                    case 'username':
                        if(in_array($value, array('user', 'real', 'none'))) {
                            $data['username'] = $value;
                        }
                        else {
                            $data['username'] = 'none';
                        }
                        break;
                    case 'start':
                        if(date('Y-m-d', strtotime($value)) == $value) {
                            $data['start'] = new DateTime($value);
                        }
                        break;
                    case 'due':
                        if(date('Y-m-d', strtotime($value)) == $value) {
                            $data['due'] = new DateTime($value);
                        }
                        break;
                    case 'showdate':
                        if(in_array($value, array('yes', 'no'))) {
                            $data['showdate'] = ($value == 'yes');
                        }
                        break;
                }
            }
        }
        return $data;
    }

    /**
     * @param Doku_Renderer_xhtml $renderer
     * @param string $id of page
     * @param array  $data  data for rendering options
     * @return string html of an item
     */
    protected function createTodoItem($renderer, $id, $data) {
        //set correct context
        global $ID, $INFO;
        $oldID = $ID;
        $ID = $id;
        $todotitle = $data['todotitle'];
        $todoindex = $data['todoindex'];
        $checked = $data['checked'];
        $return = '<span class="todo">';

        if($data['checkbox']) {
            $return .= '<input type="checkbox" class="todocheckbox"'
            . ' data-index="' . $todoindex . '"'
            . ' data-date="' . hsc(@filemtime(wikiFN($ID))) . '"'
            . ' data-pageid="' . hsc($ID) . '"'
            . ' data-strikethrough="' . ($this->getConf("Strikethrough") ? '1' : '0') . '"'
            . ($checked ? ' checked="checked"' : '') . ' /> ';
        }

        // Username(s) of todouser(s)
        if (!isset($data['todousers'])) $data['todousers']=array();
        $todousers = array();
        foreach($data['todousers'] as $user) {
            if (($user = $this->_prepUsername($user,$data['username'])) != '') {
                $todousers[] = $user;
            }
        }
        $todouser=join(', ',$todousers);

        if($todouser!='') {
            $return .= '<span class="todouser">[' . hsc($todouser) . ']</span>';
        }
        if(isset($data['completeduser']) && ($checkeduser=$this->_prepUsername($data['completeduser'],$data['username']))!='') {
            $return .= '<span class="todouser">[' . hsc('✓ '.$checkeduser);
            if(isset($data['completeddate'])) { $return .= ', '.$data['completeddate']->format('Y-m-d'); }
            $return .= ']</span>';
        }

        // start/due date
        unset($bg);
        $now = new DateTime("now");
        if(!$checked && (isset($data['start']) || isset($data['due'])) && (!isset($data['start']) || $data['start']<$now) && (!isset($data['due']) || $now<$data['due'])) $bg='todostarted';
        if(!$checked && isset($data['due']) && $now>=$data['due']) $bg='tododue';

        // show start/due date
        if($data['showdate'] == 1 && (isset($data['start']) || isset($data['due']))) {
            $return .= '<span class="tododates">[';
            if(isset($data['start'])) { $return .= $data['start']->format('Y-m-d'); }
            $return .= ' → ';
            if(isset($data['due'])) { $return .= $data['due']->format('Y-m-d'); }
            $return .= ']</span>';
        }

        // priority
        $priorityclass = ''; 
        if (isset($data['priority'])) {
            $priority = $data['priority'];
            if ($priority == 1) $priorityclass = ' todolow';
            else if ($priority == 2) $priorityclass = ' todomedium';
            else if ($priority >= 3) $priorityclass = ' todohigh';
        }

        $spanclass = 'todotext' . $priorityclass;
        if($this->getConf("CheckboxText") && !$this->getConf("AllowLinks") && $oldID == $ID && $data['checkbox']) {
            $spanclass .= ' clickabletodo todohlght';
        }
        if(isset($bg)) $spanclass .= ' '.$bg;
        $return .= '<span class="' . $spanclass . '">';

        if($checked && $this->getConf("Strikethrough")) {
            $return .= '<del>';
        }
        $return .= '<span class="todoinnertext">';
        if($this->getConf("AllowLinks")) {
            $return .= $this->_createLink($renderer, $todotitle, $todotitle);
        } else {
            if ($oldID != $ID) {
                $return .= $renderer->internallink($id, $todotitle, null, true);
            } else {
                 $return .= hsc($todotitle);
            }
        }
        $return .= '</span>';

        if($checked && $this->getConf("Strikethrough")) {
            $return .= '</del>';
        }

        $return .= '</span></span>';

        //restore page ID
        $ID = $oldID;
        return $return;
    }

    /**
     * Prepare user name string.
     *
     * @param string $username
     * @param string $displaytype - one of 'user', 'real', 'none'
     * @return string
     */
    private function _prepUsername($username, $displaytype) {

        switch ($displaytype) {
            case "real":
                global $auth;
                $username = $auth->getUserData($username)['name'];
                break;
            case "none":
                $username="";
                break;
            case "user":
            default:
                break;
        }

        return $username;
    }

     /**
     * Generate links from our Actions if necessary.
     *
     * @param Doku_Renderer_xhtml $renderer
     * @param string $pagename
     * @param string $name
     * @return string
     */
    private function _createLink($renderer, $pagename, $name = NULL) {
        $id = $this->_composePageid($pagename);

        return $renderer->internallink($id, $name, null, true);
    }

    /**
     * Compose the pageid of the pages linked by a todoitem
     *
     * @param string $pagename
     * @return string page id
     */
    private function _composePageid($pagename) {
        #Get the ActionNamespace and make sure it ends with a : (if not, add it)
        $actionNamespace = $this->getConf("ActionNamespace");
        if(strlen($actionNamespace) == 0 || substr($actionNamespace, -1) != ':') {
            $actionNamespace .= ":";
        }

        #Replace ':' in $pagename so we don't create unnecessary namespaces
        $pagename = str_replace(':', '-', $pagename);

        //resolve and build link
        $id = $actionNamespace . $pagename;
        return $id;
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :
