<?php
/*
Template Name: PoolParty Thesaurus
*/


$oPPGM = PPThesaurusManager::getInstance();
try {
	$oItem = $oPPGM->getItem($_GET['label']);
} catch (Exception $e) {
	get_header();
?>
	<div id="content" class="narrowcolumn">
		<h2 class="center"><?php _e('Fehler beim Auslesen der Daten', 'Poolparty Thesaurus'); ?></h2>
	</div>
<?php
	get_sidebar();
	get_footer();
	exit();
}

// MZ 201003021154
$_SESSION['itemLabel'] = $oItem->prefLabel;

get_header();?>

<div id="wrapper">
<?php if (is_null($oItem)) : ?>

	<div id="content" class="narrowcolumn">
		<h2 class="center"><?php _e('Glossar-Wort nicht vorhanden', 'Poolparty Thesaurus'); ?></h2>
	</div>


<?php else: ?>

	<div id="content" class="narrowcolumn" role="main">
	<?php if (have_posts()): while (have_posts()): the_post(); ?>
		<div class="post" id="post-<?php the_ID(); ?>">
		<h2><?php echo $oItem->prefLabel; ?></h2>
			<div class="entry">
				<?php the_content('<p class="serif">' . __('Read the rest of this page &raquo;', 'kubrick') . '</p>'); ?>
				<?php if ($oItem->altLabels): ?>
					<p><b>Alternative Label:</b> <?php echo implode(', ', $oItem->altLabels); ?></p>
				<?php endif; ?>
				<?php if ($oItem->hiddenLabels): ?>
					<p><b>Hidden Label:</b> <?php echo implode(', ', $oItem->hiddenLabels); ?></p>
				<?php endif; ?>
				<?php if ($oItem->definition): ?>
					<p><?php echo $oItem->definition; ?></p>
				<?php endif; ?>
				<?php if ($oItem->scopeNote): ?>
					<blockquote><?php echo $oItem->scopeNote; ?></blockquote>
				<?php endif; ?>
				<?php if ($oItem->broaderList):?>
					<p><b>Broader:</b> <?php echo implode(', ', pp_thesaurus_to_link($oItem->broaderList)); ?></p>
				<?php endif; ?>
				<?php if ($oItem->narrowerList):?>
					<p><b>Narrower:</b> <?php echo implode(', ', pp_thesaurus_to_link($oItem->narrowerList)); ?></p>
				<?php endif; ?>
				<?php if ($oItem->relatedList):?>
					<p><b>Related:</b> <?php echo implode(', ', pp_thesaurus_to_link($oItem->relatedList)); ?></p>
				<?php endif; ?>
				<p><b>Search for</b> <a href="<?php echo $oItem->searchLink; ?>" alt="Search for <?php echo $oItem->prefLabel; ?>"><?php echo $oItem->prefLabel; ?></a></p>
			</div>
		</div>
	<?php endwhile; endif; ?>
	<?php edit_post_link(__('Edit this entry.', 'kubrick'), '<p>', '</p>'); ?>
	</div>

<?php endif; ?>



<?php get_sidebar(); ?>
</div>
<?php get_footer(); ?>

