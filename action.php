<?php

/**
 * ToDo Action Plugin: Inserts button for ToDo plugin into toolbar
 *
 * Original Example: http://www.dokuwiki.org/devel:action_plugins
 * @author     Babbage <babbage@digitalbrink.com>
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');

class action_plugin_todo extends DokuWiki_Action_Plugin {

	/**
	 * Return some info
	 */
	function getInfo() {
		return array (
			'author' => 'Babbage',
			'email' => 'babbage@digitalbrink.com',
			'date' => '2010-02-27',
			'name' => 'ToDo Action Plugin',
			'desc' => 'Inserts a ToDo button into the editor toolbar',
			'url' => 'http://www.dokuwiki.org/plugin:todo',
			
		);
	}

	/**
	 * Register the eventhandlers
	 */
	function register(&$controller) {
		$controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array ());
	}
	
	/**
	 * Inserts the toolbar button
	 */
	function insert_button(&$event, $param) {
		$event->data[] = array(	
			'type'   => 'format',
			'title'  => $this->getLang('qb_todobutton'),
			'icon'   => '../../plugins/todo/todo.png',
			'key'    => 't',
			'open'   => '<todo>',
			'close'  => '</todo>',
		);
	}

}
