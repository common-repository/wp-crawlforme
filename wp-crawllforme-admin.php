<?php

/**
 * Administration section of the plugin.
 *
 * @package wp-crawlforme
 * @link http://wordpress.org/extend/plugins/wp-crawlforme/
 * @author CrawlForMe <support@crawlforme.com>
 * @copyright CrawlForMe, 2014
 */
class wp_crawlforme_admin extends wp_crawlforme {
	
	/** The WP privilege level required to use the admin interface. */
	protected $capability_required;

	/** Array of option field, for option form */
	protected $fields;
	/** Array of section, for option form */
	protected $sections;
	
	/**
	 * Sets the object's properties and options
	 * @return void
	 * @uses wp_crawlforme::initialize()  to set the object's properties
	 * @uses wp_crawlforme_admin::set_sections()  to populate the $sections property
	 * @uses wp_crawlforme_admin::set_fields()  to populate the $fields property
	 */
	public function __construct() {
		eee('wp_crawlforme_admin: __construct');
		$this->initialize();
		
		// Set the field and section to be used in the page_settings form
		$this->set_sections();
		$this->set_fields();

		// Translation already in WP combined with plugin's name.
		$this->text_settings = self::NAME . ' ' . __('Settings');

		if (is_multisite()) {
			$this->capability_required = 'manage_network_options';
		} else {
			$this->capability_required = 'manage_options';
		}
	}

	public function activate() {
		if (! current_user_can( 'activate_plugins' )){
	        return;
		}
	    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	    check_admin_referer( "activate-plugin_{$plugin}" );
		
		/* Save this plugin's options to the database. */
		if (is_multisite()) {
			switch_to_blog(1);
		}
		update_option(self::OPTION_NAME, $this->options);
		if (is_multisite()) {
			restore_current_blog();
		}
	}
	
	public function deactivate() {
		if (! current_user_can( 'activate_plugins' )){
	        return;
		}
	    $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
	    check_admin_referer( "deactivate-plugin_{$plugin}" );
		
		// Don't do anything on the deactivation
	}
	
	/** @deprecated never called even when hooked. */
	public function uninstall() {
		if (! current_user_can( 'activate_plugins' )){
	        return;
		}
	    check_admin_referer( 'bulk-plugins' );
	
	    // Important: Check if the file is the one
	    // that was registered during the uninstall hook.
	    if ( __FILE__ != WP_UNINSTALL_PLUGIN )
	        return;
	
	    // Don't keep the options
		if (is_multisite()) {
			switch_to_blog(1);
		}
		delete_option(self::OPTION_NAME);
		if (is_multisite()) {
			restore_current_blog();
		}
	}

	/**
	 * Build the sections for the registration form.
	 * @uses wp_crawlforme_admin::$sections to hold the data
	 */
	protected function set_sections() {
		eee('wp_crawlforme_admin: set_sections');
		$this->sections = array(
			'register' => array(
				'callback' => 'section_register'	// Callback to render the section
			)
		);
	}

	/**
	 * Build the fields for the registration form.
	 * @uses wp_crawlforme_admin::$fields to hold the data
	 */
	protected function set_fields() {
		eee('wp_crawlforme_admin: set_fields');
		$this->fields = array(
			'user_email' => array(
				'section' => 'register',				// Section group
				'label' => __("Email", self::ID),		// Label
				'text' => __("Email will be used to communicate CrawlForMe access and notifications when the crawl of your website is done.", self::ID),
				'type' => 'email',						// Field type
				'equiv' => 'user.email'					// The name of the field
			),
			'plugin_api_key' => array(
				'section' => 'register',
				'label' => __("Plugin API key", self::ID),
				'text' => __("Specify the plugin API key generated previously to add this website to your existing account. You can find it back in your profile page on the CrawlForMe plateform.", self::ID),
				'type' => 'apikey',
				'equiv' => 'apiKey'
			)
		);
	}

	/**
	 * A filter to add a "Settings" link in this plugin's description
	 *
	 * NOTE: This method is automatically called by WordPress for each
	 * plugin being displayed on WordPress' Plugins admin page.
	 *
	 * @param array $links the links generated thus far
	 * @return array
	 */
	public function plugin_action_links($links) {
		$links[] = '<a href="admin.php?page=' . self::ID . '">' . __('Settings') . '</a>';
		return $links;
	}

	/**
	 * Declares a menu item and callback for this plugin's settings page
	 *
	 * NOTE: This method is automatically called by WordPress when any admin page is rendered
	 */
	public function admin_menu() {
		eee('wp_crawlforme_admin: [hook] admin_menu');
		add_menu_page(
			$this->text_settings, 
			self::NAME, 
			$this->capability_required,
			self::ID,
			array(&$this, 'page_settings'), 
			plugins_url( self::ID . '/assets/crawly-27x27.png' )
		);
	}

	/**
	 * Declares the callbacks for rendering and validating this plugin's
	 * settings sections and fields
	 *
	 * NOTE: This method is automatically called by WordPress when any admin page is rendered
	 */
	public function admin_init() {
		eee('wp_crawlforme_admin: [hook] admin_init');

		register_setting(
			self::OPTION_NAME,
			self::OPTION_NAME,
			array(&$this, 'validate')
		);

		// Dynamically declares each section using the info in $sections.
		foreach ($this->sections as $id => $section) {
			add_settings_section(
				self::ID . '-' . $id,					// wp-crawlforme-register
				$this->hsc_utf8($section['title']),		// titre
				array(&$this, $section['callback']),	// rendering callback
				self::ID								// related page (wp-crawlforme)
			);
		}

		// Dynamically declares each field using the info in $fields.
		foreach ($this->fields as $id => $field) {
			add_settings_field(
				$id,									// field id
				$this->hsc_utf8($field['label']),		// titre
				array(&$this, $id),						// 
				self::ID,								// related page (wp-crawlforme)
				self::ID . '-' . $field['section']		// related section, wp-crawlforme-register
			);
		}
	}

	public function admin_notices() {
		eee('wp_crawlforme_admin: [hook] admin_notices');
		settings_errors( self::OPTION_NAME );
	}

	/**
	 * The callback for rendering the settings page
	 */
	public function page_settings() {
		eee('wp_crawlforme_admin: page_settings');
		
		//$view = null;
		wp_enqueue_style('crawlforme_admin_common', plugins_url( self::ID . '/views/common.css' ), false, $this->version, 'screen');
		
		if ($this->options['website_api_key'] == null) {
			// Use case #1
			// The user has not yet registered.
			//require_once('views/wp-crawllforme-view-register.php');
			//$view = new wp_crawlforme_admin_register($this);
			
			// Note: for whatever reason, using the wp-crawllforme-view-register.php does not work on submit
			// --> moving the code here
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
						settings_fields(self::OPTION_NAME);
						do_settings_sections(self::ID);
						submit_button($this->hsc_utf8(__("Start now", self::ID)));
					?>
				</form>
			</div>
			<?php
		} else {
			// Use case #2
			// The user is registered, display the overview
			wp_enqueue_script('postbox');
			wp_enqueue_style('crawlforme_admin_overview', plugins_url( self::ID . '/views/overview.css' ), false, $this->version, 'screen');
		
			if (isset($_POST['reset']) && $_POST['reset'] == 1) {
				if (!wp_verify_nonce($_POST['c4me_reset'], 'c4me_create_nonce_overview') 
						|| empty($_POST['_wp_http_referer']) 
						|| (isset($_SERVER['HTTP_REFERER']) && !strstr($_SERVER['HTTP_REFERER'], $_POST['_wp_http_referer']) )){
					wp_die("You do not have sufficient permissions to access this page.");
				}
				
				eee('Resetting options');
				eee(' + Current options: '.print_r($this->options, true));
				
				$this->options['user_email'] = null;
				$this->options['plugin_api_key'] = null;
				$this->options['website_api_key'] = null;
				
				update_option(self::OPTION_NAME, $this->options);
				
				echo '<div class="updated"><p>' . __("Plugin configuration reset, the page is reloading ...", self::ID) . '</p></div>';
				?>
					<script type="text/javascript">
						setTimeout(function () {
					       window.location.href = '<?php echo 'admin.php?page=' . self::ID; ?>';
					    }, 2000);
					</script>
				<?php
			} else if (isset($_POST['start_task']) && $_POST['start_task'] == 1) {
				if (!wp_verify_nonce($_POST['c4me_start_task'], 'c4me_create_nonce_overview') 
						|| empty($_POST['_wp_http_referer']) 
						|| ( isset($_SERVER['HTTP_REFERER']) && !strstr($_SERVER['HTTP_REFERER'], $_POST['_wp_http_referer']) )){
					wp_die("You do not have sufficient permissions to access this page.");
				}
						
				eee('Starting task');
				eee(' + Current options: '.print_r($this->options, true));
				
				// Process REST query
				$data = $this->queryStartTask();
				
				if ($data['status'] != 200) {
					if ($data['response'] != null && array_key_exists('fieldErrors', $data['response'])) {
						echo '<div class="error"><p>' . $this->handleValidationException($data['response']->fieldErrors, __("Unable to schedule the task", self::ID)) . '</p></div>';
					} else {
						echo '<div class="error"><p>' . __("An unexpected error prevented the schedule of a new task", self::ID) . '</p></div>';
					}
				} else {
					echo '<div class="updated"><p>' . __("Your crawl will begin in a few moment", self::ID) . '</p></div>'; 
				}
			}
			
			require_once('views/wp-crawllforme-view-overview.php');
			$view = new wp_crawlforme_admin_overview;
		}
	}
	
	/**
	 * The callback for rendering the "Register" section description
	 * @return void
	 */
	public function section_register() {
		echo '<br /><h2>'.$this->hsc_utf8(__("Welcome", self::ID)) . '</h2>'
		. '<p>' . __("We crawl your websites and report all broken links, missing pictures, obsolete documents and redirects that go wrong!", self::ID) . '</p>'
		. '<ul class="features">'
		. '<li>'. __("<b>Register below</b> with your email address and get <b>2 free analysis per month.</b>", self::ID).'</li>'
		. '<li>'. __("Your first analysis will start automatically.", self::ID).'</li>'
		. '<li>'. __("You will be <b>notified by email</b> when the job is done.", self::ID).'</li>'
		. '<li>'. __("Consult the results <b>directly from your CrawlForMe WordPress plugin</b>", self::ID).'</li>'
		. '<li>'. __("<b>Log in to CrawlForMe</b> to access the interactive online report and the code inspector", self::ID).'</li>' 
		. '</ul>'
		. '<br /><h2>'. __("Start now your first analysis", self::ID).'</h2>';
	}

	/**
	 * The callback for rendering the fields.
	 *
	 * @uses wp_crawlforme_admin::input_int()  		for rendering text input boxes for numbers
	 * @uses wp_crawlforme_admin::input_radio()  	for rendering radio buttons
	 * @uses wp_crawlforme_admin::input_string()	for rendering input fields
	 * @uses wp_crawlforme_admin::input_email()		for rendering email fields
	 */
	public function __call($name, $params) {
		eee("_CALL: ".print_r($name, true).", ".print_r($params, true));
		if (! empty($this->fields[$name]['type'])) {
			switch ($this->fields[$name]['type']) {
				case 'bool': 	$this->input_radio($name); break;
				case 'int': 	$this->input_int($name); break;
				case 'string': 	$this->input_string($name); break;
				case 'apikey': 	$this->input_apikey($name); break;
				case 'email': 	$this->input_email($name); break;
			}
		}
	}

	/**
	 * Renders the radio button inputs
	 */
	protected function input_radio($name) {
		echo $this->hsc_utf8($this->fields[$name]['text']) . '<br />';
		echo '<input type="radio" value="0" name="' . $this->hsc_utf8(self::OPTION_NAME) . '[' . $this->hsc_utf8($name) . ']"'
			. ($this->options[$name] ? '' : ' checked="checked"') . ' /> ';
		echo $this->hsc_utf8($this->fields[$name]['bool0']);
		echo '<br/>';
		echo '<input type="radio" value="1" name="' . $this->hsc_utf8(self::OPTION_NAME) . '[' . $this->hsc_utf8($name) . ']"'
			. ($this->options[$name] ? ' checked="checked"' : '') . ' /> ';
		echo $this->hsc_utf8($this->fields[$name]['bool1']);
	}

	/**
	 * Renders the text input boxes for editing integers
	 */
	protected function input_int($name) {
		echo '<input type="text" size="3" name="' . $this->hsc_utf8(self::OPTION_NAME) . '[' . $this->hsc_utf8($name) . ']"'
			. ' value="' . $this->hsc_utf8($this->options[$name]) . '" /> ';
		echo $this->hsc_utf8($this->fields[$name]['text']);
		
		if (array_key_exists($name, $this->options_default) && ! empty($this->options_default[$name])) {
			echo $this->hsc_utf8(' ' . __('Default:', self::ID) . ' ' . $this->options_default[$name] . '.');
		}
	}

	/**
	 * Renders the text input boxes for editing strings
	 */
	protected function input_string($name) {
		echo '<input type="text" name="' . $this->hsc_utf8(self::OPTION_NAME) . '[' . $this->hsc_utf8($name) . ']"'
			. ' value="' . $this->hsc_utf8($this->options[$name]) . '" style="width: 50em;" /> '
			. '<br />' . $this->hsc_utf8($this->fields[$name]['text']);
			
		if (array_key_exists($name, $this->options_default) && ! empty($this->options_default[$name])) {
			echo $this->hsc_utf8(' ' . __('Default:', self::ID) . ' ' . $this->options_default[$name] . '.');
		}
	}
	
	/**
	 * Renders the text input boxes which can be hidden
	 */
	protected function input_apikey($name){
		$option_name = $this->hsc_utf8(self::OPTION_NAME) . '[' . $this->hsc_utf8($name) . ']"';
		$option_value = $this->hsc_utf8($this->options[$name]);
		
		?>
		<script type="text/javascript">
			jQuery(function($) {
				$apiKeyToggle = $('#<?php echo self::ID . '-' . $name.'-toggle'; ?>');
				$apiKeyTarget = $('#<?php echo self::ID . '-' . $name.'-toggle-content'; ?>');
				
				$apiKeyToggle.change(function(e){
					checked = $(this).is(':checked');
					$apiKeyTarget.css({
						display: !checked ? 'none' : 'block'
					});
				});
				if ($apiKeyTarget.children(':input').val().length != 0) {
					$apiKeyToggle.trigger('click');
				}
			});
		</script>

		<input type="checkbox" id="<?php echo self::ID . '-' . $name.'-toggle'; ?>" />
		<label for="<?php echo self::ID . '-' . $name.'-toggle'; ?>"><?php _e("I am already registerd and have an API key", self::ID); ?></label>
		<br />
		<div id="<?php echo self::ID . '-' . $name.'-toggle-content'; ?>" style="display: none;">
			<input type="text" id="<?php echo self::ID . '-' . $name; ?>" name="<?php echo $option_name; ?>" value="<?php echo $option_value; ?>" style="width: 25em;" />
			<br />
			<?php
		
				echo $this->hsc_utf8($this->fields[$name]['text']);
			
				if (array_key_exists($name, $this->options_default) && ! empty($this->options_default[$name])) {
					echo $this->hsc_utf8(' ' . __('Default:', self::ID) . ' ' . $this->options_default[$name] . '.');
				}
			?>
		</div>
		<?php
	}
	
	/**
	 * Renders an email combo-field using a drop down and an editable field.
	 */
	protected function input_email($name){
		$option_name = $this->hsc_utf8(self::OPTION_NAME) . '[' . $this->hsc_utf8($name) . ']"';
		$option_value = $this->hsc_utf8($this->options[$name]);
		
		$admin_email = get_option('admin_email');
		$user_email  = wp_get_current_user()->user_email;
		
		if (empty($option_value) || $option_value == null){
			$option_value = $admin_email;
		}
		
		$option_type = $admin_email;
		if ($option_value != $admin_email && $option_value != $user_email) {
			$option_type = 'custom_email';
		} 
		
		?>
			<script type="text/javascript">
				jQuery(function($) {
					$toggle = $('#<?php echo self::ID . '-' . $name.'-toggle'; ?>');
					$input  = $('#<?php echo self::ID . '-' . $name; ?>');
					
					$toggle.val('<?php echo $option_type; ?>')
						   .change(function(e){
						if ($(this).val() == 'custom_email') {
							$input.val('').removeAttr('readonly').focus();
						} else {
							$input.val($(this).val()).attr('readonly', 'readonly');
						}
					}).trigger('change');
				});
			</script>
			<select id="<?php echo self::ID . '-' . $name.'-toggle'; ?>" style="width: 24.5em;" >
				<option value="<?php echo $admin_email; ?>">
					<i><?php _e('Administrator email', self::ID); ?></i>
				</option>
				<option value="<?php echo $user_email;  ?>">
					<i><?php _e('Your email', self::ID); ?></i>
				</option>
				<option value="custom_email">
					<?php _e('A custom email', self::ID); ?>
				</option>
			</select>
			<input type="text" id="<?php echo self::ID . '-' . $name; ?>" name="<?php echo $option_name; ?>" readonly="readonly" value="<?php echo $admin_email; ?>" style="width: 25em;" />
			<br />
		<?php 
			echo $this->hsc_utf8($this->fields[$name]['text']);
			
			if (array_key_exists($name, $this->options_default) && ! empty($this->options_default[$name])) {
				echo $this->hsc_utf8(' ' . __('Default:', self::ID) . ' ' . $this->options_default[$name] . '.');
			}
	}

	/**
	 * Validates the user input
	 *
	 * NOTE: WordPress saves the data even if this method says there are errors.
	 * So this method sets any inappropriate data to the default values.
	 *
	 * @param array $in  the input submitted by the form
	 * @return array  the sanitized data to be saved
	 */
	public function validate($in) {
		eee('wp_crawlforme_admin: validate');
		
		$out = $this->options_default;
		if (!is_array($in)) {
			// Not translating this since only hackers will see it.
			add_settings_error(self::OPTION_NAME,
					$this->hsc_utf8(self::OPTION_NAME),
					'Input must be an array.');
			return $out;
		}

		$gt_format = __("must be >= '%s',", self::ID);
		$default = __("so we used the default value instead.", self::ID);

		// Dynamically validate each field using the info in $fields.
		foreach ($this->fields as $name => $field) {
			if (!array_key_exists($name, $in)) {
				continue;
			}
			
			if (!is_scalar($in[$name])) {
				// Not translating this since only hackers will see it.
				add_settings_error(self::OPTION_NAME,
						$this->hsc_utf8($name),
						$this->hsc_utf8("'" . $field['label']) . "' was not a scalar, $default");
				continue;
			}

			switch ($field['type']) {
				case 'bool':
					if ($in[$name] != 0 && $in[$name] != 1) {
						// Not translating this since only hackers will see it.
						add_settings_error(self::OPTION_NAME,
								$this->hsc_utf8($name),
								$this->hsc_utf8("'" . $field['label'] . "' must be '0' or '1', $default"));
						continue 2;
					}
					break;
				case 'int':
					if (!ctype_digit($in[$name])) {
						add_settings_error(self::OPTION_NAME,
								$this->hsc_utf8($name),
								$this->hsc_utf8("'" . $field['label'] . "' "
										. __("must be an integer,", self::ID)
										. ' ' . $default));
						continue 2;
					}
					if (array_key_exists('greater_than', $field)
						&& $in[$name] < $field['greater_than']) {
						add_settings_error(self::OPTION_NAME,
								$this->hsc_utf8($name),
								$this->hsc_utf8("'" . $field['label'] . "' "
										. sprintf($gt_format, $field['greater_than'])
										. ' ' . $default));
						continue 2;
					}
					break;
				case 'email': 
					// Email validation is delegated to the API
					if (empty($in[$name])) {
						add_settings_error(self::OPTION_NAME,
								$this->hsc_utf8($name),
								$this->hsc_utf8("'" . $field['label'] . "' is mandatory"));
						continue 2;
					}
					break;
			}
			$out[$name] = $in[$name];
		}

		if (count(get_settings_errors()) == 0) {
			// If there is no error in the settings (invalid data), then proceed the REST query.
			
			// Process REST query
			$data = $this->queryRegister($out['user_email'], $out['plugin_api_key']);
			
 			// Handling validation errors received and map the error on the related field
			if ($data['status'] != 201) {
				if (array_key_exists('fieldErrors', $data['response'])) {
					$keys = array_keys ($this->fields);
					
					foreach ($data['response']->fieldErrors as $error) {
						$related = null;					
	
						// Find related field (local)
						for ($i=0,$len=count($keys); $i<$len && $related == null; $i++){
							$related = $this->fields[$keys[$i]]['equiv'] == $error->field ? $keys[$i] : null;
						}
						
						if ($related != null){
							add_settings_error(self::OPTION_NAME,
									$this->hsc_utf8($related),
									$this->hsc_utf8($error->message));
	
							unset($out[$related]);
						}
					}
				} else {
					eee("!!! unexpected exception occurred");
					add_settings_error(self::OPTION_NAME, $this->hsc_utf8(user_email), __('An unexpected error occurred', self::ID));
				}
			} else {
				// Everything went fine
				if (array_key_exists('apiKey', $data['response'])) {
					$out['website_api_key'] = $data['response']->apiKey;
				} else {
					// Invalid response
					eee("!!! apiKey NOT in response");
					add_settings_error(self::OPTION_NAME, 
							$this->hsc_utf8(user_email),
							__('An unexpected error occurred', self::ID) .' '
						  . __('preventing your account to be created.', self::ID));
				}
			}
		}

		return $out;
	}
}
