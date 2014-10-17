<?php

if(!defined('WP_UNINSTALL_PLUGIN')){
  die(__('You are not allowed to call this page directly.'));
}



define('PP_THESAURUS_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once(PP_THESAURUS_PLUGIN_DIR . 'classes/PPThesaurus.class.php');
require_once(PP_THESAURUS_PLUGIN_DIR . 'classes/PPThesaurusManager.class.php');


// Needed for loading the ARC2 library
$oPPThesaurus = PPThesaurus::getInstance();

// Remove options and glossary pages on plugin delete
if (function_exists('is_multisite') && is_multisite()) {
	$blogIds = PPThesaurus::getBlogIds();
	foreach ($blogIds as $blogId) {
		switch_to_blog($blogId);
		delete_glossary_pages();
		delete_options();
		delete_triple_store();
	}
} else {
	delete_glossary_pages();
	delete_options();
	delete_triple_store();
}


/*
 * Deletes the main glossary page and its sub pages.
 */
function delete_glossary_pages() {
	$pageId = get_option('PPThesaurusId');

	// Delete glossary pages if it is set
	if ($pageId > 0) {
		// Get and delete all sub pages
		$subPages = get_pages(array('child_of' => $pageId));
		foreach ($subPages as $subPage) {
			wp_delete_post($subPage->ID, TRUE);
		}

		// Delete the main glossary page
		wp_delete_post($pageId, TRUE);
	}
}

/*
 * Deletes all options for this plugin
 */
function delete_options() {
	delete_option('PPThesaurusId'); // The ID of the main glossary page
	delete_option('PPThesaurusPopup'); // Show/hide the tooltip
	delete_option('PPThesaurusLanguage'); // Enabled languages for the thesaurus
	delete_option('PPThesaurusDBPediaEndpoint'); // The DBPedia SPARQL endpoint
	delete_option('PPThesaurusSparqlEndpoint'); // The thesaurus SPARQL endpoint for import/update it 
	delete_option('PPThesaurusImportFile'); // The file name from the with the thesaurus data
	delete_option('PPThesaurusUpdated'); // The last import/update date 
	delete_option('PPThesaurusSidebarTitle'); // The title for the sidebar widget
	delete_option('PPThesaurusSidebarInfo'); // The info text for the sidebar widget
	delete_option('PPThesaurusSidebarWidth'); // The width for the input field in the sidebar widget
}

function delete_triple_store() {
	PPThesaurusManager::dropStore();
}
