<?php

/**
 * CrawlForMe Registration form rendering
 *
 * @package wp-crawlforme
 * @link http://wordpress.org/plugins/wp-crawlforme/
 * @author CrawlForMe <support@crawlforme.com>
 * @copyright CrawlForMe, 2014
 * @deprecated this code is never called. For whatever reason, when using this class 
 * and processing a submit, the request will fail without any explanation ("Are you sure you want to do this ?").
 * Code has been moved in wp-crawllforme-admin.php
 */
class wp_crawlforme_admin_register extends wp_crawlforme {
	public function __construct() {
		if (is_multisite()) {
			// WordPress doesn't show the successs/error messages on
			// the Network Admin screen, at least in version 3.3.1,
			// so force it to happen for now.
			include_once ABSPATH . 'wp-admin/options-head.php';
		}

		?>
		<div class="wrap c4me-wrap">
			<div id="icon-wp-crawlforme" class="icon32"><br /></div>
			<h2><?php echo $this->get_plugin_title(); ?></h2>
		
			<form method="POST" action="options.php"> 
				<?php
					settings_fields($this->option_name);
					do_settings_sections(self::ID);
					submit_button();
				?>
			</form>
		</div>
		<?php
	}
}

?>