<?php
$sPath = dirname(dirname(dirname(dirname(__FILE__))));

require_once($sPath.'/wp-config.php');
require_once(PP_THESAURUS_PLUGIN_DIR . '/classes/PPThesaurusManager.class.php');
require_once(PP_THESAURUS_PLUGIN_DIR . '/classes/PPThesaurusItem.class.php');
require_once(PP_THESAURUS_PLUGIN_DIR . '/classes/PPThesaurusPage.class.php');

$oPPTManager 	= PPThesaurusManager::getInstance();
$aConcepts		= $oPPTManager->searchConcepts($_GET['q'], 100, $oPPTManager->getItemLink());

echo join("\n", $aConcepts);
