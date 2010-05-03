=== PoolParty Thesaurus ===
Contributors: kurt-moser
Donate link: http://poolparty.punkt.at
Tags: poolparty, thesaurus, glossary, skos, rdf
Requires at least: 2.9
Tested up to: 2.9
Stable tag: trunk


This plugin imports a SKOS thesaurus via ARC2 and generates links automatically in any page which contains terms from the thesaurus.


== Description ==

With this plugin any SKOS thesaurus can be imported into your Wordpress blog (via ARC2 into an RDF triple store) and with only two configuration pages the whole thesaurus can be displayed and used as a glossary on your homepage. One page which has to be configured is the main page of the glossary which displays all concepts with their preferred labels and their alternative labels (synonyms). The list of concepts is displayed in an alphabetical order. Concepts can be filtered by their first letters. The second page which has to be configured represents the detail view of each concept. This template is pre-configured and can be matched with individual needs. All kinds of labels and relations (prefLabel, altLabel, hiddenLabel, definition, scopNote, broader, narrower und related) of a given term (concept) can be loaded and displayed. 
Each Wordpress page is analysed by the plugin to find out if phrases occur which are also labels of a concept (prefLabel, altLabel or hiddenLabel) from the thesaurus. The first hit will generate automatically links to the corresponding concept.

Thanks to Benjamin Nowack: The thesaurus is imported and into the system and is queried via ARC2 (http://arc.semsol.org/).
Thanks to rduffy (http://wordpress.org/extend/plugins/profile/rduffy). His 'Glossary' Plugin (http://wordpress.org/extend/plugins/automatic-glossary) inpired me, and I was able to develop this plugin on top of his ideas.

Works with PHP 5, MySQL 5 und ARC2



== Installation ==

1. Download the plugin zip file
1. Upload the plugin contents into your WordPress installation's plugin directory.
1. The plugin's .php files, readme.txt and folders should be installed in the 'wp-content/plugins/pp-thesaurus/' directory. 
1. Move the 'pp-thesaurus-template.php' file into your active theme direcory
1. Download ARC2 from http:/arc.semsol.org/download.
1. Upload the ARC files and folders into '/wp-content/plugins/pp-thesaurus/arc/' directory.
1. From the Plugin Management page in Wordpress, activate the 'PoolParty Thesaurus' plugin.
1. Create a main PoolParty Thesaurus page (example "Thesaurus") with or without body content.
1. Create a child page (example "Item") of the main PoolParty Thesaurus page with or without body content and take the 'PoolParty Thesaurus' template.
1. Go to 'Settings' -> 'PoolParty Thesaurus' in Wordpress, enter the main Thesaurus page's id# and import a SKOS/RDF file



== Frequently Asked Questions ==

= Does my main PoolParty Thesaurus page need to be titled "Thesaurus"? =

No. It can be called however you like. Just make sure to enter the page's id into the plugin's settings dashboard.

= Does my child page need to be titled "Item"? =

No. It can be called however you like.

= How do I add a thesaurus item?  =

Therefore you need a SKOS thesaurus management tool like PoolParty (http://poolparty.punkt.at/). The glossary is generated automatically from the imported thesaurus.

= How can I update the glossary? =

Simply load the updated thesaurus again (admin area - settings -> PoolParty Thesaurus). The old thesaurus will be overwritten. New or updated concepts will be recognized immediately by the link generator.



== Changelog ==

= 1.0 =
