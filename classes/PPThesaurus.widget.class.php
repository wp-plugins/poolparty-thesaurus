<?php

class PPThesaurusWidget extends WP_Widget {
	/**
   * @var string
   */
	protected $slug = 'pp-thesaurus';


	/**
	 * Register the widget
   */
	public function __construct() {
		$sTitle = __('Glossary Search', $this->slug);
		$sDescription = __('Search the glossary', $this->slug);
		parent::__construct('ppthesaurus_widget', $sTitle, array('description' => $sDescription));
	}

	public function widget( $aArgs, $aInstance ) {
		extract($aArgs, EXTR_SKIP);

		$sTitle = empty($aInstance['title']) ? '' : apply_filters('widget_title', $aInstance['title']);
		$sInfo = empty($aInstance['info']) ? '' : apply_filters('widget_info', $aInstance['info']);
		$sWidth = empty($aInstance['width']) ? '100%' : apply_filters('widget_width', $aInstance['width']);

		echo $before_widget;
		if (!empty($sTitle)) {
			echo $before_title . $sTitle . $after_title;
		}
		echo '
			<script type="text/javascript">
			//<![CDATA[
				var pp_thesaurus_suggest_url = "' . plugins_url('/pp-thesaurus-autocomplete.php', dirname(__FILE__)) . '";
			//]]>
			</script>
			<div class="PPThesaurus_sidebar"  style="width:' . $sWidth . '">
				<input id="pp_thesaurus_input_term" type="text" name="term" value="" title="' . $sInfo . '" />
			</div>
		';
		echo $after_widget;
		
	}

	public function form( $aInstance ) {
		$aDefaults = array(
			'title' => get_option('PPThesaurusSidebarTitle'),
			'info' => get_option('PPThesaurusSidebarInfo'),
			'width' => get_option('PPThesaurusSidebarWidth'),
		);
		$aInstance = wp_parse_args((array) $aInstance, $aDefaults);
	?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'pp-thesaurus'); ?>: <br />
			<input id="<?php echo $this->get_field_id('title'); ?>" class="widefat" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($aInstance['title']); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('info'); ?>"><?php _e('Info text', 'pp-thesaurus'); ?>: <br />
			<input id="<?php echo $this->get_field_id('info'); ?>" class="widefat" name="<?php echo $this->get_field_name('info'); ?>" type="text" value="<?php echo esc_attr($aInstance['info']); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width of the search field', 'pp-thesaurus'); ?>: <br />
			<input id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo  esc_attr($aInstance['width']); ?>" /></label> ('%' <?php _e('or', 'pp-thesaurus'); ?> 'px')
		</p>
	<?php

	}

	public function update( $aNewInstance, $aOldInstance ) {
		$aInstance = $aOldInstance;
		$aInstance['title'] = strip_tags($aNewInstance['title']);
		$aInstance['info'] = strip_tags($aNewInstance['info']);
		$aInstance['width'] = strip_tags($aNewInstance['width']);

		return $aInstance;
	}
}
