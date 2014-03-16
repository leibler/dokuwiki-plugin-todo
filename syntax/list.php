<?php
/**
 * DokuWiki Plugin todo (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_todo_list
 */
class syntax_plugin_todo_list extends syntax_plugin_todo_todo {

    /**
     * @return string Syntax mode type
     */
    public function getType() {
        return 'substition';
    }

    /**
     * @return string Paragraph type
     */
    public function getPType() {
        return 'block';
    }

    /**
     * @return int Sort order - Low numbers go before high numbers
     */
    public function getSort() {
        return 250;
    }

    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~TODOLIST[^~]*~~', $mode, 'plugin_todo_list');
    }

    /**
     * Handle matches of the todo syntax
     *
     * @param string $match The match of the syntax
     * @param int $state The state of the handler
     * @param int $pos The position in the document
     * @param Doku_Handler $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, Doku_Handler &$handler) {

        $options = substr($match, 10, -2); // strip markup
        $options = explode(' ', $options);
        $data = array(
            'completed' => 'all',
            'assigned' => 'all',
            'pos' => $pos
        );
        $allowedvalues = array('yes', 'no');
        foreach($options as $option) {
            @list($key, $value) = explode(':', $option, 2);
            switch($key) {
                case 'completed':
                    if(in_array($value, $allowedvalues)) {
                        $data['completed'] = ($value == 'yes');
                    }
                    break;
                case 'assigned':
                    if(in_array($value, $allowedvalues)) {
                        $data['assigned'] = ($value == 'yes');
                        break;
                    }
                    //assigned?
                    $data['assigned'] = explode(',', $value);
                    $data['assigned'] = array_map(
                        function ($user) {
                            return ltrim($user, '@');
                        }, $data['assigned']
                    );
                    break;
            }
        }
        return $data;
    }

    /**
     * Render xhtml output or metadata
     *
     * @param string $mode Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer $renderer The renderer
     * @param array $data The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, Doku_Renderer &$renderer, $data) {
        global $conf;

        if($mode != 'xhtml') return false;
        /** @var Doku_Renderer_xhtml $renderer */

        $opts['pattern'] = '/<todo([^>]*)>(.*)<\/todo[\W]*?>/'; //all todos in a wiki page
        //TODO check if storing subpatterns doesn't cost too much resources

        // search(&$data, $base,            $func,                       $opts,$dir='',$lvl=1,$sort='natural')
        search($todopages, $conf['datadir'], array($this, 'search_todos'), $opts); //browse wiki pages with callback to search_pattern

        $todopages = $this->filterpages($todopages, $data);

        $this->htmlTodoTable($renderer, $todopages);

        return true;
    }

    /**
     * Custom search callback
     *
     * This function is called for every found file or
     * directory. When a directory is given to the function it has to
     * decide if this directory should be traversed (true) or not (false).
     * Return values for files are ignored
     *
     * All functions should check the ACL for document READ rights
     * namespaces (directories) are NOT checked (when sneaky_index is 0) as this
     * would break the recursion (You can have an nonreadable dir over a readable
     * one deeper nested) also make sure to check the file type (for example
     * in case of lockfiles).
     *
     * @param array &$data  - Reference to the result data structure
     * @param string $base  - Base usually $conf['datadir']
     * @param string $file  - current file or directory relative to $base
     * @param string $type  - Type either 'd' for directory or 'f' for file
     * @param int    $lvl   - Current recursion depht
     * @param array  $opts  - option array as given to search()
     * @return bool if this directory should be traversed (true) or not (false). Return values for files are ignored.
     */
    public function search_todos(&$data, $base, $file, $type, $lvl, $opts) {
        $item['id'] = pathID($file); //get current file ID

        //we do nothing with directories
        if($type == 'd') return true;

        //only search txt files
        if(substr($file, -4) != '.txt') return true;

        //check ACL
        if(auth_quickaclcheck($item['id']) < AUTH_READ) return false;

        $wikitext = rawWiki($item['id']); //get wiki text

        $item['count'] = preg_match_all($opts['pattern'], $wikitext, $matches); //count how many times appears the pattern
        if(!empty($item['count'])) { //if it appears at least once
            $item['matches'] = $matches;
            $data[] = $item;
        }
        return true;
    }

    /**
     * filter the pages
     *
     * @param $todopages array pages with all todoitems
     * @param $data      array listing parameters
     * @return array filtered pages
     */
    private function filterpages($todopages, $data) {
        $pages = array();
        foreach($todopages as $page) {
            $todos = array();
            // contains 3 arrays: an array with complete matches and 2 arrays with subpatterns
            foreach($page['matches'][1] as $todoindex => $todomatch) {
                list($checked, $todouser) = $this->parseTodoArgs($todomatch);
                $todotitle = trim($page['matches'][2][$todoindex]);

                if(
                    (
                        //completed?
                        $data['completed'] === 'all'
                        OR $data['completed'] === $checked //yes or no
                    ) AND (
                        //assigned?
                        $data['assigned'] === 'all'
                        OR (is_bool($data['assigned']) && $data['assigned'] == $todouser) //yes or no
                        OR (is_array($data['assigned']) && in_array($todouser, $data['assigned'])) //one of requested users?
                    )
                ) {
                    $todos[] = array($todotitle, $todoindex, $todouser, $checked);
                }
            }
            if(count($todos) > 0) {
                $pages[] = array('id' => $page['id'], 'todos' => $todos);
            }
        }
        return $pages;
    }

    /**
     * Create html for table with todos
     *
     * @param Doku_Renderer_xhtml $R
     * @param array $todopages
     */
    private function htmlTodoTable($R, $todopages) {
        $R->table_open();
        foreach($todopages as $page) {
            $R->tablerow_open();
            $R->tableheader_open();
            $R->internallink($page['id'], $page['id']);
            $R->tableheader_close();
            $R->tablerow_close();
            foreach($page['todos'] as $todo) {
                $R->tablerow_open();
                $R->tablecell_open();
                $R->doc .= $this->createTodoItem($R, $todo[0], $todo[1], $todo[2], $todo[3], $page['id']);
                $R->tablecell_close();
                $R->tablerow_close();
            }
        }
        $R->table_close();
    }
}
