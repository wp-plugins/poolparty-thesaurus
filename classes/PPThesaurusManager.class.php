<?php

include_once(PP_THESAURUS_PLUGIN_DIR . '/arc/ARC2.php');

function getWpPrefix () {
	global $wpdb;
	return $wpdb->prefix;
}


class PPThesaurusManager {

	private static $oInstance;

	private $oStore;
	private $aList;
	private $sSkosUri;
	private $sLanguage;



	private function __construct () {
		/* Den Tripplestore konfigurieren */
		$aConfig = array(
			'db_host'		=> DB_HOST,
			'db_name'		=> DB_NAME,
			'db_user'		=> DB_USER,
			'db_pwd'		=> DB_PASSWORD,
			'store_name'	=> getWpPrefix() . 'pp_thesaurus',
		);
		$this->oStore = ARC2::getStore($aConfig);
		if (!$this->oStore->isSetUp()) {
			$this->oStore->setUp();
		}
		$this->aList = array();
		$this->sSkosUri 	= 'http://www.w3.org/2004/02/skos/core#';
		$this->sLanguage 	= get_option('PPThesaurusLanguage');
	}

	public static function getInstance () {
		if(!isset(self::$oInstance)){
			$sClass =  __CLASS__;
			self::$oInstance = new $sClass();
		}
		return self::$oInstance;
	}

	public function import () {
		$aUploadFile 	= $_FILES['rdfFile'];

		if ($aUploadFile['error'] == 4) {
			return 0;
		}
		if ($aUploadFile['error'] >= 1) {
			throw new Exception ('Es ist beim Hochladen ein Fehler aufgetreten.');
		}
		if ($aUploadFile['type'] != 'application/rdf+xml') {
			throw new Exception ('Das angegebene File ist kein RDF-File.');
		}
		if (!is_uploaded_file($aUploadFile['tmp_name'])) {
			throw new Exception ('RDF-File konnte nicht upgeloaded werden.');
		}

		// All tables are emptied
		$this->oStore->reset();

		// Load RDF data into ARC store
		if (!($this->oStore->query('LOAD <file://' . $aUploadFile['tmp_name'] . '>'))) {
			throw new Exception ('RDF-Daten konnten nicht gespeichert werden.');
		}
	}


	public function parse ($sContent) {
		$aConcepts = $this->getConcepts();

		// Um die gefundenen Begriffe im Content einen speziellen Tag legen (nur dem 1. Fund pro Begriff)
		$sTagName = 'pp-thesaurus-code';
		foreach($aConcepts as $aConcept){
			$sLabel = addcslashes($aConcept['label'], '/.*+');
			$sLabelSearch = '/\b' . $sLabel . '\b/i';
			$sLabelReplace = '<' . $sTagName . ' label="' . $sLabel . '">$0</' . $sTagName . '>';
			$sContent = preg_replace($sLabelSearch, $sLabelReplace, $sContent, 1, $count);
			$sContent = rtrim($sContent);
		}

		// Speziellen Tag innerhalb eines speziellen Tags wieder rausnehmen
		$sLinkSearch = '/<' . $sTagName . ' label="(.*?)<' . $sTagName . ' label="(.+?)">(.+?)<\/' . $sTagName . '>/i';
		while (preg_match($sLinkSearch, $sContent)) {
			$sLinkReplace = '<' . $sTagName . ' label="$1$2';
			$sContent = preg_replace($sLinkSearch, $sLinkReplace, $sContent);
		}

		// Den speziellen Tag im href-Attribut in einem Link wieder entfernen
		$sLinkSearch = '/<a href="(.*?)<' . $sTagName . ' label="(.+?)">(.+?)<\/' . $sTagName . '>(.*?)"/i';
		while (preg_match($sLinkSearch, $sContent)) {
			$sLinkReplace = '<a href="$1$3$4"';
			$sContent = preg_replace($sLinkSearch, $sLinkReplace, $sContent);
		}

		// Um den gesamten Content einen speziellen Root Tag legen
		$sContent = "<pp-thesaurus>$sContent</pp-thesaurus>";
		
		// Ein gefundenen Begriff innerhalb eines Link-Tags markieren
		$oDom = new DOMDocument();
		@$oDom->loadXML($sContent);
		$oDom->preserveWhiteSpace = false; 
		$oNewLink = $oDom->getElementsByTagname($sTagName);

		$aNodes = array();
		for ($i = 0; $i < $oNewLink->length; ++$i) {
			$aNodes[] = $oNewLink->item($i);
		}
		foreach ($aNodes as $oNode){
			$oCurrentNode = $oNode->parentNode;
			$bEndLoop = false;
			while(!$bEndLoop){
				if ($oCurrentNode->nodeName == 'pp-thesaurus'){
					$bEndLoop = true;
				}
				if ($oCurrentNode->nodeName == 'a'){
					$oDom->createAttribute('delete');
					$oNode->setAttribute('delete', 'yes');
					$sContent = $oDom->saveHTML();
				}
				$oCurrentNode = $oCurrentNode->parentNode;
			}
		}

		// Einem markierten Begriff den speziellen Tag entfernen
		$sLinkSearch = '/<' . $sTagName . ' label="(.+?)" delete="yes">(.+?)<\/' . $sTagName . '>/i';
		$sLinkReplace = '$2';
		$sContent = preg_replace($sLinkSearch, $sLinkReplace, $sContent);

		// Um alle restlichen Begriffe einen Link Tag legen
		$sLinkSearch = '/<' . $sTagName . ' label="(.+?)">(.+?)<\/' . $sTagName . '>/i';
		$sLinkReplace = '<a class="ppThesaurus" href="' . pp_thesaurus_link() . '$1" title="Item: $1">$2</a>';
		$sContent = preg_replace($sLinkSearch, $sLinkReplace, $sContent);

		// Den speziellen Root Tag wieder entfernen
		$sContent = str_replace(array('<pp-thesaurus>', '</pp-thesaurus>'), array('', ''), $sContent);

		return $sContent;
	}


	public function exists () {
		$aList = $this->getConcepts();
		return !empty($aList);
	}


	public function getAbcIndex ($sFilter='ALL') {
		$aList = $this->getConcepts('sortByLabel');

		$aIndex['ALL'] = 'enabled';
		for ($i=65; $i<=90; $i++) {
			$aIndex[$i] = 'disabled';
		}

		foreach ($aList as $aItem) {
			$sChar = ord(strtoupper($aItem['label']));
			$aIndex[$sChar] = ($sChar == $sFilter) ? 'selected' : 'enabled';
		}

		if (strtoupper($sFilter) == 'ALL') {
			$aIndex['ALL'] = 'selected';
		}

		return $aIndex;
	}


	public function getList ($sFilter='ALL') {
		$aList = $this->getConcepts('sortByLabel');
		if (strtoupper($sFilter) == 'ALL') {
			return $aList;
		}

		$aReturn = array();
		foreach ($aList as $aItem) {
			$sFirstLetter = ord(strtoupper($aItem['label']));
			if ( $sFirstLetter == $sFilter) {
				$aReturn[] = $aItem;
			}
		}

		return $aReturn;
	}

	
	public function getTemplatePage () {
		$iPPThesaurusId = get_option('PPThesaurusId');

		$oParent 	= get_page($iPPGlossaryId);
		$aChildren 	= get_children(array('numberposts'	=> 1,
										 'post_parent'	=> $iPPThesaurusId,
										 'post_type'	=> 'page'));
		$oChild = array_shift($aChildren);

		return get_option('siteurl') . '/' . $oParent->post_name . '/' . $oChild->post_name;
	}


	public function getItem ($sLabel, $sType='label', $bWithRelations=true) {
		$sLabel = trim($sLabel);
		if (empty($sLabel)) {
			return null;
		}

		if ($sType == 'label') {
			$sQuery = "
				PREFIX skos: <" . $this->sSkosUri . ">

				SELECT *
				WHERE {
				  ?concept a skos:Concept .
				  ?concept skos:prefLabel ?prefLabel FILTER (lang(?prefLabel) = '" . $this->sLanguage . "') .
				  ?concept skos:definition ?definition FILTER (lang(?definition) = '" . $this->sLanguage . "') .
				  OPTIONAL {?concept skos:altLabel ?altLabel FILTER (lang(?altLabel) = '" . $this->sLanguage . "') . }
				  OPTIONAL {?concept skos:hiddenLabel ?hiddenLabel FILTER (lang(?hiddenLabel) = '" . $this->sLanguage . "') . }
				  OPTIONAL {?concept skos:scopeNote ?scopeNote FILTER (lang(?scopeNote) = '" . $this->sLanguage . "') . }

					{ ?concept skos:prefLabel ?label . }
				  UNION
					{ ?concept skos:altLabel ?label . }
				  UNION
					{ ?concept skos:hiddenLabel ?label . }

				  FILTER (lang(?label) = '" . $this->sLanguage . "' && ?label = '$sLabel')
				}
			";
		} else {
			$sQuery = "
				PREFIX skos: <" . $this->sSkosUri . ">

				SELECT *
				WHERE {
				  ?concept a skos:Concept .
				  ?concept skos:prefLabel ?prefLabel FILTER (lang(?prefLabel) = '" . $this->sLanguage . "') .
				  ?concept skos:definition ?definition FILTER (lang(?definition) = '" . $this->sLanguage . "') .
				  OPTIONAL {?concept skos:altLabel ?altLabel FILTER (lang(?altLabel) = '" . $this->sLanguage . "') . }
				  OPTIONAL {?concept skos:hiddenLabel ?hiddenLabel FILTER (lang(?hiddenLabel) = '" . $this->sLanguage . "') . }
				  OPTIONAL {?concept skos:scopeNote ?scopeNote FILTER (lang(?scopeNote) = '" . $this->sLanguage . "') . }

				  FILTER (?concept = '$sLabel')
				}
			";
		}
		$aRows = $this->oStore->query($sQuery, 'rows');

		if ($this->oStore->getErrors()) {
			throw new Exception ("Konnte Query nicht ausführen: $sQuery");
		}
		if (empty($aRows)) {
			return null;
		}

		$oItem 			= new PPThesaurusItem();
		$aAltLables 	= array();
		$aHiddenLables 	= array();

		foreach ($aRows as $aRow) {
			$oItem->uri = $aRow['concept'];
			$oItem->prefLabel = $aRow['prefLabel'];
			if (isset($aRow['altLabel'])) {
				$aAltLabels[] = $aRow['altLabel'];
			}
			if (isset($aRow['hiddenLabel'])) {
				$aHiddenLabels[] = $aRow['hiddenLabel'];
			}
			if (isset($aRow['definition'])) {
				$oItem->definition = $aRow['definition'];
			}
			if (isset($aRow['scopeNote'])) {
				$oItem->scopeNote = $aRow['scopeNote'];
			}
			if ($bWithRelations) {
				$oItem->broaderList 	= $this->getItemRelations($aRow['concept'], 'broader');
				$oItem->narrowerList 	= $this->getItemRelations($aRow['concept'], 'narrower');
				$oItem->relatedList 	= $this->getItemRelations($aRow['concept'], 'related');
			}
		}

		if (!empty($aAltLabels)) {
			$oItem->altLabels = array_unique($aAltLabels);
		}
		if (!empty($aHiddenLabels)) {
			$oItem->hiddenLabels = array_unique($aHiddenLabels);
		}
		$oItem->searchLink = get_option('siteurl') . '?s=' . urlencode($oItem->prefLabel);
		$oItem->link = pp_thesaurus_link() . urlencode($oItem->prefLabel);

		return $oItem;
	}


	private function getItemRelations ($sUrn, $sRelType) {
		$sQuery = "
			PREFIX skos: <" . $this->sSkosUri . ">

			SELECT ?relation
			WHERE {
			  ?concept a skos:Concept .
			  ?relation a skos:Concept .
			  ?relation skos:prefLabel ?label .
			  ?relation skos:definition ?definition .
			  ?concept skos:$sRelType ?relation .
			  
			  FILTER (lang(?label) = '" . $this->sLanguage . "') .
			  FILTER (?concept = '$sUrn')
			}
		";

		$aRows = $this->oStore->query($sQuery, 'rows');

		if ($this->oStore->getErrors()) {
			throw new Exception ("Konnte Query nicht ausführen: $sQuery");
		}

		$aResult = array();
		foreach ($aRows as $aRow) {
			$aResult[] = $this->getItem($aRow['relation'], 'concept', false);
		}

		return $aResult;
	}


	private function getConcepts ($sSort='sortByCount' ) {
		if (empty($this->aList)) {
			$sQuery = "
				PREFIX skos: <" . $this->sSkosUri . ">

				SELECT DISTINCT ?concept ?label ?rel
				WHERE {
				  ?concept a skos:Concept .
			  	  ?concept skos:definition ?definition .
				  ?concept ?rel ?label .

					{ ?concept skos:prefLabel ?label . }
				  UNION
					{ ?concept skos:altLabel ?label . }
				  UNION
					{ ?concept skos:hiddenLabel ?label . }

				  FILTER (lang(?label) = '" . $this->sLanguage . "')
				}
			";

			$aRows = $this->oStore->query($sQuery, 'rows');

			if ($this->oStore->getErrors()) {
				throw new Exception ("Konnte Query nicht ausführen: $sQuery");
			}

			foreach ($aRows as $aRow) {
				if ($aRow['rel'] != $this->sSkosUri . 'hiddenLabel') {
					$aConcept['label'] = preg_replace('/ {2,}/', ' ', $aRow['label']);
					$aConcept['rel'] = $aRow['rel'];
					$aConcept['count'] = count(explode(' ', $aConcept['label']));
					$this->aList[] = $aConcept;
				}
			}
		}

		usort($this->aList, array($this, $sSort));

		return $this->aList;
	}


	private function sortByCount ($a, $b) {
		if ($a['count'] == $b['count']) {
			return 0;
		}
		return ($a['count'] < $b['count']) ? 1 : -1;
	}


	private function sortByLabel ($a, $b) {
		return strcasecmp($a['label'], $b['label']);
	}
}
