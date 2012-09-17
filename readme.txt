=== PoolParty Thesaurus ===
Author URI: http://www.semantic-web.at/users/kurt-moser
Plugin URI: http://poolparty.biz
Contributors: kurt-moser
Tags: poolparty, thesaurus, glossary, skos, rdf
Requires at least: 2.9
Tested up to: 2.9
Stable tag: trunk


PoolParty plugin makes websites more understandable. Blogs benefit from linking posts with key terms automatically. The plugin uses SKOS vocabularies.


== Description ==
PoolParty thesaurus plugin helps to make Wordpress blogs and websites more understandable. The website will be improved by linking posts with key terms and key terms with other key terms. The plugin imports a controlled vocabulary (SKOS thesaurus) or retrieves a thesaurus from a (public) SPARQL endpoint via web. Based on the thesaurus, terms in articles are high-lighted automatically, definitions are provided "inline" (as mouse-over effect) and links to the thesaurus terms are generated on-the-fly. The thesaurus is available as an extra resource on the blog and can be navigated to learn more about the knowledge domain instantly. The plugin also works with multilingual blogs. Dbpedia (Linked data) is used to retrieve definitions automatically whenever the imported thesaurus lacks of definitions.

With this plugin any SKOS/RDF thesaurus can be imported or retrieved from a SPARQL endpoint and used within your Wordpress blog to underpin articles with key terms.

On two extra pages (which are generated automatically) the thesaurus can be displayed and used as a browsable glossary on your blog. The main page of the glossary displays all concepts with their preferred labels and their alternative labels (synonyms). The list of concepts is displayed in an alphabetical order and can be filtered by their first letters. The second page represents the detail view of each concept. All kinds of labels and relations (prefLabel, altLabel, hiddenLabel, definition, scopNote, broader, narrower und related) of a given term (concept) can be loaded and displayed.

Each post is analysed automatically to find words and phrases matching labels of a concept (prefLabel, altLabel or hiddenLabel) in the thesaurus. The first hit will be highlighted automatically. A mousover tooltip shows the short description of the term/phrase and the link points to the more detailed description on the glossary page.

= What's new? =
* Updating the plugin via the wordpress admin interface has been simplified. The plugin now gets the ARC2-tripelstore and installs it automatically without need to intervene  manually.
* There is a new sidebar-widget which incorporates a search field including autocomplete. This autocomplete service suggests terms from the glossary. Once such a term is chosen, one is automatically connected to the webpage describing the term. The widget can be pulled into any sidebar (depending on the theme) from the sub-section of *appearance/widgets*.
* There is a new shortcode with which specific parts of the content can be excluded from automatically being linked. The shortcode is called ppt-noparse, and it is opened with *[ppt-noparse]* and closed with *[/ppt-noparse]*. Automatic linking is disabled for any text between the code.
* Automatic finding and linking of concepts in running content can be totally disabled under settings. The glossary area is still present and can be reached via the glossary link and the sidebar widget.

Thanks to Benjamin Nowack: The thesaurus is imported into the system and is queried via ARC2 (https://github.com/semsol/arc2).
Thanks to rduffy (http://wordpress.org/extend/plugins/profile/rduffy). His 'Glossary' Plugin (http://wordpress.org/extend/plugins/automatic-glossary) inspired me, and I was able to develop this plugin on top of his ideas.

Works with PHP 5, MySQL 5 und ARC2


== Installation ==

Install using WordPress:

1. Log in and go to *Plugins* and click on *Add New*.
2. Search for *poolparty thesaurus* and hit the *Install Now* link in the results. WordPress will install it.
3. From the Plugin Management page in Wordpress, activate the *PoolParty Thesaurus* plugin.
4. Go to *Settings* -> *PoolParty Thesaurus* in Wordpress, specify a RDF/XML file or SPARQL endpoint and click on *Import/Update Thesaurus*. Uploading the thesaurus can take a few minutes. Please remain patient and do not interrupt the procedure.

Install manually:

1. Download the plugin zip file and unzip it.
2. Upload the plugin contents into your WordPress installation*s plugin directory on the server. The plugin*s .php files, readme.txt and subfolders should be installed in the *wp-content/plugins/poolparty-thesaurus/* directory.
3. Download ARC2 from https://github.com/semsol/arc2 and unzip it.
4. Open the unziped folder and upload the entire contents into the */wp-content/plugins/poolparty-thesaurus/arc/* directory.
5. From the Plugin Management page in Wordpress, activate the *PoolParty Thesaurus* plugin.
6. Go to *Settings* -> *PoolParty Thesaurus* in Wordpress, specify a RDF/XML file or SPARQL endpoint and click on *Import/Update Thesaurus*. Uploading the thesaurus can take a few minutes. Please remain patient and don*t interrupt the procedure.


For users, who have installed version 1.x already:

Version 2.0 was reworked completely, thus take in mind:

* Please deactivate the older version and delete */wp-content/plugins/poolparty-thesaurus/* folder
* Please delete the page *Thesaurus* and the page called *Item*
* You can also delete the template file *pp-thesaurus-template.php* from your active theme folder. There is no use for this anymore.



== Frequently Asked Questions ==

= Does my automatically generated glossary page need to be titled **Glossary**? =
No. It can be called whatever you like. You can enter a content if you like, but be careful with the shortcuts.

= Does my automatically generated subpage need to be titled **Item**? =
No. It can be called whatever you like. You can enter a content if you like, but be careful with the shortcuts.

= How do I add a thesaurus item?  =
You will need a SKOS thesaurus management tool like PoolParty (http://poolparty.biz) to add/modify terms. The glossary is generated automatically from the imported thesaurus or (public) SPARQL endpoint.

= Where can I find SKOS thesauri on the web? =
A good source for this is CKAN, see: http://ckan.net/package?tags=format-skos

= How con I exclude certain text sections from parsing? =
Enclose such text sections with preceding [ppt-noparse] and a final [/ppt-noparse]

= How can I update the glossary? =
Simply load the updated thesaurus again (admin area: *Settings* -> *PoolParty Thesaurus*). The old thesaurus will be overwritten. New or updated concepts will be recognized immediately by the link generator.

= How to I style the tooltip? =
The tooltip consists of a CSS file and three PNG pictures which can be found in the plugin directory (*js/unitip/*). Two pictures consist of the top and bottom edge with without the pointer and the third picture consists of the middle part. To style this tooltip, the three pictures can be interchanged and the CSS file adjusted accordingly.



== Screenshots ==

1. Tooltip with the description of a concept
2. The detail page of a concept
3. Admin settings page


== Changelog ==
= 2.4 - 17.09.2012 =
* Small bug fix

= 2.3 - 19.01.2012 =
* Improved the autocomplete field (sidebar-widget)
* Performance improvements
* Bugfix for the wordpress version 3.3.1

= 2.2.2 - 01.12.2011 =
* Fixed small error in the header title

= 2.2.1 - 30.11.2011 =
* Few bugfixes

= 2.2 - 29.11.2011 =
* Updating the plugin via the wordpress admin interface has been simplified. The plugin now gets the ARC2-tripelstore and installs it automatically without need to intervene  manually.
* There is a new sidebar-widget which incorporates a search field including autocomplete. This autocomplete service suggests terms from the glossary. Once such a term is chosen, one is automatically connected to the webpage describing the term. The widget can be pulled into any sidebar (depending on the theme) from the sub-section of *appearance/widgets*.
* There is a new shortcode with which specific parts of the content can be excluded from automatically being linked. The shortcode is called ppt-noparse, and it is opened with *[ppt-noparse]* and closed with *[/ppt-noparse]*. Automatic linking is disabled for any text between the code.
* Automatic finding and linking of concepts in running content can be totally disabled under settings. The glossary area is still present and can be reached via the glossary link and the sidebar widget.
* The procedure for the automatic linking has been stabilized and improved
* Bugfixes

= 2.1 - 29.07.2011 =
* Few bugfixes

= 2.0 - 28.07.2011 =
* Plugin has been re-worked from scratch
* Definitions are displayed as tooltip via mouseover (can be dectivated)
* Plugin can be used in multilingual Wordpress blogs. Pre-condition is to use another plugin called qTranslate (http://wordpress.org/extend/plugins/qtranslate/) by chineseleper
* Thesaurus can also be retrieved/updated via (public) SPARQL-Endpoint
* If definition of a concept is missing it will be retrieved automatcally from DBPedia (via SPARQL), pre-condition is that concept is mapped via close or exact match with corresponding DBPedia resource.

= 1.2 - 21.04.2011 =
* Few bugfixes

= 1.1 - 13.04.2011 =
* Bugfix: Compat fix for upgrade
* Changed: Only concepts with a definition are displayed on the thesaurus pages.

= 1.0 =
* First plugin version

