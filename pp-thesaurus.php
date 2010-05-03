<?php
/*
Plugin Name: PoolParty Thesaurus
Plugin URI: http://www.punkt.at
Description: Instantly adds links to your posts and pages based on a thesaurus of definitions. Requires the <a href="htp://arc.semsol.org/">ARC2 Toolkit</a>
Version: 1.0
Author: Kurt Moser
Author URI: http://www.punkt.at/8/7/kurt-moser.htm
*/

/*  Copyright 2010  Kurt Moser  (email: moser@punkt.at)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/




/* defines */

define('PP_THESAURUS_PLUGIN_DIR', WP_PLUGIN_DIR . '/pp-thesaurus');


/* includes */

include_once(PP_THESAURUS_PLUGIN_DIR . '/classes/PPThesaurusManager.class.php');
include_once(PP_THESAURUS_PLUGIN_DIR . '/classes/PPThesaurusItem.class.php');



/* hooks */

add_option('PPThesaurusId', 	0);		//The ID of the main PoolParty Thesaurus page
add_action('admin_menu', 	'pp_thesaurus_settings_request');
add_filter('the_content', 	'pp_thesaurus_parse_content');
add_filter('the_content', 	'pp_thesaurus_show_list');



/* wp-admin */

function pp_thesaurus_settings_request () {
	add_options_page('PoolParty Thesaurus Settings', 'PoolParty Thesaurus', 10, __FILE__, 'pp_thesaurus_settings');
}

function pp_thesaurus_settings () {
	$sError = '';
	if (isset($_POST['secureToken']) && ($_POST['secureToken'] == pp_thesaurus_get_secure_token())) {
		pp_thesaurus_settigs_save();
		$sError = pp_thesaurus_import();
	}
	pp_thesaurus_settings_page ($sError);
}

function pp_thesaurus_settings_page ($sError='') {
	?>
	<div class="wrap">
		<h2>PoolParty Thesaurus Settings</h2>
		<form method="post" action="" enctype="multipart/form-data">
			<table class="form-table">
	<?php
	if (!empty($sError)) {
	?>
				<tr valign="top">
					<td colspan="2" style="color:red;"><?php echo $sError; ?></td>
				</tr>
	<?php
	}
	$sLanguage = get_option('PPThesaurusLanguage');
	if (empty($sLanguage) || $sLanguage == 'en') {
		$sLangEN 	= 'checked="checked" ';
	} else {
		$sLangOther = 'checked="checked" ';
		$sOther		= $sLanguage;
	}
	?>
				<tr valign="top">
					<th scope="row">ID of main PoolParty Thesaurus page</th>
					<td><input type="text" name="PPThesaurusId" value="<?php echo get_option('PPThesaurusId'); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Import SKOS/RDF File</th>
					<td><input type="file" name="rdfFile" value="" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Set the Thesaurus language</th>
					<td>
						<input type="radio" name="language" value="en" <?php echo $sLangEN; ?>/> en 
						<input type="radio" name="language" value="other" <?php echo $sLangOther; ?>/> other: 
						<input type="text" name="other" value="<?php echo $sOther; ?>" />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save data') ?>" />
			</p>
			<p>
				This plugin is provided by the <a href="http://poolparty.punkt.at/" target="_blank">PoolParty</a> Team.<br />
				PoolParty is an easy-to-use SKOS editor for the Semantic Web
			</p>
			<input type="hidden" name="secureToken" value="<?php echo pp_thesaurus_get_secure_token(); ?>" />
		</form>
	</div>
	<?php
}


function pp_thesaurus_settigs_save () {
	$sLanguage = $_POST['language'] == 'other' ? empty($_POST['other']) ? 'en' : $_POST['other'] : $_POST['language'];
	update_option('PPThesaurusLanguage', $sLanguage);
	update_option('PPThesaurusId', $_POST['PPThesaurusId']);

}

function pp_thesaurus_import () {
	// Load RDF-Data into ARC store
	$oPPGM = PPThesaurusManager::getInstance();
	$sError = '';
	try {
		$oPPGM->import();
	} catch (Exception $e) {
		$sError = $e->getMessage();
	}
	return $sError;
}

function pp_thesaurus_get_secure_token () {
	return substr(md5(DB_USER . DB_NAME), -10);
}






/* wp-content */

function pp_thesaurus_parse_content ($sContent) {
	$oPPGM = PPThesaurusManager::getInstance();
	return $oPPGM->parse($sContent);
}

function pp_thesaurus_show_list ($sContent) {
	$iPPThesaurusId = get_option('PPThesaurusId');
	if (!is_page($iPPThesaurusId)) {
		return $sContent;
	}

	$oPPGM = PPThesaurusManager::getInstance();
	if (!$oPPGM->exists()) {
		return $sContent;
	}

	$oPage = get_page($iPPThesaurusId);
	$aIndex = $oPPGM->getAbcIndex($_GET['filter']);
	$sContent .= '<ul class="PPThesaurusAbcIndex">';
	foreach($aIndex as $sChar => $sKind) {
		$sLetter = ($sChar == 'ALL') ? 'ALL' : chr($sChar);
		switch ($sKind) {
			case 'disabled':
				$sContent .= '<li>' . $sLetter . '</li>';
				break;

			case 'enabled':
				$sContent .= '<li><a href="' . $oPage->guid . '&amp;filter=' . $sChar . '">' . $sLetter . '</a></li>';
				break;

			case 'selected':
				$sContent .= '<li class="selected">' . $sLetter . '</li>';
				break;
		}
	}
	$sContent .= '</ul>';

	// generate the list of thesaurus items
	$aList = $oPPGM->getList($_GET['filter']);
	$sContent .= '<ul class="PPThesaurusList">';
	foreach($aList as $aItem) {
		$sLink = pp_thesaurus_link() . urlencode($aItem['label']);
		
		$sContent .= '<li><a href="' . $sLink . '">' . $aItem['label'] . '</a></li>';
	}
	$sContent .= '</ul>';

	return $sContent;
}

function pp_thesaurus_link () {
	$oTemplate = PPThesaurusManager::getTemplatePage();
	return $oTemplate->guid . '&amp;label=';
}

function pp_thesaurus_to_link ($aItemList) {
	if (empty($aItemList)) {
		return array();
	}

	$aLinks = array();
	foreach ($aItemList as $oItem) {
		$aLinks[] = '<a class="ppThesaurus" href="' . $oItem->link . '" title:"Thesaurus: ' . $oItem->prefLabel . '">' . $oItem->prefLabel . '</a>';
	}

	return $aLinks;
}
