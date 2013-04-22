<?php

/**
 * ToDo Action Plugin: Inserts button for ToDo plugin into toolbar
 *
 * Original Example: http://www.dokuwiki.org/devel:action_plugins
 * @author     Babbage <babbage@digitalbrink.com>
 * @date 20130405 Leo Eibler <dokuwiki@sprossenwanne.at> \n
 *                replace old sack() method with new jQuery method and use post instead of get \n
 * @date 20130408 Leo Eibler <dokuwiki@sprossenwanne.at> \n
 *                remove getInfo() call because it's done by plugin.info.txt (since dokuwiki 2009-12-25 “Lemming”)
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');

class action_plugin_todo extends DokuWiki_Action_Plugin {

	/**
	 * Return some info
	 */
	 /*
	function getInfo() {
		// replaced by plugin.info.txt file
	}*/

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
			'block' => false,
		);
	}

}
