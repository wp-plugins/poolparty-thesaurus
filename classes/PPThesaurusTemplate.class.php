<?php

class PPThesaurusTemplate {

	private $oPPTM;
	private $oItem;

	public function __construct () {
		$this->oPPTM = PPThesaurusManager::getInstance();
		$this->oItem = NULL;
	}

	public function showABCIndex ($aAtts) {

		if (isset($_GET['filter'])) {
			$iChar = $_GET['filter'];
		} else {
			try {
				if (is_null($this->oItem)) {
					$this->oItem = $this->oPPTM->getItem($_GET['uri']);
				}
			} catch (Exception $e) {
				return '<p>' . __('An error has occurred while reading concept data.', 'pp-thesaurus') . '</p>';
			}

			$iChar = ord(strtoupper($this->oItem->prefLabel));
		}
		$oPage		= PPThesaurusPage::getInstance();
		$aIndex 	= $this->oPPTM->getAbcIndex($iChar);
		$iCount 	= count($aIndex);
		$sContent 	= '<ul class="PPThesaurusAbcIndex">';
		$i 			= 1;
		foreach($aIndex as $sChar => $sKind) {
			$sClass = ($i == 1) ? 'first' : ($i == $iCount ? 'last' : '');
			$sLetter = ($sChar == 'ALL') ? 'ALL' : chr($sChar);
			$sLink = '<a href="' . get_bloginfo('url', 'display') . '/' . $oPage->thesaurusPage->post_name . '?filter=' . $sChar . '">' . $sLetter . '</a>';
			switch ($sKind) {
				case 'disabled':
					if (!empty($sClass)) {
						$sClass = ' class="' . $sClass . '"';
					}
					$sContent .= '<li' . $sClass . '>' . $sLetter . '</li>';
					break;

				case 'enabled':
					if (!empty($sClass)) {
						$sClass = ' class="' . $sClass . '"';
					}
					$sContent .= '<li' . $sClass . '>' . $sLink . '</li>';
					break;

				case 'selected':
					$sContent .= '<li class="selected ' . $sClass . '">' . $sLink . '</li>';
					break;
			}
			$i++;
		}
		$sContent .= '</ul>';

		return $sContent;
	}


	public function showItemList ($aAtts) {
		$aList 		= $this->oPPTM->getList($_GET['filter']);
		$iCount 	= count($aList);
		$sContent 	= '';
		if ($iCount > 0) {
			$sContent .= '<ul class="PPThesaurusList">';
			$i = 1;
			foreach($aList as $oConcept) {
				$sClass = ($i == 1) ? ' class="first"' : ($i == $iCount ? ' class="last"' : '');
				$sDefinition = $this->oPPTM->getDefinition($oConcept->uri, $oConcept->definition, true, true);
				$sContent .= '<li' . $sClass . '>' . $this->createLink($oConcept->prefLabel, $oConcept->uri, $oConcept->prefLabel, $sDefinition, true) . '</li>';
				$i++;
			}
			$sContent .= '</ul>';
		}

		return $sContent;
	}


	public function showItemDetails ($aAtts) {
		try {
			if (is_null($this->oItem)) {
				$this->oItem = $this->oPPTM->getItem($_GET['uri']);
			}
		} catch (Exception $e) {
			return '<p>' . __('An error has occurred while reading concept data.', 'pp-thesaurus') . '</p>';
		}

		$sContent = '<div class="PPThesaurusDetails">';
		if ($this->oItem->searchLink) {
			$sContent .= '<p>' . __('Search for documents related with', 'pp-thesaurus') . ' <a href="' . $this->oItem->searchLink . '">' . $this->oItem->prefLabel . '</a></p>';
		}
		if ($this->oItem->altLabels) {
			$sContent .= '<div class="synonyms"><strong>' . __('Synonyms', 'pp-thesaurus') . ':</strong> ' . implode(', ', $this->oItem->altLabels) . '</div>';
		}
		$sContent .= '<p class="definition">' . $this->oPPTM->getDefinition($this->oItem->uri, $this->oItem->definition) . '</p>';

		if ($this->oItem->scopeNote) {
			$sContent .= '<blockquote>' . $this->oItem->scopeNote . '</blockquote>';
		}

		if ($this->oItem->relatedList) {
			$sContent .= '<p class="relation"><strong>' . __('Related terms', 'pp-thesaurus') . ':</strong><br />' . implode(', ', $this->createLinks($this->oItem->relatedList)) . '</p>';
		}

		if ($this->oItem->broaderList) {
			$sContent .= '<p class="relation"><strong>' . __('Broader terms', 'pp-thesaurus') . ':</strong><br />' . implode(', ', $this->createLinks($this->oItem->broaderList)) . '</p>';
		}

		if ($this->oItem->narrowerList) {
			$sContent .= '<p class="relation"><strong>' . __('Narrower terms', 'pp-thesaurus') . ':</strong><br />' . implode(', ', $this->createLinks($this->oItem->narrowerList)) . '</p>';
		}

		if ($this->oItem->uri) {
			$sLabel	= '<strong>' . $this->oItem->prefLabel . '</strong>';
			$sLink 	= '<a href="' . $this->oItem->uri . '" target="_blank">' . $sLabel . '</a>';
			$sContent .= '<p>' . sprintf(__('Linked data frontend for %s', 'pp-thesaurus'), $sLink) . '.</p>';
		}

		$sContent .= '</div>';

		return $sContent;
	}


	public function setWPTitle ($sTitle, $sSep, $sSepLocation) {
		$sTitle		= trim($sTitle);
		$sNewTitle 	= $this->setTitle($sTitle);
		if ($sNewTitle == $sTitle) {
			return $sTitle;
		}

		return sprintf(__('Definition of %s', 'pp-thesaurus'), $sNewTitle);
	}


	public function setTitle ($sTitle) {
		$oPage	= PPThesaurusPage::getInstance();
		$oChild = $oPage->itemPage;
		$sTitle = trim($sTitle);

		if (!is_page($oChild->ID)) {
			return $sTitle;
		}

		if (function_exists('qtrans_split')) {
			$aTitles = qtrans_split($oChild->post_title);
			$sPageTitle = $aTitles[qtrans_getLanguage()];
		} else {
			$sPageTitle = $oChild->post_title;
		}
		if (strcasecmp($sTitle, $sPageTitle) || empty($_GET['uri'])) {
			return $sTitle;
		}

		try {
			if (is_null($this->oItem)) {
				$this->oItem = $this->oPPTM->getItem($_GET['uri']);
			}
		} catch (Exception $e) {
			return $sTitle;
		}

		return $this->oItem->prefLabel;
	}


	public function showSidebar ($aArgs) {
		$sTitle = get_option('PPThesaurusSidebarTitle');
		$sInfo 	= get_option('PPThesaurusSidebarInfo');
		$sWidth = get_option('PPThesaurusSidebarWidth');
		extract($aArgs);
		echo $before_widget;
		echo $before_title . $sTitle . $after_title;
		echo '
			<script type="text/javascript">
			//<![CDATA[
				var pp_thesaurus_suggest_url = "' . plugins_url('/pp-thesaurus-autocomplete.php', dirname(__FILE__)) . '";
			//]]>
			</script>
			<div class="PPThesaurus_sidebar">
				<input id="pp_thesaurus_input_term" type="text" name="term" value="" title="' . $sInfo . '" style="width:' . $sWidth . '" />
			</div>
		';
		echo $after_widget;
	}


	public function showSidebarControl () {
		$sTitle = $sNewTitle = get_option('PPThesaurusSidebarTitle');
		$sInfo 	= $sNewInfo	 = get_option('PPThesaurusSidebarInfo');
		$sWidth = $sNewWidth = get_option('PPThesaurusSidebarWidth');
		if (isset($_POST['pp_thesaurus_submit'] ) && $_POST['pp_thesaurus_submit'] ) {
			$sNewTitle 	= trim(strip_tags(stripslashes($_POST['pp_thesaurus_title'])));
			$sNewInfo 	= trim(strip_tags(stripslashes($_POST['pp_thesaurus_info'])));
			$sNewWidth 	= trim(stripslashes($_POST['pp_thesaurus_width']));
			if (empty($sNewTitle)) $sNewTitle = 'Thesaurus Search';
			if (empty($sNewWidth)) $sNewWidth = '100%';
		}
		if ($sTitle != $sNewTitle ) {
			$sTitle = $sNewTitle;
			update_option('PPThesaurusSidebarTitle', $sTitle);
		}
		if ($sInfo != $sNewInfo ) {
			$sInfo = $sNewInfo;
			update_option('PPThesaurusSidebarInfo', $sInfo);
		}
		if ($sWidth != $sNewWidth ) {
			$sWidth = $sNewWidth;
			update_option('PPThesaurusSidebarWidth', $sWidth);
		}
		$sTitle = htmlspecialchars($sTitle, ENT_QUOTES);
	?>
		<p>
			<label for="pp_thesaurus_title"><?php _e('Title', 'pp-thesaurus'); ?>: <br />
			<input id="pp_thesaurus_title" class="widefat" name="pp_thesaurus_title" type="text" value="<?php echo $sTitle; ?>" /></label>
		</p>
		<p>
			<label for="pp_thesaurus_info"><?php _e('Info text', 'pp-thesaurus'); ?>: <br />
			<input id="pp_thesaurus_info" class="widefat" name="pp_thesaurus_info" type="text" value="<?php echo $sInfo; ?>" /></label>
		</p>
		<p>
			<label for="pp_thesaurus_width"><?php _e('Width of the search field', 'pp-thesaurus'); ?>: <br />
			<input name="pp_thesaurus_width" type="text" value="<?php echo $sWidth; ?>" /></label> ('%' <?php _e('or', 'pp-thesaurus'); ?> 'px')
		</p>
		<input type="hidden" name="pp_thesaurus_submit" value="1" />
	<?php
	}


	public function createLink ($sText, $sUri, $sPrefLabel, $sDefinition, $bShowLink=false) {
		if (empty($sDefinition)) {
			if ($bShowLink) {
				$sPage = self::getItemLink();
				$sLink = '<a class="ppThesaurus" href="' . $sPage . '?uri=' . $sUri . '" title="Term: ' . $sPrefLabel . '">' . $sText . '</a>';
			} else {
				$sLink = $sText;
			}
		} else {
			$sPage = self::getItemLink();
			$sLink = '<a class="ppThesaurus" href="' . $sPage . '?uri=' . $sUri . '" title="Term: ' . $sPrefLabel . '">' . $sText . '</a>';
			$sLink .= '<span style="display:none;">' . $sDefinition . '</span>';
		}

		return $sLink;
	}


	public function getItemLink () {
		$oPage = PPThesaurusPage::getInstance();
		return get_bloginfo('url', 'display') . '/' . $oPage->thesaurusPage->post_name . '/' . $oPage->itemPage->post_name;
	}


	protected function createLinks ($aItemList) {
		if (empty($aItemList)) {
			return array();
		}

		$oPPTM = PPThesaurusManager::getInstance();
		$aLinks = array();
		foreach ($aItemList as $oItem) {
			$sDefinition = $oPPTM->getDefinition($oItem->uri, $oItem->definition, true, true);
			$aLinks[] = $this->createLink( $oItem->prefLabel, $oItem->uri, $oItem->prefLabel, $sDefinition, true);
		}

		return $aLinks;
	}
}
