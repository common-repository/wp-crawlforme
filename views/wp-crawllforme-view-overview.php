<?php
require_once('wp-crawlforme-graph.php');

/**
 * CrawlForMe Overview dashboard
 *
 * @package wp-crawlforme
 * @link http://wordpress.org/plugins/wp-crawlforme/
 * @author CrawlForMe <support@crawlforme.com>
 * @copyright CrawlForMe, 2014
 */
class wp_crawlforme_admin_overview extends wp_crawlforme {
	
	/** Object containing statistics of the last report. */
	private $stats;
	/** Object containing information about the scheduled tasks (previous, current and next). */
	private $scheduling;
	/** Fully built link to access part of the CrawlForMe plateform. */
	private $links;
	/** Number of manual crawl left. */
	private $manualCrawlLeft;
	/** Date upon which the number of crawl left will be reset. */
	private $manualCrawlDate;
	
	public function __construct() {
		$this->initialize();

		$data = $this->queryOverview();
		$status = $data['status'];
	
		$this->links = $data['response']->links;
		$this->stats = $data['response']->statistics;
		$this->scheduling = $data['response']->scheduling;
		$this->manualCrawlLeft = $data['response']->manualCrawlLeft;
		$this->manualCrawlDate = $data['response']->manualCrawlDate;
		
		if ($status != 200) {
			add_meta_box('dashboard_overview_api_error', 		__('CrawlForMe API Error', self::ID), array(&$this, 'meta_box_api_error'), 'crawlforme_overview', 'left', 'core');
			add_meta_box('dashboard_overview_help', 			__('Useful Links', self::ID), array(&$this, 'meta_box_help'), 'crawlforme_overview', 'right', 'core');
		} else {
			/* Define all meta box, example: */
			// add_meta_box(id, title, callback, 'target screen', context, priority, callback args)
			
			/* Left */
			if ($this->scheduling->lastReport != null){
				add_meta_box('dashboard_overview_report', 		__('Overview of your last report', self::ID), array(&$this, 'meta_box_last_report'), 'crawlforme_overview', 'left', 'core');
			}
			add_meta_box('dashboard_overview_start_task',		__('Manual crawl', self::ID), array(&$this, 'meta_box_start_task'), 'crawlforme_overview', 'left', 'core');
			//add_meta_box('dashboard_overview_reset',			__('Reset', self::ID), array(&$this, 'meta_box_reset'), 'crawlforme_overview', 'left', 'core');
			
			//* Right */
			add_meta_box('dashboard_overview_about', 			__('About', self::ID), array(&$this, 'meta_box_about'), 'crawlforme_overview', 'right', 'core');
			add_meta_box('dashboard_overview_help', 			__('Useful Links', self::ID), array(&$this, 'meta_box_help'), 'crawlforme_overview', 'right', 'core');
			add_meta_box('dashboard_overview_retreive_access',	__('Retrieve access', self::ID), array(&$this, 'meta_box_retreive_access'), 'crawlforme_overview', 'right', 'core');
		}


		?>
		<div class="wrap c4me-wrap">
			<div id="icon-wp-crawlforme" class="icon32"><br /></div>
			<h2><?php echo $this->get_plugin_title(); ?></h2>
			
			<div id="dashboard-widgets-wrap" class="crawlforme-overview">
				<div id="dashboard-widgets" class="metabox-holder">
					<div id="post-body">
						<div id="dashboard-widgets-main-content">
							<div class="postbox-container" style="width:49%;">
								<?php do_meta_boxes('crawlforme_overview', 'left', ''); ?>
							</div>
							<div class="postbox-container" style="width:49%;">
								<?php do_meta_boxes('crawlforme_overview', 'right', ''); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(document).ready( function($) {
				// postboxes setup
				postboxes.add_postbox_toggles('crawlforme_overview');
				
				$('.meta-box-sortables').sortable({
			        disabled: true
			    });
			
			    $('.postbox .hndle').css('cursor', 'default')
			    	.unbind('click').siblings('.handlediv').remove();
			});
		</script>
		<?php
	}
	
	public function meta_box_last_report(){
		?><div><?php
		
		if ($this->scheduling->lastReport != null){
			$graph = new wp_crawlforme_graph($this->stats);
			
			?>
			<div style="float: left; margin-top: 18px;">
				<?php echo sprintf(__('Last report completed on: <b>%s</b>', self::ID), $this->scheduling->lastReport); ?>
			</div>
			<p style="text-align: right;">
				<input type="button" name="consult-report" id="consult-report" class="button button-primary" value="<?php _e('Consult the full report', self::ID); ?>" onclick="window.open('<?php echo $this->links->consult_report; ?>'); return false;" />
			</p>
			<p>
				<?php _e('Connect to the <a href="http://www.crawlforme.com" target="_blank">CrawlForme platform</a> to consult previous reports, to modify options and to discover the CrawlForMe features.', self::ID); ?>
			</p>
			<?php
		} else {
			echo '<p>'.__('No report is currently available.', self::ID) . '</p>';
		}
		
		?></div><?php
	}
	public function meta_box_start_task() {
		$disabled = $this->scheduling->nextReport != null || $this->scheduling->currentReport ? 'disabled="disabled"' : '';
		?>
		<div>
			<?php
				$allowCrawl = false;
			 	if ($this->scheduling->currentReport == 1) {
			 		// A report is beeing crawled
					$description = __('<b>Your website is being crawled ...</b>', self::ID).'<br /><br />'
						. __('You will be <b>notified by email</b> when the job is done.', self::ID) .'<br />'
						. __('Come back to this plugin page to consult the results.', self::ID) ;
				} else if ($this->scheduling->nextReport != null) {
					// A report is scheduled a is going to start in a few moments
					$description = __('<b>Your website is being crawled ...</b>', self::ID).'<br /><br />'
						. __('You will be <b>notified by email</b> when the job is done.', self::ID) .'<br />'
						. __('Come back to this plugin page to consult the results.', self::ID) ;
				} else {
					// No report in progress
					if ($this->manualCrawlLeft <= 0){
						$description = __('Unfortunately you have no more manual crawl left for this month.', self::ID) . '<br />'
									 . sprintf(__('Come back on <b>%s</b> the amount of manual crawl will be reset or subscribe to one of our plans.', self::ID), $this->manualCrawlDate);
					} else {
						$allowCrawl = true;
						$description = __('Click the button below to begin the crawl of your website in a few moment.', self::ID) . ' ' 
									 . __('You will be <b>notified by email</b> when the job is done.', self::ID);
					}
				}
				?>
				
				<span class="description"><?php echo $description; ?></span>
				<?php 
				if ($allowCrawl){
					?>
					<form method="post">
						<?php  wp_nonce_field('c4me_create_nonce_overview','c4me_start_task'); ?>
						<input type="hidden" name="start_task" value="1" />
						<p style="float: left; margin-top: 16px;"><?php echo sprintf(__('You have <b>%s</b> manual crawl(s) left to use.', self::ID), $this->manualCrawlLeft); ?></p>
						<p style="float: right;">
							<input type="submit" value="<?php _e('Crawl my website', self::ID); ?>" name="submit_start_task" class="button-primary" id="submit_start_task" <?php echo $disabled; ?>/>
						</p>
					</form>
					<div style="clear: both;"></div>
					<?php
				}
			?>
		</div>
		<?php
	}
	public function meta_box_reset(){
		?>
		<div>
			<span class="description">Press the button below to reset your account (you'll need to create a new account in order to use this plugin again).<br /><br /><b>For dev only !</b></span><br />
			<br />Current settings:<pre><?php print_r($this->options); ?></pre><br />
			<br />Current response:<b>Statistics</b><pre><?php print_r($this->stats); ?></pre>
			<br /><b>Scheduling</b><pre><?php print_r($this->scheduling); ?></pre>
			<br /><b>Links</b><pre><?php print_r($this->links); ?></pre>
			<form method="post" style="margin-top:15px">
				<?php 
				wp_nonce_field('c4me_create_nonce_overview','c4me_reset'); ?>
				<input type="hidden" name="reset" value="1" />
				<p style="text-align: right;">
					<input type="button" value="Reset plugin settings" name="submit_reset" class="button-secondary" id="submit_reset" />
					<script type="text/javascript">
						jQuery(function($) {
							$('#submit_reset').click(function(e){
								e.preventDefault();
								if (confirm('Are you sure you want to wipe out the settings of the plugin? This cannot be undone !')) {
								    $(this).closest('form').submit();
								}
							});
						});
					</script>
				</p>
			</form>
		</div>
		<?php
	}
	public function meta_box_help(){
		?>
		<div>
			<table class="form-table crawlforme_links">
				<tr valign="top">
					<td class="c4me-c16"><a href="http://www.crawlforme.com" target="_blank"><?php _e('CrawlForMe Website', self::ID);?></a></td>
					<td class="c4me-help"><a href="http://www.crawlforme.com/blog" target="_blank"><?php _e('Blog', self::ID);?></a></td>
				</tr>
				<tr valign="top">
					<td class="c4me-faq"><a href="http://www.crawlforme.com/blog/faq" target="_blank"><?php _e('FAQ', self::ID);?></a></td>
					<td class="c4me-tutorial"><a href="http://www.crawlforme.com/blog/tutorial" target="_blank"><?php _e('Tutorial', self::ID);?></a></td>
				</tr>
			</table>
		</div>
		<?php
	}
	public function meta_box_about(){
		?>
		<div>
			<span class="description">
				<?php _e('CrawlForMe offers you <b>two manual crawls each month</b> which can be started whenever you want.', self::ID); ?>
				<br />
				<?php _e('<b>As a trial user, you are subject to other limitations</b> such as the maximum number of resources crawled or key features (handling of ignored links, protected pages, forms, cookie and so on).', self::ID); ?>
				<br /><br />
				<?php _e('If your are interested by <b>unlocking the full potential of this tool subscribe to one of our plans</b> and enjoy all thoses advantages.', self::ID); ?>
			</span>
	
			<p style="text-align: left;">
				<input type="button" name="see-tarification-plan" id="see-tarification-plan" class="button button-secondary" value="<?php _e('See our tarification plans', self::ID); ?>" onclick="window.open('<?php echo $this->links->tarification_plan; ?>'); return false;" />
			</p>
		</div>
		<?php
	}
	public function meta_box_retreive_access(){
		?>
		<div>
			<span class="description">
				<?php _e('Did you forget your plugin API key to connect other Wordpress to your account?', self::ID); ?>
				<br />
				<?php _e('Did you miss the email containing you login and password details to access the CrawlForMe plateform?', self::ID); ?>

			</span>
			
			<p style="text-align: left;">
				<input type="button" name="retreive-api-key" id="retreive-api-key" class="button button-secondary" value="<?php _e('Retrieve API key', self::ID); ?>" onclick="window.open('<?php echo $this->links->retreive_api_key; ?>'); return false;" />
				<input type="button" name="retreive-access" id="retreive-access" class="button button-secondary" value="<?php _e('Retrieve access', self::ID); ?>" onclick="window.open('<?php echo $this->links->retreive_access; ?>'); return false;" />
			</p>
		</div>
		<?php
	}
	public function meta_box_api_error(){
		?>
		<div>
			<div style="float: left; width: 50px; height: 72px;" class="c4me-error">&nbsp;</div>
			<p><?php 
				_e('The CrawlForMe API seems to be down at the moment.', self::ID); 
				?><br /><?php 
				_e('The good news is that you\'re still able to consult your report by accessing directly to the <a href="http://www.crawlforme.com/">plateform</a>. ', self::ID);
				_e('If the problem persist, feel free to <a href="mailto:info@cralwforme.com">contact us</a>.', self::ID);
			?></p>
			<div style="clear:both;"></div>
		</div>
		<?php
	}
}
