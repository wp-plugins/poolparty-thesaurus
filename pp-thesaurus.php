<?php
/**
 * The WordPress Plugin PoolParty Thesaurus
 *
 * PoolParty Thesaurus plugin makes websites more understandable. Blogs benefit from linking posts with key terms automatically. The plugin uses SKOS vocabularies.
 *
 * Plugin Name: PoolParty Thesaurus
 * Plugin URI: http://poolparty.biz
 * Description: This plugin imports a SKOS thesaurus via <a href="https://github.com/semsol/arc2">ARC2</a>. It highlighs terms and generates links automatically in any page which contains terms from the thesaurus.
 * Version: 2.5.0
 * Author: Kurt Moser
 * Author URI: http://www.semantic-web.at/users/kurt-moser
 * Text Domain: pp-thesaurus
 * Domain Path: /languages
 */



/*	Copyright 2010-2011  Kurt Moser  (email: k.moser@semantic-web.at)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


defined('ABSPATH') or die('No script kiddies please!');


/*
 * Defines
 */
define('PP_THESAURUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PP_THESAURUS_ARC_URL', 'https://github.com/semsol/arc2/tarball/master');

/*
 * Include files
 */
require_once(PP_THESAURUS_PLUGIN_DIR . 'classes/PPThesaurus.class.php');
require_once(PP_THESAURUS_PLUGIN_DIR . 'classes/PPThesaurusManager.class.php');
require_once(PP_THESAURUS_PLUGIN_DIR . 'classes/PPThesaurusPage.class.php');
require_once(PP_THESAURUS_PLUGIN_DIR . 'classes/PPThesaurusItem.class.php');
require_once(PP_THESAURUS_PLUGIN_DIR . 'classes/PPThesaurusTemplate.class.php');
require_once(PP_THESAURUS_PLUGIN_DIR . 'classes/PPThesaurus.widget.class.php');
require_once(PP_THESAURUS_PLUGIN_DIR . 'classes/simple_html_dom.php');


/*
 * Register hooks that are fired when the plugin is activated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook( __FILE__, array( 'PPThesaurus', 'activate' ) );


/*
 * Load the plugin and widget
 */
add_action('plugins_loaded', array('PPThesaurus', 'getInstance'));
add_action('widgets_init', array('PPThesaurus', 'registerWidget') );

