<?php

class PPThesaurus {

	/**
   * @var object
   */
	protected static $oInstance;

	/**
   * @var string
   */
	protected $slug = 'pp-thesaurus';

	/**
   * @var boolean
   */
	protected $thesaurusUpdated = FALSE;


	private function __construct() {
		if (file_exists(PP_THESAURUS_PLUGIN_DIR . 'arc/ARC2.php') || class_exists('ARC2')) {
			if (!class_exists('ARC2')) {
				require_once(PP_THESAURUS_PLUGIN_DIR . 'arc/ARC2.php');
			}
			if (class_exists('PPThesaurusTemplate')) {
				$oPPTManager = PPThesaurusManager::getInstance();
				$oPPTTemplate = new PPThesaurusTemplate();

				// Register actions and filters
				add_action('init', array($this, 'init'));
				add_action('init', array($this, 'loadTextdomain'));
				add_action('wpmu_new_blog', array($this, 'activateNewBlog'));
				add_action('admin_menu', array($this, 'requestSettingsPage'));
				add_filter('the_content', array($oPPTManager, 'parse'), 100);
				add_filter('the_title', array($oPPTTemplate, 'setTitle'));
				add_filter('wp_title', array($oPPTTemplate, 'setWPTitle'), 10, 3);

				// Register shortcodes
				add_shortcode('ppt-abcindex', array($oPPTTemplate, 'showABCIndex'));
				add_shortcode('ppt-itemlist', array($oPPTTemplate, 'showItemList'));
				add_shortcode('ppt-itemdetails', array($oPPTTemplate, 'showItemDetails'));
				add_shortcode('ppt-noparse', array($oPPTManager, 'cutContent'));

				// Add an action link pointing to the options page.
				$plugin_basename = plugin_basename(plugin_dir_path(realpath(dirname( __FILE__ ))) . $this->slug . '.php' );
				add_filter('plugin_action_links_' . $plugin_basename, array($this, 'addActionLinks'));
			}
		} else {
			add_action('init', 'pp_thesaurus_init_textdomain');
			add_action('admin_menu', 'pp_thesaurus_settings_request');
		}
	}


	/**
   * Return an instance of a class
   *
   * @return object		A single instance of this class.
   */
	public static function getInstance () {
		if(!isset(self::$oInstance)){
			$sClass =  __CLASS__;
			self::$oInstance = new $sClass();
		}
		return self::$oInstance;
	}



	/*********************************************
	 * Plugin activate methods
	 *********************************************/

	/**
   * Fired when the plugin is activated.
   */
	public static function activate($bNetworkWide) {
		if (function_exists('is_multisite') && is_multisite()) {
			if ($bNetworkWide) {
				// Get all blog ids
				$aBlogIds = self::getBlogIds();
				foreach ($aBlogIds as $iBlogId) {
					switch_to_blog($iBlogId);
					self::singleActivate();
					restore_current_blog();
				}
			} else {
				self::singleActivate();
			}
		} else {
			self::singleActivate();
		}
	}


	/**
   * Fired when a new site is activated with a WPMU environment.
   *
   * @param int $iBlogId		ID of the new blog.
   */
	public function activateNewBlog($iBlogId) {
		if (1 !== did_action('wpmu_new_blog')) {
			return;
		}

		switch_to_blog($iBlogId );
		self::singleActivate();
		restore_current_blog();
	}


	/**
   * Fired for each blog when the plugin is activated.
   */
	protected static function singleActivate() {
		// Install ARC2
		self::installArc();

		// Add options
		add_option('PPThesaurusId', 0); // The ID of the main glossary page
		add_option('PPThesaurusPopup', 1); // Show/hide the tooltip
		add_option('PPThesaurusLanguage', 'en'); // Enabled languages for the thesaurus
		add_option('PPThesaurusDBPediaEndpoint', 'http://dbpedia.org/sparql'); // The DBPedia SPARQL endpoint
		add_option('PPThesaurusSparqlEndpoint', ''); // The thesaurus SPARQL endpoint for import/update it 
		add_option('PPThesaurusImportFile', ''); // The file name from the with the thesaurus data
		add_option('PPThesaurusUpdated', ''); // The last import/update date 
		add_option('PPThesaurusSidebarTitle', 'Glossary Search'); // The title for the sidebar widget
		add_option('PPThesaurusSidebarInfo', 'Type a term ...'); // The info text for the sidebar widget
		add_option('PPThesaurusSidebarWidth', '100%'); // The width for the input field in the sidebar widget
	}


	/**
   * Register and enqueue JavaScript files, style sheets and sidebar widget.
   */
	public function init() {
		// Register and enqueue JavaScript files and style sheets only on the public area
		if (!is_admin()) {
			wp_enqueue_script('jquery');
			wp_enqueue_script($this->slug . '-autocomplete-script', plugins_url('/js/jquery.autocomplete.min.js', dirname(__FILE__)), array('jquery'));
			wp_enqueue_script($this->slug . '-common-script', plugins_url('/js/script.js', dirname(__FILE__)), array($this->slug . '-autocomplete-script'));
			wp_enqueue_style($this->slug . '-autocomplete-style', plugins_url('/css/jquery.autocomplete.css', dirname(__FILE__)));
			wp_enqueue_style($this->slug . '-common-style', plugins_url('/css/style.css', dirname(__FILE__)));

			// Load tooltip Javascript and style sheet if it is enabled
			$iPopup = get_option('PPThesaurusPopup');
			if ($iPopup == 1) {
				wp_enqueue_script($this->slug . '-unitip-script', plugins_url('/js/unitip.js', dirname(__FILE__)), array('jquery'));
				wp_enqueue_style($this->slug . '-unitip-style', plugins_url('/js/unitip/unitip.css', dirname(__FILE__)));
			}
		}
  }


	/**
   * Register the sidebar widget
	 */
	public function registerWidget() {
		register_widget('PPThesaurusWidget');
	}


	/**
   * Load the plugin text domain for translation.
   */
	public function loadTextdomain() {
		load_plugin_textdomain($this->slug, FALSE, dirname(plugin_basename( dirname(__FILE__) )) . '/languages/');
	}


	/**
   * Add settings action link to the plugins page.
   */
	public function addActionLinks($aLinks) {
		return array_merge(array('<a href="' . admin_url('options-general.php?page=' . $this->slug) . '">' . __('Settings', $this->slug) . '</a>'),	$aLinks);
	}


	/**
   * Get all blog ids of blogs in the current network that are:
   * not archived, not spam, not deleted
   *
   * @return array|false		The blog ids, false if no matches.
   */
	public static function getBlogIds() {
		global $wpdb;

		// get an array of blog ids
		$sSql = "SELECT blog_id 
						FROM $wpdb->blogs
						WHERE archived = '0' AND spam = '0'
						AND deleted = '0'";

		return $wpdb->get_col( $sSql );
	}


	/**
	 * Downlods and installs the ARC2 library if not exists.
	 * It caches the thesaurus in a triple store.
   */
	protected static function installArc() {
		if (file_exists(PP_THESAURUS_PLUGIN_DIR . 'arc/ARC2.php') || class_exists('ARC2')) {
			return true;
		}

		if (!is_writable(PP_THESAURUS_PLUGIN_DIR)) {
			return false;
		}

		$sDir = getcwd();
		chdir(PP_THESAURUS_PLUGIN_DIR);

		// download ARC2
		$sTarFileName 	= 'arc.tar.gz';
		$sCmd 			= 'wget --no-check-certificate -T 2 -t 1 -O ' . $sTarFileName . ' ' . PP_THESAURUS_ARC_URL . ' 2>&1';
		$aOutput 		= array();
		exec($sCmd, $aOutput, $iResult);
		if ($iResult != 0) {
			chdir($sDir);
			return false;
		}

		// untar the file
		$sCmd 		= 'tar -xvzf ' . $sTarFileName . ' 2>&1';
		$aOutput 	= array();
		exec($sCmd, $aOutput, $iResult);
		if ($iResult != 0) {
			chdir($sDir);
			return false;
		}

		// delete old arc direcotry and tar file
		@rmdir('arc');
		@unlink($sTarFileName);

		// rename the ARC2 folder to arc
		$sCmd		= 'mv semsol-arc2-* arc 2>&1';
		$aOutput 	= array();
		exec($sCmd, $aOutput, $iResult);
		if ($iResult != 0) {
			chdir($sDir);
			return false;
		}

		chdir($sDir);
		return true;
	}



	/*********************************************
	 * Plugin load methods
	 *********************************************/

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
   */
	public function requestSettingsPage() {
		add_options_page('PoolParty Thesaurus Settings', 'PoolParty Thesaurus', 'manage_options', $this->slug, array($this, 'preloadSettingsPage'));
	}

	
	/**
	 * Check if settings must be saved before loading the settings page
   */
	public function preloadSettingsPage() {
		$sError = '';
		if (isset($_POST['secureToken']) && ($_POST['secureToken'] == $this->getSecureToken())) {
			$sError = $this->saveSettings();
		}
		$this->loadSettingsPage($sError);
	}

	protected function loadSettingsPage($sError) {
		if (!class_exists('ARC2')) {
		?>
			<div class="wrap">
				<h2><?php _e('PoolParty Thesaurus Settings', $this->slug); ?></h2>
				<div id="message" class="error">
					<p><strong><?php _e('Please install ARC2 first before you can change the settings!', $this->slug); ?></strong></p>
				</div>
				<p><?php _e('Download ARC2 from https://github.com/semsol/arc2 and unzip it. Open the unziped folder and upload the entire contents into the \'/wp-content/plugins/poolparty-thesaurus/arc/\' directory.', $this->slug); ?></p>
			</div>
		<?php
			exit();
		}

		$oPPTM = PPThesaurusManager::getInstance();
		?>
			<div class="wrap">
				<div class="icon32" id="icon-options-general"></div>
				<h2><?php _e('PoolParty Thesaurus Settings', $this->slug); ?></h2>
		<?php
		if (!empty($sError)) {
			echo '<div id="message" class="error"><p><strong>' . $sError . '</strong></p></div>';
		} elseif (isset($_POST['secureToken']) && empty($sError)) {
			echo '<div id="message" class="updated fade"><p>' . __('Settings saved', $this->slug) . '</p>';
			if ($this->thesaurusUpdated) {
				echo '<p>' . __('Please select the thesaurus languages.', $this->slug) . '</p>';
			}
			echo '</div>';
		}

		$sImportFile = get_option('PPThesaurusImportFile');
		$sSparqlEndpoint = get_option('PPThesaurusSparqlEndpoint');
		$sDBPediaEndpoint = get_option('PPThesaurusDBPediaEndpoint');
		$sUpdated = get_option('PPThesaurusUpdated');
		$sDate = empty($sUpdated) ? 'undefined' : date('d.m.Y', $sUpdated);
		$sFrom = empty($sImportFile) ? empty($sSparqlEndpoint) ? 'undefined' : 'SPARQL endpoint' : $sImportFile;
		?>
			<h3><?php _e('Data settings', $this->slug); ?></h3>
			<form method="post" action="" enctype="multipart/form-data">
				<table class="form-table">
					<tr valign="baseline">
						<td scope="row" colspan="2">
		<?php
		if ($oPPTM->existsTripleStore()) {
			printf(__('Last data update on %1$s from %2$s', $this->slug), "<strong>$sDate</strong>", "<strong>$sFrom</strong>");
		} else {
			echo '<span style="color:red;">' . __('Please import a SKOS Thesaurus', $this->slug) . '.</span>';
		}
		?>
						</td>
					</tr>
					<tr valign="baseline">
						<th scope="row" colspan="2"><strong><?php _e('Import/Update SKOS Thesaurus from', $this->slug); ?></strong>:</th>
					</tr>
					<tr valign="baseline">
						<th scope="row" style="padding-left:10px"><?php _e('SPARQL endpoint', $this->slug); ?></th>
						<td>
							URL: <input type="text" size="50" name="SparqlEndpoint" value="<?php echo get_option('PPThesaurusSparqlEndpoint'); ?>" />
						</td>
					</tr>
					<tr valign="baseline">
						<td scope="row" colspan="2" style="padding: 0 10px;"> - <?php  _e('or', $this->slug); ?> - </td>
					</tr>
					<tr valign="baseline">
						<th scope="row" style="padding-left:10px"><?php _e('RDF/XML file', $this->slug); ?> (max. <?php echo ini_get('upload_max_filesize'); ?>B)</th>
						<td><input type="file" size="50" name="rdfFile" value="" /></td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Import/Update Thesaurus', $this->slug) ?>" />
					<input type="hidden" name="secureToken" value="<?php echo $this->getSecureToken(); ?>" />
					<input type="hidden" name="from" value="data_settings" />
				</p>
			</form>
		<?php
		if ($oPPTM->existsTripleStore()) {
			$iPopup = get_option('PPThesaurusPopup');
			$sVariable = 'sPopup' . $iPopup;
			$$sVariable = 'checked="checked" ';
			?>
			<p>&nbsp;</p>
				<h3><?php _e('Common settings', $this->slug); ?></h3>
				<form method="post" action="">
					<table class="form-table">
						<tr valign="baseline">
							<th scope="row"><?php _e('Options for automatic linking of recognized terms', $this->slug); ?></th>
							<td>
								<input id="popup_1" type="radio" name="popup" value="1" <?php echo $sPopup1; ?>/>
								<label for="popup_1"><?php _e('link and show description in tooltip',  $this->slug); ?></label><br />
								<input id="popup_0" type="radio" name="popup" value="0" <?php echo $sPopup0; ?>/>
								<label for="popup_0"><?php _e('link without tooltip',  $this->slug); ?></label><br />
								<input id="popup_2" type="radio" name="popup" value="2" <?php echo $sPopup2; ?>/>
								<label for="popup_2"><?php _e('automatic linking disabled',  $this->slug); ?></label>
							</td>
						</tr>
						<tr valign="baseline">
							<th scope="row"><?php  _e('Thesaurus languages', $this->slug); ?></th>
							<td>
								<?php echo $this->getLanguageForm(); ?>
							</td>
						</tr>
						<tr valign="baseline">
							<th scope="row"><?php _e('DBPedia SPARQL endpoint', $this->slug); ?></th>
							<td>
								URL: <input type="text" size="50" name="DBPediaEndpoint" value="<?php echo $sDBPediaEndpoint; ?>" />
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php _e('Save settings', $this->slug) ?>" />
						<input type="hidden" name="secureToken" value="<?php echo $this->getSecureToken(); ?>" />
						<input type="hidden" name="from" value="common_settings" />
					</p>
				</form>
		<?php
		}
		?>
			<p>
				This plugin is provided by the <a href="http://poolparty.biz/" target="_blank">PoolParty</a> Team.<br />
				PoolParty is an easy-to-use SKOS editor for the Semantic Web
			</p>
		</div>
		<?php
	} 


	protected function getLanguageForm() {
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
					$sContent .= '<img src="' . $sFlagPath . $aFlags[$sLang] . '" alt="Language: ' . $sLangName . '" /> ';
					$sContent .= $sLangName . ' (' . __('not available', 'pp-thesaurus') . ')<br />';
				}
			}
		}
		return $sContent;
	}


	protected function saveSettings() {
		$sError = '';
		$iPPThesaurusId = get_option('PPThesaurusId');
		$sPPThesaurusUpdated = get_option('PPThesaurusUpdated');
		switch ($_POST['from']) {
			case 'common_settings':
				update_option('PPThesaurusPopup', $_POST['popup']);
				update_option('PPThesaurusLanguage', join('#', $_POST['languages']));
				update_option('PPThesaurusDBPediaEndpoint', $_POST['DBPediaEndpoint']);
				break;

			case 'data_settings':
				if (empty($_FILES['rdfFile']['name']) && empty($_POST['SparqlEndpoint'])) {
					$sError = __('Please indicate the SPARQL endpoint or the SKOS file to be imported.', 'pp-thesaurus');
				} else {
					$sError = $this->importThesaurus();
				}
				break;
		}
		// Check for old Versions (1.x) also
		if ($iPPThesaurusId == 0 || $sPPThesaurusUpdated == false || empty($sPPThesaurusUpdated)) {
			$iPageId = $this->addGlossaryPage();
			update_option('PPThesaurusId', $iPageId);
		}

		return $sError;
	}


	protected function addGlossaryPage() {
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


	protected function importThesaurus() {
		// Load RDF-Data into ARC store
		try {
			if (!empty($_FILES['rdfFile']['name'])) {
				PPThesaurusManager::importFromFile();
				update_option('PPThesaurusImportFile', $_FILES['rdfFile']['name']);
			} else {
				PPThesaurusManager::importFromEndpoint();
				$this->setDefaultLanguages();
				update_option('PPThesaurusSparqlEndpoint', $_POST['SparqlEndpoint']);
				update_option('PPThesaurusImportFile', '');
			}
		} catch (Exception $e) {
			return $e->getMessage();
		}
		update_option('PPThesaurusUpdated', time());
		$this->thesaurusUpdated = true;

		return '';
	}


	protected function setDefaultLanguages() {
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


	protected function getSecureToken() {
		return substr(md5(DB_USER . DB_NAME . 'ppThesaurus'), -10);
	}
}
