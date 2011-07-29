<?php
/*
Plugin Name: PoolParty Thesaurus
Plugin URI: http://poolparty.punkt.at
Description: This plugin imports a SKOS thesaurus via <a href="https://github.com/semsol/arc2">ARC2</a>. It highlighs terms and generates links automatically in any page which contains terms from the thesaurus.
Version: 2.0
Author: Kurt Moser
Author URI: http://www.punkt.at/8/7/kurt-moser.htm
Text Domain: pp-thesaurus
Domain Path: /languages
*/

/*  
	Copyright 2010-2011  Kurt Moser  (email: moser@punkt.at)

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

define('PP_THESAURUS_PLUGIN_DIR', dirname(__FILE__));


/* includes */

include_once(PP_THESAURUS_PLUGIN_DIR . '/classes/PPThesaurusManager.class.php');
include_once(PP_THESAURUS_PLUGIN_DIR . '/classes/PPThesaurusItem.class.php');
include_once(PP_THESAURUS_PLUGIN_DIR . '/classes/PPThesaurusTemplate.class.php');


if (class_exists('PPThesaurusTemplate')) {
	$oPPTManager 	= PPThesaurusManager::getInstance();
	$oPPTTemplate 	= new PPThesaurusTemplate();

	/* add options */
	add_option('PPThesaurusId', 				0);									// The ID of the main glossary page
	add_option('PPThesaurusPopup', 				true);								// Show/hide the tooltip
	add_option('PPThesaurusLanguage', 			'en');								// Enabled languages for the thesaurus
	add_option('PPThesaurusDBPediaEndpoint', 	'http://dbpedia.org/sparql');		// The DBPedia SPARQL endpoint
	add_option('PPThesaurusSparqlEndpoint', 	'');								// The thesaurus SPARQL endpoint for import/update it 
	add_option('PPThesaurusImportFile', 		'');								// The file name from the with the thesaurus data
	add_option('PPThesaurusUpdated', 			'');								// The last import/update date 

	/* hooks */
	add_action('init',			'pp_thesaurus_init');
	add_action('admin_menu', 	'pp_thesaurus_settings_request');
	add_filter('the_content', 	array($oPPTManager, 'parse'));
	add_filter('the_title', 	array($oPPTTemplate, 'setTitle'));
	add_filter('wp_title', 		array($oPPTTemplate, 'setTitle'));

	/* register shortcode */
	add_shortcode('ppt-abcindex', 		array($oPPTTemplate, 'showABCIndex'));
	add_shortcode('ppt-itemlist', 		array($oPPTTemplate, 'showItemList'));
	add_shortcode('ppt-itemdetails', 	array($oPPTTemplate, 'showItemDetails'));
}


/* load Javascript file into header */

function pp_thesaurus_init () {
	if(defined('PP_THESAURUS_INIT')) return;
	define('PP_THESAURUS_INIT',true);

	if (!is_admin() && get_option('PPThesaurusPopup')) {	// instruction to only load if it is not the admin area
		wp_enqueue_script('jquery');
		wp_enqueue_script('unitip_script', plugins_url('/js/unitip.js', __FILE__), array('jquery'));
		wp_enqueue_style('unitip_style', plugins_url('/js/unitip/unitip.css', __FILE__));
		wp_enqueue_style('ppthesaurus_style', plugins_url('/css/style.css', __FILE__));
	}
	// load plugin translations
	load_plugin_textdomain('pp-thesaurus', false, dirname(plugin_basename( __FILE__ )) . '/languages');
}


/* wp-admin */
$pp_thesaurus_updated = false;

function pp_thesaurus_settings_request () {
	add_options_page('PoolParty Thesaurus Settings', 'PoolParty Thesaurus', 10, __FILE__, 'pp_thesaurus_settings');
}

function pp_thesaurus_settings () {
	$sError = '';
	if (isset($_POST['secureToken']) && ($_POST['secureToken'] == pp_thesaurus_get_secure_token())) {
		$sError = pp_thesaurus_settings_save();
	}
	pp_thesaurus_settings_page($sError);
}


function pp_thesaurus_settings_page ($sError='') {
	global $pp_thesaurus_updated;

	$bPopup 			= get_option('PPThesaurusPopup');
	$sImportFile 		= get_option('PPThesaurusImportFile');
	$sSparqlEndpoint 	= get_option('PPThesaurusSparqlEndpoint');
	$sDBPediaEndpoint 	= get_option('PPThesaurusDBPediaEndpoint');
	$sUpdated 			= get_option('PPThesaurusUpdated');
	if ($bPopup) {
		$sPopupTrue = 'checked="checked" ';
	} else {
		$sPopupFalse = 'checked="checked" ';
	}
	$sDate = empty($sUpdated) ? 'undefined' : date('d.m.Y', $sUpdated);
	$sFrom = empty($sImportFile) ? empty($sSparqlEndpoint) ? 'undefined' : 'SPARQL endpoint' : $sImportFile;
	
	$oPPTM = PPThesaurusManager::getInstance();

	?>
	<div class="wrap">
		<h2><?php _e('PoolParty Thesaurus Settings', 'pp-thesaurus'); ?></h2>
	<?php
	if (!empty($sError)) {
		echo '<p style="color:red;"><strong>' . $sError . '</strong></p>';
	} elseif (isset($_POST['secureToken']) && empty($sError)) {
		echo '<p style="color:green;"><strong>' . __('Settings saved', 'pp-thesaurus') . '</strong></p>';
		if ($pp_thesaurus_updated) {
			echo '<p style="color:green;"><strong>' . __('Please select the languages for the thesaurus', 'pp-thesaurus') . '</strong></p>';
		}
	}
	?>
			<h3><?php _e('Common settings', 'pp-thesaurus'); ?></h3>
			<form method="post" action="">
			<table class="form-table">
				<tr valign="baseline">
					<th scope="row"><?php _e('Set the mouseover effect', 'pp-thesaurus'); ?></th>
					<td>
						<input id="popup_true" type="radio" name="popup" value="true" <?php echo $sPopupTrue; ?>/>
						<label for="popup_true"><?php _e('show the description in a tooltip',  'pp-thesaurus'); ?></label><br />
						<input id="popup_false" type="radio" name="popup" value="false" <?php echo $sPopupFalse; ?>/>
						<label for="popup_false"><?php _e('show only the title',  'pp-thesaurus'); ?></label>
					</td>
				</tr>
				<tr valign="baseline">
	<?php
	if (!$oPPTM->existsTripleStore()) {
		echo '<th scope="row">' . __('Set the thesaurus languages after the import', 'pp-thesaurus') . '</th>';
	} else {
		echo '<th scope="row">' . __('Set the thesaurus languages', 'pp-thesaurus') . '</th>';
	}
	?>
					<td>
						<?php echo pp_thesaurus_get_language_form(); ?>
					</td>
				</tr>
				<tr valign="baseline">
					<th scope="row"><?php _e('DBPedia SPARQL endpoint', 'pp-thesaurus'); ?></th>
					<td>
						URL: <input type="text" size="50" name="DBPediaEndpoint" value="<?php echo $sDBPediaEndpoint; ?>" />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save settings', 'pp-thesaurus') ?>" />
				<input type="hidden" name="secureToken" value="<?php echo pp_thesaurus_get_secure_token(); ?>" />
				<input type="hidden" name="from" value="common_settings" />
			</p>
			</form>


			<p>&nbsp;</p>
			<h3><?php _e('Data settings', 'pp-thesaurus'); ?></h3>
			<form method="post" action="" enctype="multipart/form-data">
			<table class="form-table">
				<tr valign="baseline">
					<th scope="row" colspan="2"><strong><?php _e('Import/Update SKOS Thesaurus from', 'pp-thesaurus'); ?></strong>:</th>
				</tr>
				<tr valign="baseline">
					<th scope="row"><?php _e('SPARQL endpoint', 'pp-thesaurus'); ?></th>
					<td>
						URL: <input type="text" size="50" name="SparqlEndpoint" value="<?php echo get_option('PPThesaurusSparqlEndpoint'); ?>" />
					</td>
				</tr>
				<tr valign="baseline">
					<th scope="row"><?php _e('RDF/XML file', 'pp-thesaurus'); ?></th>
					<td><input type="file" size="50" name="rdfFile" value="" /></td>
				</tr>
				<tr valign="baseline">
					<th scope="row" colspan="2">
	<?php
	if ($oPPTM->existsTripleStore()) {
		printf(__('Last data update on %1$s from %2$s', 'pp-thesaurus'), "<strong>$sDate</strong>", "<strong>$sFrom</strong>");
	} else {
		echo '<span style="color:red;">' . __('Please import a SKOS Thesaurus', 'pp-thesaurus') . '.</span>';
	}
	?>
					</th>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Import/Update Thesaurus', 'pp-thesaurus') ?>" />
				<input type="hidden" name="secureToken" value="<?php echo pp_thesaurus_get_secure_token(); ?>" />
				<input type="hidden" name="from" value="data_settings" />
			</p>
			</form>
			<p>
				This plugin is provided by the <a href="http://poolparty.punkt.at/" target="_blank">PoolParty</a> Team.<br />
				PoolParty is an easy-to-use SKOS editor for the Semantic Web
			</p>
	</div>
	<?php
}


function pp_thesaurus_get_language_form () {

	$oPPTM = PPThesaurusManager::getInstance();
	if (!$oPPTM->existsTripleStore()) {
		return '';
	}
	
	$aStoredLanguages = array();
	if ($sLang = get_option('PPThesaurusLanguage')) {
		$aStoredLanguages = split('#', $sLang);
	}

	$aThesLanguages	= $oPPTM->getLanguages();
	$aSysLanguages 	= array();
	if (function_exists('qtrans_getSortedLanguages')) {
		$aLang = qtrans_getSortedLanguages();
		foreach ($aLang as $sLang) {
			$aSysLanguages[$sLang] = qtrans_getLanguageName($sLang);
		}
	}

	$sContent = '';
	if (empty($aSysLanguages)) {
		$sFirstLang = $aStoredLanguages[0];
		foreach ($aThesLanguages as $sLang) {
			$sChecked = $sLang == $sFirstLang ? 'checked="checked" ' : '';
			$sContent .= '<input id="lang_' . $sLang . '" type="radio" name="languages[]" value="' . $sLang . '" ' . $sChecked . '/> ';
			$sContent .= '<label for="lang_' . $sLang . '">' . $sLang . '</label><br />';
		}
	} else {
		$aFlags = get_option('qtranslate_flags');
		$sFlagPath = get_option('qtranslate_flag_location');
		$sFlagPath = plugins_url() . substr($sFlagPath, strpos($sFlagPath, '/'));
		foreach ($aSysLanguages as $sLang => $sLangName) {
			if (in_array($sLang, $aThesLanguages)) {
				if (empty($aStoredLanguages)) {
					$sChecked = 'checked="checked" ';
				} else {
					$sChecked = in_array($sLang, $aStoredLanguages) ? 'checked="checked" ' : '';
				}
				$sContent .= '<input id="lang_' . $sLang . '" type="checkbox" name="languages[]" value="' . $sLang . '" ' . $sChecked . '/> ';
				$sContent .= '<label for="lang_' . $sLang . '">';
				$sContent .= '<img src="' . $sFlagPath . $aFlags[$sLang] . '" alt="Language: ' . $sLangName . '" /> ' . $sLangName . '</label><br />';
			} else {
				$sContent .= '<input id="lang_' . $sLang . '" type="checkbox" name="languages[]" value="' . $sLang . '" disabled="disabled" /> ';
				$sContent .= '<img src="' . $sFlagPath . $aFlags[$sLang] . '" alt="Language: ' . $sLangName . '" /> ' . $sLangName . ' (not available)<br />';
			}
		}
	}
	return $sContent;
}


function pp_thesaurus_settings_save () {
	$sError = '';
	$iPPThesaurusId = get_option('PPThesaurusId');
	$sPPThesaurusUpdated = get_option('PPThesaurusUpdated');
	switch ($_POST['from']) {
		case 'common_settings':
			update_option('PPThesaurusPopup', $_POST['popup'] == 'true' ? true : false);
			update_option('PPThesaurusLanguage', join('#', $_POST['languages']));
			update_option('PPThesaurusDBPediaEndpoint', $_POST['DBPediaEndpoint']);
			break;

		case 'data_settings':
			if (empty($_FILES['rdfFile']['name']) && empty($_POST['SparqlEndpoint'])) {
				$sError = 'Please indicate the SPARQL endpoint or the SKOS file to be imported.';
			} else {
				$sError = pp_thesaurus_import();
			}
			break;
	}
	// Check for old Versions (1.x) also
	if ($iPPThesaurusId == 0 || $sPPThesaurusUpdated == false || empty($sPPThesaurusUpdated)) {
		$iPageId = pp_thesaurus_add_pages();
		update_option('PPThesaurusId', $iPageId);
	}
	return $sError;
}

function pp_thesaurus_add_pages() {
	$aPageConf = array(
		'post_type'		=> 'page',
		'post_status'	=> 'publish',
		'post_title'	=> 'Glossary',
		'post_content'	=> "[ppt-abcindex]\n[ppt-itemlist]"
	);
	if (!($iPageId = wp_insert_post($aPageConf))) {
		return 0;
	}
	$aChildPageConf = array(
		'post_type'		=> 'page',
		'post_status'	=> 'publish',
		'post_title'	=> 'Item',
		'post_content'	=> "[ppt-abcindex]\n[ppt-itemdetails]",
		'post_parent'	=> $iPageId
	);
	if (!($iChildPageId = wp_insert_post($aChildPageConf))) {
		return 0;
	}

	return $iPageId;
}

function pp_thesaurus_import () {
	global $pp_thesaurus_updated;
	// Load RDF-Data into ARC store
	try {
		if (!empty($_FILES['rdfFile']['name'])) {
			PPThesaurusManager::importFromFile();
			update_option('PPThesaurusImportFile', $_FILES['rdfFile']['name']);
		} else {
			PPThesaurusManager::importFromEndpoint();
			pp_thesaurus_set_default_languages();
			update_option('PPThesaurusSparqlEndpoint', $_POST['SparqlEndpoint']);
			update_option('PPThesaurusImportFile', '');
		}
	} catch (Exception $e) {
		return $e->getMessage();
	}
	update_option('PPThesaurusUpdated', time());
	$pp_thesaurus_updated = true;
	return '';
}


function pp_thesaurus_set_default_languages () {
	// Set the default languages only if a new SPAQL endpoint is given
	if (get_option('PPThesaurusSparqlEndpoint') == $_POST['SparqlEndpoint']) {
		return false;
	}

	$oPPTM = PPThesaurusManager::getInstance();
	if (!$oPPTM->existsTripleStore()) {
		// No thesaurus data is given
		return false;
	}
	
	$aThesLanguages	= $oPPTM->getLanguages();
	if (!function_exists('qtrans_getSortedLanguages')) {
		update_option('PPThesaurusLanguage', $aThesLanguages[0]);
		return true;
	}

	$aLanguages = array();
	$aSysLanguages = qtrans_getSortedLanguages();
	foreach ($aSysLanguages as $sLang) {
		if (in_array($sLang, $aThesLanguages)) {
			$aLanguages[] = $sLang;
		}
	}
	sort($aLanguages, SORT_STRING);
	update_option('PPThesaurusLanguage', join('#', $aLanguages));

	return true;
}

function pp_thesaurus_get_secure_token () {
	return substr(md5(DB_USER . DB_NAME . 'ppThesaurus'), -10);
}






/* wp-content */

function pp_thesaurus_to_link ($aItemList) {
	if (empty($aItemList)) {
		return array();
	}

	$oPPTM = PPThesaurusManager::getInstance();
	$aLinks = array();
	foreach ($aItemList as $oItem) {
		$sDefinition = $oPPTM->getDefinition($oItem->uri, $oItem->definition, true);
		$aLinks[] = pp_thesaurus_get_link( $oItem->prefLabel, $oItem->uri, $oItem->prefLabel, $sDefinition, true);
	}

	return $aLinks;
}

function pp_thesaurus_get_link ($sText, $sUri, $sPrefLabel, $sDefinition, $bShowLink=false) {
	if (empty($sDefinition)) {
		if ($bShowLink) {
			$sPage = getTemplatePage();
			$sLink = '<a class="ppThesaurus" href="' . $sPage . '?uri=' . $sUri . '" title="Term: ' . $sPrefLabel . '">' . $sText . '</a>';
		} else {
			$sLink = $sText;
		}
	} else {
		$sPage = getTemplatePage();
		$sLink = '<a class="ppThesaurus" href="' . $sPage . '?uri=' . $sUri . '" title="Term: ' . $sPrefLabel . '">' . $sText . '</a>';
		$sLink .= '<span style="display:none;">' . $sDefinition . '</span>';
	}

	return $sLink;
}


function getTemplatePage () {
	$iPPThesaurusId = get_option('PPThesaurusId');

	$aChildren 	= get_children(array('numberposts'	=> 1,
									 'post_parent'	=> $iPPThesaurusId,
									 'post_type'	=> 'page'));
	$oChild = array_shift($aChildren);

	return get_page_link($iPPThesaurusId) . $oChild->post_name . '/';
}

