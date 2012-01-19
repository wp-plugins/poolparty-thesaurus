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
				$sContent .= '<li' . $sClass . '>' . pp_thesaurus_get_link($oConcept->prefLabel, $oConcept->uri, $oConcept->prefLabel, $sDefinition, true) . '</li>';
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
			$sContent .= '<p class="relation"><strong>' . __('Related terms', 'pp-thesaurus') . ':</strong><br />' . implode(', ', pp_thesaurus_to_link($this->oItem->relatedList)) . '</p>';
		}

		if ($this->oItem->broaderList) {
			$sContent .= '<p class="relation"><strong>' . __('Broader terms', 'pp-thesaurus') . ':</strong><br />' . implode(', ', pp_thesaurus_to_link($this->oItem->broaderList)) . '</p>';
		}

		if ($this->oItem->narrowerList) {
			$sContent .= '<p class="relation"><strong>' . __('Narrower terms', 'pp-thesaurus') . ':</strong><br />' . implode(', ', pp_thesaurus_to_link($this->oItem->narrowerList)) . '</p>';
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
}
