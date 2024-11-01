<?php

/**
 * Plugin Name: CrawlForMe
 *
 * Description: Check your website for broken links.
 *
 * Plugin URI: http://wordpress.org/plugins/wp-crawlforme/
 * Version: 1.0
 * Author: CrawlForMe <support@crawlforme.com>
 * Author URI: http://www.crawlforme.com/
 * @package wp-crawlforme
 * @copyright CrawlForMe, 2014
 */

/**
 * The instantiated version of this plugin's class
 */
$GLOBALS['wp_crawlforme'] = new wp_crawlforme;

function eee($x) {
	if (WP_DEBUG) {
		error_log($x);
	}
}

/**
 * CrawlForMe Wordpress plugin
 *
 * @package wp-crawlforme
 * @link http://wordpress.org/plugins/wp-crawlforme/
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @author CrawlForMe <support@crawlforme.com>
 */
class wp_crawlforme {
	/** This plugin's identifier */
	const ID = 'wp-crawlforme';
	const PREFIX = 'wp_crawlforme';

	/** Our option name for storing the plugin's settings */
	const OPTION_NAME = 'wp_crawlforme_options';

	/** This plugin's name */
	const NAME = 'CrawlForMe';

	/** This plugin's version */
	const VERSION = '1.0';

	/** REST api endpoint */
	const REST_ENDPOINT = 'http://api.crawlforme.com';
	
	/** Has the internationalization text domain been loaded? */
	protected $loaded_textdomain = false;

	/**
	 * This plugin's options, options from the database are merged on top of the default options.
	 * @see wp_crawlforme::set_options()  to obtain the saved settings
	 */
	protected $options = array();

	/** This plugin's default options */
	protected $options_default = array(
		'user_email' => null,
		'plugin_api_key' => null,
		'website_api_key' => null
	);
	
	/**
	 * Declares the WordPress action and filter callbacks
	 * @return void
	 * @uses wp_crawlforme::initialize()  to set the object's properties
	 */
	public function __construct() {
		eee('------------------------------------------------------------------------------------------');
		eee('wp_crawlforme: __construct');
		$this->initialize();

		if (is_admin()) {
			$this->load_plugin_textdomain();

			require_once dirname(__FILE__) . '/wp-crawllforme-admin.php';
			$admin = new wp_crawlforme_admin();

			// Configure properies depending on multisite
			if (is_multisite()) {
				$admin_menu = 'network_admin_menu';
				$plugin_action_links = 'network_admin_plugin_action_links_wp-crawlforme/wp-crawlforme.php';
			} else {
				$admin_menu = 'admin_menu';
				$plugin_action_links = 'plugin_action_links_wp-crawlforme/wp-crawlforme.php';
			}

			// Admin page rendering
			add_action('admin_init', array(&$admin, 'admin_init'));
			add_action('admin_notices', array(&$admin, 'admin_notices'));

			// Menu and link to setting page
			add_action($admin_menu, array(&$admin, 'admin_menu'));
			add_filter($plugin_action_links, array(&$admin, 'plugin_action_links'));

			// Plugin activation/deactivation
			register_activation_hook(__FILE__, array(&$admin, 'activate'));
			register_deactivation_hook(__FILE__, array(&$admin, 'deactivate'));
			// uninstall is never called :'(
			//register_uninstall_hook(__FILE__, array(&$admin, 'uninstall'));
		}
	}

	protected function initialize() {
		eee('wp_crawlforme: initialize');
		$this->set_options();
	}

	/**
	 * Sanitizes output via htmlspecialchars() using UTF-8 encoding
	 *
	 * Makes this program's native text and translated/localized strings
	 * safe for displaying in browsers.
	 *
	 * @param $in the string to sanitize
	 * @return the sanitized string
	 */
	protected function hsc_utf8($in) {
		return htmlspecialchars($in, ENT_QUOTES, 'UTF-8');
	}
	
	/**
	 * Replaces all whitespace characters with one space
	 * @param $in the string to clean
	 * @return the cleaned string
	 */
	protected function sanitize_whitespace($in) {
		return preg_replace('/\s+/u', ' ', $in);
	}

	/**
	 * A centralized way to load the plugin's textdomain for internationalization
	 */
	protected function load_plugin_textdomain() {
		if (!$this->loaded_textdomain) {
			load_plugin_textdomain(self::ID, false, self::ID . '/languages');
			$this->loaded_textdomain = true;
		}
	}
	
	protected function get_plugin_title() {
		return __('CrawlForMe, Broken Links Checker', self::ID);
	}

	/**
	 * Replaces the default option values with those stored in the database
	 * @uses login_security_solution::$options to hold the data
	 */
	protected function set_options() {
		eee('wp_crawlforme: set_options');
		if (is_multisite()) {
			switch_to_blog(1);
			$options = get_option(self::OPTION_NAME);
			restore_current_blog();
		} else {
			$options = get_option(self::OPTION_NAME);
		}
		
		if (!is_array($options)) {
			$options = array();
		}
		
		$this->options = array_merge($this->options_default, $options);
		eee(' + options: '.print_r($this->options, true));
	}
	
	/**
	 * Proceed the registration query for an new or existing user.
	 * @return an array containing a status code and a JSON as the response which will only be defined if the query is not a success.
	 */
	protected function queryRegister($email, $key = null){
		@require_once('lib/RestClient.class.php');

		eee("[*] REST Processing queryRegister ...");
		
		$request = array(
			'email' => $email,
			'website' => get_option('siteurl'),
			'timeZone' => get_option('timezone_string'),
			'apiKey' => $key
		);

		eee("   + Query: ".print_r($request, true));
		
		$ex = RestClient::post( self::REST_ENDPOINT . '/register.json', json_encode($request), null, null, "application/json" );
		$code = $ex->getResponseCode();
		eee("   + Code: ".$code);
		
		$r = json_decode($ex->getResponse());
		eee("   + Response: ".print_r($r, true));
		
		return array(
			'status'   => $code,
			'response' => $r
		);
	}
	
	/**
	 * Proceed the overview query to gather general information to be displayed on the main plugin screen.
	 * @return an array containing a status code and a JSON as the response which will only be defined if the query is a success.
	 */
	protected function queryOverview() {
		@require_once('lib/RestClient.class.php');

		eee("[*] REST Processing queryResults ...");
		
		// $this->options is empty ??
		$options = get_option(self::OPTION_NAME);
		$request = array(
			'apiKey' => $options['website_api_key']
		);
		
		eee("   + Query: ".print_r($request, true));
		
		$ex = RestClient::post( self::REST_ENDPOINT . '/overview.json', json_encode($request), null, null, "application/json" );
		$code = $ex->getResponseCode();
		eee("   + Code: ".$code);
		
		$r = json_decode($ex->getResponse());
		eee("   + Response: ".($r == null ? "NULL" : print_r($r, true)));
		
		return array(
			'status'   => $code,
			'response' => $r
		);
	}
	
	 /**
	 * Proceed the start task query to execute an instant crawl.
	 * @return an array containing a status code and a JSON with the error which will only be defined if the query is a success.
	 */
	protected function queryStartTask() {
		@require_once('lib/RestClient.class.php');

		eee("[*] REST Processing queryStartTask ...");
		
		// $this->options is empty ??
		$options = get_option(self::OPTION_NAME);
		$request = array(
			'apiKey' => $options['website_api_key']
		);
		
		eee("   + Get options of: ".$this->option_name);
		eee(print_r($options, true));
		
		eee("   + Query: ".print_r($request, true));
		
		$ex = RestClient::post( self::REST_ENDPOINT . '/start.json', json_encode($request), null, null, "application/json" );
		$code = $ex->getResponseCode();
		eee("   + Code: ".$code);
		
		$r = null;
		if ($code != 200) {
			// Only fetch response body in case of error
			$r = json_decode($ex->getResponse());
		}
		
		eee("   + Response: ".($r == null ? "NULL" : print_r($r, true)));
		
		return array(
			'status'   => $code,
			'response' => $r
		);
	}
	
	/**
	 * Handle the validation error messages. If there is only one message return it as is
	 * otherwise build and return an unorderd list of message.
	 * @param fieldErrors array containing at least one error message 
	 * @param $message a facultative message that will prepend the error message/list
	 */
	protected function handleValidationException($fieldErrors, $message=null) {
		$html = '';
		if (!empty($fieldErrors)) {
			if (count($fieldErrors) > 1) {
				$html = '<ul>';
				foreach ($data['response']->fieldErrors as $error) {
					$html .= '<li>'. $error->message .'</li>';
				}
				$html .= '</ul>';		
			} else {
				$html = $fieldErrors[0]->message;
			}
		}
		return $message == null ? $html : '<b>'. $this->hsc_utf8($message) . ':</b> ' . $html;
	}
}