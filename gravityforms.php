<?php
/*
Plugin Name: Gravity Forms
Plugin URI: https://gravityforms.com
Description: Easily create web forms and manage form entries within the WordPress admin.
Version: 2.6.5
Requires at least: 4.0
Requires PHP: 5.6
Author: Gravity Forms
Author URI: https://gravityforms.com
License: GPL-2.0+
Text Domain: gravityforms
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009-2022 Rocketgenius, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see http://www.gnu.org/licenses.
*/

use Gravity_Forms\Gravity_Forms\TranslationsPress_Updater;
use Gravity_Forms\Gravity_Forms\Libraries\Dom_Parser;

//------------------------------------------------------------------------------------------------------------------
//---------- Gravity Forms License Key -----------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------
// If you hardcode a Gravity Forms License Key here, it will automatically populate on activation.
$gf_license_key = '';

//-- OR ---//

// You can also add the Gravity Forms license key to your wp-config.php file to automatically populate on activation
// Add the code in the comment below to your wp-config.php to do so:
// define('GF_LICENSE_KEY','YOUR_KEY_GOES_HERE');
//------------------------------------------------------------------------------------------------------------------

//------------------------------------------------------------------------------------------------------------------
//---------- reCAPTCHA Keys -----------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------
// If you hardcode your reCAPTCHA Keys here, it will automatically populate on activation.
$gf_recaptcha_private_key = '';
$gf_recaptcha_public_key  = '';

//-- OR ---//

// You can  also add the reCAPTCHA keys to your wp-config.php file to automatically populate on activation
// Add the two lines of code in the comment below to your wp-config.php to do so:
// define('GF_RECAPTCHA_SITE_KEY','YOUR_SITE_KEY_GOES_HERE');
// define('GF_RECAPTCHA_SECRET_KEY','YOUR_SECRET_KEY_GOES_HERE');
//------------------------------------------------------------------------------------------------------------------

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! defined( 'RG_CURRENT_PAGE' ) ) {
	/**
	 * Defines the current page.
	 *
	 * @since   Unknown
	 *
	 * @var string RG_CURRENT_PAGE The current page.
	 */
	define( 'RG_CURRENT_PAGE', basename( $_SERVER['PHP_SELF'] ) );
}

if ( ! defined( 'IS_ADMIN' ) ) {
	/**
	 * Checks if an admin page is being viewed.
	 *
	 * @since   Unknown
	 *
	 * @var boolean IS_ADMIN True if admin page. False otherwise.
	 */
	define( 'IS_ADMIN', is_admin() );
}

/**
 * Defines the current view within Gravity Forms.
 *
 * Defined from URL parameters.
 *
 * @since      Unknown
 * @deprecated 2.1.3.6
 *
 * @var string|boolean RG_CURRENT_VIEW The view if available.  False otherwise.
 */
define( 'RG_CURRENT_VIEW', GFForms::get( 'view' ) );

/**
 * Defines the minimum version of WordPress required for Gravity Forms.
 *
 * @since   Unknown
 *
 * @var string GF_MIN_WP_VERSION Minimum version number.
 */
define( 'GF_MIN_WP_VERSION', '4.0' );

/**
 * Checks if the current WordPress version is supported.
 *
 * @since   Unknown
 *
 * @var boolean GF_SUPPORTED_VERSION True if supported.  False otherwise.
 */
define( 'GF_SUPPORTED_WP_VERSION', version_compare( get_bloginfo( 'version' ), GF_MIN_WP_VERSION, '>=' ) );

/**
 * Defines the minimum version of WordPress that will be officially supported.
 *
 * @var string GF_MIN_WP_VERSION_SUPPORT_TERMS The version number
 */
define( 'GF_MIN_WP_VERSION_SUPPORT_TERMS', '5.9' );

/**
 * The filesystem path of the directory that contains the plugin, includes trailing slash.
 *
 * @since 2.6.2
 *
 * @var string
 */
define( 'GF_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'GRAVITY_MANAGER_URL' ) ) {
	/**
	 * Defines the Gravity Manager URL.
	 *
	 * @var string GRAVITY_MANAGER_URL The full URL to the Gravity Manager.
	 */
	define( 'GRAVITY_MANAGER_URL', 'https://gravityapi.com/wp-content/plugins/gravitymanager' );
}

require_once GF_PLUGIN_DIR_PATH . 'includes/class-gf-service-container.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/class-gf-service-provider.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/transients/interface-gf-transient-strategy.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/transients/class-gf-wp-transient-strategy.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/external-api/interface-gf-api-response-factory.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/external-api/class-gf-api-connector.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/external-api/class-gf-api-response.php';

require_once GF_PLUGIN_DIR_PATH . 'currency.php';
require_once GF_PLUGIN_DIR_PATH . 'common.php';
require_once GF_PLUGIN_DIR_PATH . 'forms_model.php';
require_once GF_PLUGIN_DIR_PATH . 'form_detail.php';
require_once GF_PLUGIN_DIR_PATH . 'widget.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/api.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/webapi/webapi.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/fields/class-gf-fields.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/class-gf-download.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/query/class-gf-query.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/assets/class-gf-asset.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/assets/class-gf-script-asset.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/assets/class-gf-style-asset.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/trait-redirects-on-save.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/class-translationspress-updater.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/messages/class-dismissable-messages.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/orders/factories/class-gf-order-factory.php';
require_once GF_PLUGIN_DIR_PATH . 'includes/orders/summaries/class-gf-order-summary.php';

// Load Logging if Logging Add-On is not active.
if ( ! GFCommon::is_logging_plugin_active() ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/logging/logging.php';
}

// GFCommon::$version is deprecated, set it to current version for backwards compatibility
GFCommon::$version = GFForms::$version;

add_action( 'init', array( 'GFForms', 'init' ) );
add_action( 'init', array( 'GFForms', 'screen_options_filters' ) );
add_action( 'admin_init', array( 'GFForms', 'add_entry_list_filter' ) );
add_action( 'admin_init', array( 'GFForms', 'initialize_admin_settings' ) );
add_action( 'gform_preview_init', array( 'GFForms', 'init_preview' ) );
add_action( 'wp', array( 'GFForms', 'maybe_process_form' ), 9 );
add_action( 'admin_init', array( 'GFForms', 'maybe_process_form' ), 9 );
add_action( 'wp', array( 'GFForms', 'process_exterior_pages' ) );
add_action( 'admin_init', array( 'GFForms', 'process_exterior_pages' ) );
add_action( 'admin_head', array( 'GFCommon', 'find_admin_notices' ) );
add_action( 'admin_head', array( 'GFCommon', 'admin_notices_style' ) );
add_action( 'upgrader_process_complete', array( 'GFForms', 'install_addon_translations' ), 10, 2 );
add_action( 'update_option_WPLANG', array( 'GFForms', 'update_translations' ), 10, 2 );
add_action( 'wp_ajax_update_auto_update_setting', array( 'GFForms', 'update_auto_update_setting' ) );
add_action( 'plugins_loaded', array( 'GFForms', 'register_services' ), 10, 0 );
add_action( 'init', array( 'GFForms', 'init_buffer' ) );
add_filter( 'upgrader_pre_install', array( 'GFForms', 'validate_upgrade' ), 10, 2 );
add_filter( 'tiny_mce_before_init', array( 'GFForms', 'modify_tiny_mce_4' ), 20 );
add_filter( 'user_has_cap', array( 'RGForms', 'user_has_cap' ), 10, 4 );
add_filter( 'query', array( 'GFForms', 'filter_query' ) );
add_filter( 'plugin_auto_update_setting_html', array( 'GFForms', 'auto_update_message' ), 10, 3 );
add_filter( 'plugin_auto_update_debug_string', array( 'GFForms', 'auto_update_debug_message' ), 10, 4 );

//Hooks for no-conflict functionality
if ( is_admin() && ( GFForms::is_gravity_page() || GFForms::is_gravity_ajax_action() ) ) {
	add_action( 'wp_print_scripts', array( 'GFForms', 'no_conflict_mode_script' ), 1000 );
	add_action( 'admin_print_footer_scripts', array( 'GFForms', 'no_conflict_mode_script' ), 9 );

	add_action( 'wp_print_styles', array( 'GFForms', 'no_conflict_mode_style' ), 1000 );
	add_action( 'admin_print_styles', array( 'GFForms', 'no_conflict_mode_style' ), 1 );
	add_action( 'admin_print_footer_scripts', array( 'GFForms', 'no_conflict_mode_style' ), 1 );
	add_action( 'admin_footer', array( 'GFForms', 'no_conflict_mode_style' ), 1 );
}

add_action( 'plugins_loaded', array( 'GFForms', 'loaded' ) );

register_activation_hook( __FILE__, array( 'GFForms', 'activation_hook' ) );
register_deactivation_hook( __FILE__, array( 'GFForms', 'deactivation_hook' ) );

gf_upgrade();

/**
 * Class GFForms
 *
 * Handles the loading of Gravity Forms and other core functionality
 */
class GFForms {

	/**
	* @var \Gravity_Forms\Gravity_Forms\GF_Service_Container $container
	*/
	private static $container;

	/**
	 * Defines this version of Gravity Forms.
	 *
	 * @since  Unknown
	 *
	 * @var string $version The version number.
	 */
	public static $version = '2.6.5';

	/**
	 * Handles background upgrade tasks.
	 *
	 * @var GF_Background_Upgrader
	 */
	public static $background_upgrader = null;

	/**
	 * Runs after Gravity Forms is loaded.
	 *
	 * Initializes add-ons.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFAddOn::init_addons()
	 *
	 * @return void
	 */
	public static function loaded() {

		// Load in Settings Framework.
		require_once( GFCommon::get_base_path() . '/settings.php' );
		require_once( GFCommon::get_base_path() . '/includes/settings/class-settings.php' );

		/**
		 * Fires when Gravity Forms has loaded.
		 *
		 * When developing Add-Ons, use this hook to initialize any functionality that depends on Gravity Forms functionality.
		 */
		do_action( 'gform_loaded' );

		//initializing Add-Ons if necessary
		if ( class_exists( 'GFAddOn' ) ) {
			GFAddOn::init_addons();
		}

		if ( defined( 'OSDXP_DASHBOARD_VER' ) ) {
			// Integration with osDXP.
			require_once  GF_PLUGIN_DIR_PATH . 'includes/class-gf-osdxp.php';
		}
	}

	/**
	* Register services and providers.
	*
	* @since 2.5.11
	*
	* @return void
	*/
	public static function register_services() {
		$container = self::get_service_container();

		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Util\GF_Util_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\License\GF_License_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Embed_Form\GF_Embed_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Merge_Tags\GF_Merge_Tags_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Duplicate_Submissions\GF_Duplicate_Submissions_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Form_Editor\GF_Form_Editor_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Splash_Page\GF_Splash_Page_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Query\Batch_Processing\GF_Batch_Operations_Service_Provider() );
        $container->add_provider( new \Gravity_Forms\Gravity_Forms\Settings\GF_Settings_Service_Provider() );
		$container->add_provider( new \Gravity_Forms\Gravity_Forms\Assets\GF_Asset_Service_Provider() );
	}

	/**
	* Get the Service Container for the plugin.
	*
	* @since 2.5.11
	*
	* @return \Gravity_Forms\Gravity_Forms\GF_Service_Container
	*/
	public static function get_service_container() {
		require_once GF_PLUGIN_DIR_PATH . 'includes/license/class-gf-license-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/duplicate-submissions/class-gf-duplicate-submissions-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/util/class-gf-util-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/config/class-gf-config-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/embed-form/class-gf-embed-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/form-editor/class-gf-form-editor-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/splash-page/class-gf-splash-page-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/query/batch-processing/class-gf-batch-operations-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/save-form/class-gf-save-form-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/merge-tags/class-gf-merge-tags-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/settings/class-gf-settings-service-provider.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/assets/class-gf-asset-service-provider.php';

		if ( ! empty( self::$container ) ) {
			return self::$container;
		}

		self::$container = new \Gravity_Forms\Gravity_Forms\GF_Service_Container();

		return self::$container;
	}

	/**
	 * Determines if the 3rd party Members plugin is active.
	 *
	 * @since  2.4.13 Removed Members v1 support.
	 * @since  Unknown
	 *
	 * @param null $deprecated No longer used. Previously the minimum version number of Members plugin to check for.
	 *
	 * @return boolean True if the Members plugin is active. False otherwise.
	 */
	public static function has_members_plugin( $deprecated = null ) {
		return function_exists( 'members_register_cap_group' ) && function_exists( 'members_register_cap' );
	}

	/**
	 * Initializes Gravity Forms.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return void
	 */
	public static function init() {

		if ( ! wp_next_scheduled( 'gravityforms_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'gravityforms_cron' );
		}

		add_action( 'gravityforms_cron', array( 'GFForms', 'cron' ) );

		GF_Download::maybe_process();

		//load text domains
		GFCommon::load_gf_text_domain( 'gravityforms' );

		add_filter( 'gform_logging_supported', array( 'GFForms', 'set_logging_supported' ) );
		add_action( 'admin_head', array( 'GFCommon', 'maybe_output_gf_vars' ) );
		add_action( 'admin_head', array( 'GFForms', 'load_admin_bar_styles' ) );
		add_action( 'wp_head', array( 'GFForms', 'load_admin_bar_styles' ) );
		add_action( 'dynamic_sidebar_before', array( 'GFCommon', 'check_for_gf_widgets' ), 10 );
		add_action( 'gform_enqueue_scripts', array( 'GFCommon', 'localize_gf_legacy_multi' ), 9999 );

		if ( self::get_page() === 'form_editor' ) {
			add_action( 'admin_head', array( 'GFForms', 'preload_webfonts' ), 0, 0 );
		}

		if ( self::get_page() === 'form_editor' ) {
			add_action( 'admin_head', array( 'GFForms', 'preload_webfonts' ), 0, 0 );
		}

		self::register_scripts();

		self::init_hook_vars();

		GFCommon::localize_gform_i18n();

		self::init_background_upgrader();

		// Run background feed processing.
		require_once GF_PLUGIN_DIR_PATH . 'includes/addon/class-gf-feed-processor.php';

		gf_feed_processor();

		// Maybe set up Gravity Forms
		if ( ( false === ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) ) ) {

			gf_upgrade()->maybe_upgrade();

		}

		// Load Editor Blocks if WordPress is 5.0+.
		if ( function_exists( 'register_block_type' ) ) {

			// Load block framework.
			if ( ! class_exists( 'GF_Blocks' ) ) {
				require_once GF_PLUGIN_DIR_PATH . 'includes/blocks/class-gf-blocks.php';
			}

			// Load included Blocks.
			GFCommon::glob_require_once( '/includes/blocks/class-gf-block-*.php' );

		}

		// Plugin update actions
		add_filter( 'transient_update_plugins', array( 'GFForms', 'check_update' ) );
		add_filter( 'site_transient_update_plugins', array( 'GFForms', 'check_update' ) );
		add_filter( 'auto_update_plugin', array( 'GFForms', 'maybe_auto_update' ), 10, 2 );

		if ( IS_ADMIN ) {

			global $current_user;

			//Members plugin integration. Adding Gravity Forms roles to the checkbox list
			if ( self::has_members_plugin() ) {
				add_action( 'members_register_cap_groups', array( 'GFForms', 'members_register_cap_group' ) );
				add_action( 'members_register_caps', array( 'GFForms', 'members_register_caps' ) );
			}

            // User Role Editor integration.
            add_filter( 'ure_capabilities_groups_tree', array( 'GFForms', 'filter_ure_capabilities_groups_tree' ) );
            add_filter( 'ure_custom_capability_groups', array( 'GFForms', 'filter_ure_custom_capability_groups' ), 10, 2 );

			if ( is_multisite() ) {
				add_filter( 'wpmu_drop_tables', array( 'GFFormsModel', 'mu_drop_tables' ) );
			}

			add_action( 'admin_enqueue_scripts', array( 'GFForms', 'enqueue_admin_scripts' ) );
			add_action( 'print_media_templates', array( 'GFForms', 'action_print_media_templates' ) );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				add_action( 'admin_footer', array( 'GFForms', 'deprecate_add_on_methods' ) );
			}

			//Loading Gravity Forms if user has access to any functionality
			if ( GFCommon::current_user_can_any( GFCommon::all_caps() ) ) {
				require_once( GFCommon::get_base_path() . '/export.php' );
				GFExport::maybe_export();

				//Imports theme forms if configured to be automatic imported
				gf_upgrade()->maybe_import_theme_forms();

				//creates the "Forms" left menu
				add_action( 'admin_menu', array( 'GFForms', 'create_menu' ) );

				// Add site-wide admin notices
				add_action( 'admin_notices', array( 'GFForms', 'action_admin_notices' ) );

				if ( GF_SUPPORTED_WP_VERSION ) {

					add_action( 'admin_footer', array( 'GFForms', 'check_upload_folder' ) );
					add_action( 'wp_dashboard_setup', array( 'GFForms', 'dashboard_setup' ) );

					// Support modifying the admin page title for settings
					add_filter( 'admin_title', array( __class__, 'modify_admin_title' ), 10, 2 );

					// Display admin notice if logging is enabled.
					add_action( 'admin_notices', array( 'GFForms', 'maybe_display_logging_notice' ) );

					require_once( GFCommon::get_base_path() . '/includes/locking/locking.php' );

					if ( self::is_gravity_page() ) {
						require_once( GFCommon::get_base_path() . '/tooltips.php' );
					} elseif ( RG_CURRENT_PAGE == 'media-upload.php' ) {
						require_once( GFCommon::get_base_path() . '/entry_list.php' );
					} elseif ( in_array( RG_CURRENT_PAGE, array( 'admin.php', 'admin-ajax.php' ) ) ) {

						add_action( 'wp_ajax_rg_change_input_type', array( 'GFForms', 'change_input_type' ) );
						add_action( 'wp_ajax_rg_refresh_field_preview', array( 'GFForms', 'refresh_field_preview' ) );
						add_action( 'wp_ajax_rg_add_field', array( 'GFForms', 'add_field' ) );
						add_action( 'wp_ajax_rg_duplicate_field', array( 'GFForms', 'duplicate_field' ) );
						add_action( 'wp_ajax_rg_delete_field', array( 'GFForms', 'delete_field' ) );
						add_action( 'wp_ajax_rg_delete_file', array( 'GFForms', 'delete_file' ) );
						add_action( 'wp_ajax_rg_select_export_form', array( 'GFForms', 'select_export_form' ) );
						add_action( 'wp_ajax_rg_start_export', array( 'GFForms', 'start_export' ) );
						add_action( 'wp_ajax_gf_upgrade_license', array( 'GFForms', 'upgrade_license' ) );
						add_action( 'wp_ajax_gf_delete_custom_choice', array( 'GFForms', 'delete_custom_choice' ) );
						add_action( 'wp_ajax_gf_save_custom_choice', array( 'GFForms', 'save_custom_choice' ) );
						add_action( 'wp_ajax_gf_get_post_categories', array( 'GFForms', 'get_post_category_values' ) );
						add_action( 'wp_ajax_gf_get_address_rule_values_select', array(
							'GFForms',
							'get_address_rule_values_select'
						) );
						add_action( 'wp_ajax_gf_get_notification_post_categories', array(
							'GFForms',
							'get_notification_post_category_values'
						) );
						//add_action( 'wp_ajax_gf_save_confirmation', array( 'GFForms', 'save_confirmation' ) );
						add_action( 'wp_ajax_gf_delete_confirmation', array( 'GFForms', 'delete_confirmation' ) );
						add_action( 'wp_ajax_gf_save_new_form', array( 'GFForms', 'save_new_form' ) );
						add_action( 'wp_ajax_gf_save_title', array( 'GFForms', 'save_form_title' ) );

						//entry list ajax operations
						add_action( 'wp_ajax_rg_update_lead_property', array( 'GFForms', 'update_lead_property' ) );
						add_action( 'wp_ajax_delete-gf_entry', array( 'GFForms', 'update_lead_status' ) );

						//form list ajax operations
						add_action( 'wp_ajax_rg_update_form_active', array( 'GFForms', 'update_form_active' ) );

						//notification list ajax operations
						add_action( 'wp_ajax_rg_update_notification_active', array(
							'GFForms',
							'update_notification_active'
						) );

						//confirmation list ajax operations
						add_action( 'wp_ajax_rg_update_confirmation_active', array(
							'GFForms',
							'update_confirmation_active'
						) );

						//dynamic captcha image
						add_action( 'wp_ajax_rg_captcha_image', array( 'GFForms', 'captcha_image' ) );

						//dashboard message "dismiss upgrade" link
						add_action( 'wp_ajax_rg_dismiss_upgrade', array( 'GFForms', 'dashboard_dismiss_upgrade' ) );

						// entry detail: resend notifications
						add_action( 'wp_ajax_gf_resend_notifications', array( 'GFForms', 'resend_notifications' ) );

						// Shortcode UI
						add_action( 'wp_ajax_gf_do_shortcode', array( 'GFForms', 'handle_ajax_do_shortcode' ) );

						// Export
						add_filter( 'wp_ajax_gf_process_export', array( 'GFForms', 'ajax_process_export' ) );
						add_filter( 'wp_ajax_gf_download_export', array( 'GFForms', 'ajax_download_export' ) );

						// Dismiss message
						add_action( 'wp_ajax_gf_dismiss_message', array( 'GFForms', 'ajax_dismiss_message' ) );

						// Check background tasks for the system report
						add_action( 'wp_ajax_gf_check_background_tasks', array( 'GFForms', 'check_background_tasks' ) );

						// Check status of upgrade
						add_action( 'wp_ajax_gf_force_upgrade', array( 'GFForms', 'ajax_force_upgrade' ) );

						// Disable logging.
						add_action( 'wp_ajax_gf_disable_logging', array( 'GFForms', 'ajax_disable_logging' ) );

						// Get change log.
						add_action( 'wp_ajax_gf_get_changelog', array( 'GFForms', 'ajax_display_changelog' ) );

					}

					add_filter( 'plugins_api', array( 'GFForms', 'get_addon_info' ), 100, 3 );
					add_action( 'after_plugin_row', array( 'GFForms', 'plugin_row' ), 10, 2 );
					add_action( 'in_plugin_update_message-gravityforms/gravityforms.php', array( 'GFForms', 'in_plugin_update_message' ), 10, 2 );
					add_action( 'install_plugins_pre_plugin-information', array( 'GFForms', 'display_changelog' ), 9 );
					add_filter( 'plugin_action_links', array( 'GFForms', 'plugin_settings_link' ), 10, 2 );
				}
			}
			add_action( 'admin_init', array( 'GFForms', 'ajax_parse_request' ), 10 );

			add_filter(	'wp_privacy_personal_data_exporters', array( 'GFForms', 'register_data_exporter' ),	10 );
			add_filter(	'wp_privacy_personal_data_erasers', array( 'GFForms', 'register_data_eraser' ),	10 );

		} else {
			add_action( 'wp_enqueue_scripts', array( 'GFForms', 'enqueue_scripts' ), 11 );
			add_action( 'wp', array( 'GFForms', 'ajax_parse_request' ), 10 );
		}

		// Add admin bar items
		add_action( 'wp_before_admin_bar_render', array( 'GFForms', 'admin_bar' ) );

		add_shortcode( 'gravityform', array( 'GFForms', 'parse_shortcode' ) );
		add_shortcode( 'gravityforms', array( 'GFForms', 'parse_shortcode' ) );

		// ManageWP premium update filters
		add_filter( 'mwp_premium_update_notification', array( 'GFForms', 'premium_update_push' ) );
		add_filter( 'mwp_premium_perform_update', array( 'GFForms', 'premium_update' ) );

		// Push Gravity Forms to the top of the list of plugins to make sure it's loaded before any add-ons
		add_action( 'activated_plugin', array( 'GFForms', 'load_first' ) );

		// Add the "Add Form" button to the editor. The customizer doesn't run in the admin context.
		if ( GFForms::page_supports_add_form_button() ) {
			require_once( GFCommon::get_base_path() . '/tooltips.php' );

			// Adding "embed form" button to the editor
			add_action( 'media_buttons', array( 'GFForms', 'add_form_button' ), 20 );
			// Adding the modal
			add_action( 'admin_print_footer_scripts', array( 'GFForms', 'add_mce_popup' ) );
		}

		// Add a special classname to the body element when admin.css is loaded
		add_filter( 'admin_body_class', array( 'GFForms', 'add_admin_body_class' ), 99, 1 );
	}

	/**
	 * Initialize any settings needed for the Preview functionality.
	 *
	 * @since  2.5
	 * @access public
	 *
	 * @return void
	 */
	public static function init_preview() {
		$min      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		$base_url = GFCommon::get_base_url();

		wp_register_style( 'gf-preview', "$base_url/css/preview$min.css" );
		wp_register_style( 'gf-preview-rtl', "$base_url/css/rtl$min.css" );

		add_filter( 'gform_preview_styles', function( $styles ) {
			$styles[] = 'gf-preview-reset';
			$styles[] = 'gf-preview';

			if ( is_rtl() ) {
				$styles[] = 'gf-rtl';
			}

			return $styles;
		}, 10, 1 );
	}

	/**
	 * Show and save screen options on GF list pages.
	 *
	 * @since  2.5
	 * @access public
	 *
	 * @return void
	 */
	public static function screen_options_filters() {
		$gf_page = self::get_page();
		if ( $gf_page == 'entry_list' ) {
			add_filter( 'screen_settings', array( 'GFForms', 'show_screen_options' ), 10, 2 );
			// For WP 5.4.1 and older.
			add_filter( 'set-screen-option', array( 'GFForms', 'set_screen_options' ), 10, 3 );
			// For WP 5.4.2+.
			add_filter( 'set_screen_option_gform_entries_screen_options', array( 'GFForms', 'set_screen_options', ), 10, 3 );
		}

		if ( $gf_page == 'form_list' ) {
			// For WP 5.4.1 and older.
			add_filter( 'set-screen-option', array( 'GFForms', 'set_screen_options' ), 10, 3 );
			// For WP 5.4.2+.
			add_filter( 'set_screen_option_gform_forms_per_page', array( 'GFForms', 'set_screen_options' ), 10, 3 );
		}
	}

	/**
	 * Add the appropriate filter if necessary on the entry list page.
	 *
	 * @since  2.5
	 * @access public
	 *
	 * @return void
	 */
	public static function add_entry_list_filter() {
		$gf_page = self::get_page();
		if ( $gf_page == 'entry_list' && ! isset( $_GET['filter'] ) ) {
			require_once( GFCommon::get_base_path() . '/entry_list.php' );
			$default_filter = GFEntryList::get_default_filter();
			if ( $default_filter !== 'all' ) {
				$url = add_query_arg( array( 'filter' => $default_filter ) );
				$url = esc_url_raw( $url );
				wp_safe_redirect( $url );
			}
		}
	}

	/**
	 * Initialize all of the admin settings based on the current admin page.
	 *
	 * @since  2.5
	 * @access public
	 *
	 * @return void
	 */
	public static function initialize_admin_settings() {
		$gf_page = self::get_page();
		require_once GFCommon::get_base_path() . '/tooltips.php';

		// Initialize Plugin Settings.
		if ( $gf_page === 'settings' && ( ! rgget( 'subview' ) || rgget( 'subview' ) === 'settings' ) ) {
			if ( ! class_exists( 'GFSettings' ) ) {
				require_once( GFCommon::get_base_path() . '/settings.php' );
			}
			GFSettings::initialize_plugin_settings();
		}

		// Initialize reCAPTCHA Settings.
		if ( $gf_page === 'settings' && rgget( 'subview' ) === 'recaptcha' ) {
			if ( ! class_exists( 'GFSettings' ) ) {
				require_once( GFCommon::get_base_path() . '/settings.php' );
			}
			GFSettings::initialize_recaptcha_settings();
		}

		// Initialize Form Settings.
		if ( $gf_page === 'form_settings' ) {
			if ( ! class_exists( 'GFFormSettings' ) ) {
				require_once( GFCommon::get_base_path() . '/form_settings.php' );
			}
			GFFormSettings::initialize_settings_renderer();
		}

		// Initialize Personal Data settings.
		if ( $gf_page === 'personal_data' ) {
			if ( ! class_exists( 'GF_Personal_Data' ) ) {
				require_once( GFCommon::get_base_path() . '/includes/class-personal-data.php' );
			}
			GF_Personal_Data::initialize_settings_renderer();
		}

		// Initialize Confirmation settings.
		if ( $gf_page === 'confirmation' && isset( $_GET['cid'] ) ) {
			if ( ! class_exists( 'GF_Confirmation' ) ) {
				require_once( GFCommon::get_base_path() . '/includes/class-confirmation.php' );
			}
			GF_Confirmation::initialize_settings_renderer();
		}

		// Initialize Notification settings.
		if ( $gf_page === 'notification_edit' ) {
			if ( ! class_exists( 'GFNotification' ) ) {
				require_once( GFCommon::get_base_path() . '/notification.php' );
			}
			GFNotification::initialize_settings_renderer();
		}
	}

	/**
	 * Ensures that Gravity Forms is loaded first.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return void
	 */
	public static function load_first() {
		$plugin_path    = basename( dirname( __FILE__ ) ) . '/gravityforms.php';
		$active_plugins = array_values( maybe_unserialize( self::get_wp_option( 'active_plugins' ) ) );
		$key            = array_search( $plugin_path, $active_plugins );
		if ( $key > 0 ) {
			array_splice( $active_plugins, $key, 1 );
			array_unshift( $active_plugins, $plugin_path );
			update_option( 'active_plugins', $active_plugins );
		}
	}

	/**
	 * Performs Gravity Forms deactivation tasks.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFCache::flush()
	 *
	 * @return void
	 */
	public static function deactivation_hook() {
		GFCache::flush( true );
		flush_rewrite_rules();
	}

	/**
	 * Performs Gravity Forms activation tasks.
	 *
	 * @since  2.3
	 * @access public
	 */
	public static function activation_hook() {
		self::register_services();
		self::init_background_upgrader();
		gf_upgrade()->maybe_upgrade();
	}

	/**
	 * Add Gravity Forms to the plugins that support logging.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $plugins Existing plugins that support logging.
	 *
	 * @return array $plugins Supported plugins.
	 */
	public static function set_logging_supported( $plugins ) {
		$plugins['gravityformsapi'] = 'Gravity Forms API';
		$plugins['gravityforms']    = 'Gravity Forms Core';

		return $plugins;
	}

	/**
	 * Gets the value of an option from the wp_options table.
	 *
	 * @since  Unknown
	 * @access public
	 * @global       $wpdb
	 *
	 * @param string $option_name The option to find.
	 *
	 * @return string The option value, if found.
	 */
	public static function get_wp_option( $option_name ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->prefix}options WHERE option_name=%s", $option_name ) );
	}

	/**
	 * Determines if a form should be processed, and passes it off to processing.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormsModel::get_form()
	 * @uses   GFCommon::get_base_path()
	 * @uses   GFFormDisplay::process_form()
	 * @uses   GFFormDisplay::process_send_resume_link()
	 *
	 * @return void
	 */
	public static function maybe_process_form() {
		if ( isset( $_POST['gform_send_resume_link'] ) ) {
			require_once( GFCommon::get_base_path() . '/form_display.php' );
			GFFormDisplay::process_send_resume_link();
		} elseif ( isset( $_POST['gform_submit'] ) ) {
			require_once( GFCommon::get_base_path() . '/form_display.php' );
			$form_id = GFFormDisplay::is_submit_form_id_valid();

			if ( $form_id ) {
				GFFormDisplay::process_form( $form_id, GFFormDisplay::SUBMISSION_INITIATED_BY_WEBFORM );
			}
		}
	}

	/**
	 * Processes pages that are not loaded directly within WordPress
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFCommon::get_upload_page_slug()
	 * @uses   GFCommon::get_base_path()
	 *
	 * @return void
	 */
	public static function process_exterior_pages() {
		if ( rgempty( 'gf_page', $_GET ) ) {
			return;
		}

		$page = rgget( 'gf_page' );

		$is_legacy_upload_page = $_SERVER['REQUEST_METHOD'] == 'POST' && $page == 'upload';

		if ( $is_legacy_upload_page && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			_doing_it_wrong( 'gf_page=upload', 'gf_page=upload is now deprecated. Use GFCommon::get_upload_page_slug() instead', '1.9.6.13' );
		}

		$is_upload_page = $_SERVER['REQUEST_METHOD'] == 'POST' && $page == GFCommon::get_upload_page_slug();

		if ( $is_upload_page || $is_legacy_upload_page ) {
			require_once( GFCommon::get_base_path() . '/includes/upload.php' );
			exit();
		}

		// Ensure users are logged in
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		switch ( $page ) {
			case 'preview':
				require_once( GFCommon::get_base_path() . '/preview.php' );
				break;

			case 'print-entry' :
				require_once( GFCommon::get_base_path() . '/print-entry.php' );
				break;

			case 'select_columns' :
				require_once( GFCommon::get_base_path() . '/select_columns.php' );
				break;
		}
		exit();
	}

	/**
	 * Checks for Gravity Forms updates.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFCommon::check_update()
	 *
	 * @param GFAutoUpgrade $update_plugins_option The GFAutoUpgrade object.
	 *
	 * @return GFAutoUpgrade The GFAutoUpgrade object.
	 */
	public static function check_update( $update_plugins_option ) {
		if ( ! class_exists( 'GFCommon' ) ) {
			require_once( 'common.php' );
		}

		return GFCommon::check_update( $update_plugins_option, true );
	}

	/**
	 * Adds index and htaccess files to the upload root for security.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return void
	 */
	public static function add_security_files() {
		GFCommon::log_debug( __METHOD__ . '(): Start adding security files' );

		$upload_root = GFFormsModel::get_upload_root();

		if ( ! is_dir( $upload_root ) ) {
			return;
		}

		GFCommon::recursive_add_index_file( $upload_root );

		GFCommon::add_htaccess_file();
	}

	/**
	 * Self-heals suspicious files.
	 *
	 * @since  Unknown
	 */
	private static function do_self_healing() {

		GFCommon::log_debug( __METHOD__ . '(): Start self healing' );

		$gf_upload_root = GFFormsModel::get_upload_root();

		if ( ! is_dir( $gf_upload_root ) || is_link( $gf_upload_root ) ) {
			return;
		}

		self::rename_suspicious_files_recursive( $gf_upload_root );
	}

	/**
	 * Renames files with a .bak extension if they have a file extension that is not allowed in the Gravity Forms uploads folder.
	 *
	 * @since   Unknown
	 * @access  private
	 *
	 * @used-by GFForms::do_self_healing()
	 *
	 * @param string $dir The path to process.
	 */
	private static function rename_suspicious_files_recursive( $dir ) {
		if ( ! is_dir( $dir ) || is_link( $dir ) ) {
			return;
		}

		if ( ! ( $dir_handle = opendir( $dir ) ) ) {
			return;
		}

		// Ignores all errors
		set_error_handler( '__return_false', E_ALL );

		while ( false !== ( $file = readdir( $dir_handle ) ) ) {
			if ( is_dir( $dir . DIRECTORY_SEPARATOR . $file ) && $file != '.' && $file != '..' ) {
				self::rename_suspicious_files_recursive( $dir . DIRECTORY_SEPARATOR . $file );
			} elseif ( GFCommon::file_name_has_disallowed_extension( $file )
			           && ! GFCommon::match_file_extension( $file, array( 'htaccess', 'bak', 'html' ) )
			) {
				$mini_hash = substr( wp_hash( $file ), 0, 6 );
				$newName   = sprintf( '%s/%s.%s.bak', $dir, $file, $mini_hash );
				rename( $dir . '/' . $file, $newName );
			}
		}

		// Restores error handler
		restore_error_handler();

		closedir( $dir_handle );

		return;
	}

	/**
	 * Renames suspicious content within the wp_upload directory.
	 *
	 * @deprecated  2.4.11
	 *
	 * @since       Unknown
	 */
	private static function heal_wp_upload_dir() {
	    _deprecated_function( 'heal_wp_upload_dir', '2.4.11' );

		$wp_upload_dir = wp_upload_dir();

		$wp_upload_path = $wp_upload_dir['basedir'];

		if ( ! is_dir( $wp_upload_path ) || is_link( $wp_upload_path ) ) {
			return;
		}

		// ignores all errors
		set_error_handler( '__return_false', E_ALL );

		foreach ( glob( $wp_upload_path . DIRECTORY_SEPARATOR . '*_input_*.php' ) as $filename ) {
			$mini_hash = substr( wp_hash( $filename ), 0, 6 );
			$newName   = sprintf( '%s.%s.bak', $filename, $mini_hash );
			rename( $filename, $newName );
		}

		// restores error handler
		restore_error_handler();

		return;
	}

	/**
	 * Defines styles needed for "no conflict mode"
	 *
	 * @since  Unknown
	 * @access public
	 * @global $wp_styles
	 *
	 * @uses   GFForms::no_conflict_mode()
	 */
	public static function no_conflict_mode_style() {
		if ( ! get_option( 'gform_enable_noconflict' ) ) {
			return;
		}

		global $wp_styles;
		$wp_required_styles = array( 'admin-bar', 'colors', 'ie', 'wp-admin', 'editor-style' );
		$gf_required_styles = array(
			'common'                     => array( 'gform_tooltip', 'gform_font_awesome', 'gform_admin', 'gform_settings' ),
			'gf_edit_forms'              => array(
				'thickbox',
				'editor-buttons',
				'wp-jquery-ui-dialog',
				'media-views',
				'buttons',
				'wp-pointer',
				'gform_chosen',
				'gform_editor',
				'gform_admin_theme',
			),
			'gf_edit_forms_settings' => array(
				'thickbox',
				'editor-buttons',
				'wp-jquery-ui-dialog',
				'media-views',
				'buttons',
			),
			'gf_new_form'                => array( 'thickbox' ),
			'gf_entries'                 => array( 'thickbox', 'gform_chosen', 'gform_admin_theme' ),
			'gf_settings'                => array(),
			'gf_export'                  => array(),
			'gf_help'                    => array(),
			'gf_system_status'			 => array( 'thickbox' ),
		);

		self::no_conflict_mode( $wp_styles, $wp_required_styles, $gf_required_styles, 'styles' );
	}


	/**
	 * Defines scripts needed for "no conflict mode".
	 *
	 * @since  Unknown
	 * @access public
	 * @global $wp_scripts
	 *
	 * @uses   GFForms::no_conflict_mode()
	 */
	public static function no_conflict_mode_script() {
		if ( ! get_option( 'gform_enable_noconflict' ) ) {
			return;
		}

		global $wp_scripts;

		$wp_required_scripts = array(
			'admin-bar',
			'common',
			'jquery-color',
			'utils',
			'svg-painter',
			'mce-view' // added in 2.5.13 to support Media Uploads in no-conflict mode
		);

		$gf_required_scripts = array(
			'common'                     => array( 'gform_tooltip_init', 'sack' ),
			'gf_edit_forms'              => array(
				'backbone',
				'editor',
				'gform_forms',
				'gform_form_admin',
				'gform_form_editor',
				'gform_gravityforms',
				'gform_gravityforms_admin',
				'gform_json',
				'gform_placeholder',
				'jquery-ui-autocomplete',
				'jquery-ui-core',
				'jquery-ui-datepicker',
				'jquery-ui-sortable',
				'jquery-ui-draggable',
				'jquery-ui-droppable',
				'jquery-ui-tabs',
				'jquery-ui-accordion',
				'json2',
				'media-editor',
				'media-models',
				'media-upload',
				'media-views',
				'plupload',
				'plupload-flash',
				'plupload-html4',
				'plupload-html5',
				'quicktags',
				'rg_currency',
				'thickbox',
				'word-count',
				'wp-plupload',
				'wpdialogs-popup',
				'wplink',
				'wp-pointer',
				'gform_chosen',
				'gform_selectwoo',
			),
			'gf_edit_forms_settings' => array(
				'editor',
				'word-count',
				'quicktags',
				'wpdialogs-popup',
				'media-upload',
				'wplink',
				'backbone',
				'jquery-ui-sortable',
				'json2',
				'media-editor',
				'media-models',
				'media-views',
				'plupload',
				'plupload-flash',
				'plupload-html4',
				'plupload-html5',
				'plupload-silverlight',
				'wp-plupload',
				'gform_placeholder',
				'gform_json',
				'gform_gravityforms',
				'gform_gravityforms_admin',
				'gform_forms',
				'gform_form_admin',
				'jquery-ui-datepicker',
				'gform_masked_input',
				'sack',
				'jquery-ui-autocomplete',
				'wp-tinymce',
				'wp-tinymce-root',
				'wp-tinymce-lists',
				'gform_selectwoo',
			),
			'gf_new_form'                => array(
				'thickbox',
				'jquery-ui-core',
				'jquery-ui-sortable',
				'jquery-ui-tabs',
				'jquery-ui-accordion',
				'rg_currency',
				'gform_gravityforms',
				'gform_gravityforms_admin',
				'gform_json',
				'gform_form_admin',
			),
			'gf_entries'                 => array(
				'thickbox',
				'gform_gravityforms',
				'gform_gravityforms_admin',
				'gform_form_admin',
				'wp-lists',
				'gform_json',
				'gform_field_filter',
				'plupload-all',
				'postbox',
				'gform_chosen',
				'gform_selectwoo',
			),
			'gf_settings'                => array( 'gform_gravityforms_admin' ),
			'gf_export'                  => array( 'gform_form_admin', 'jquery-ui-datepicker', 'gform_field_filter', 'gform_gravityforms_admin' ),
			'gf_help'                    => array( 'gform_gravityforms_admin' ),
			'gf_system_status'           => array(
				'gform_system_report_clipboard',
				'thickbox',
				'gform_placeholder'),
		);

		$load_scripts_globally = apply_filters( 'gform_load_admin_scripts_globally', true );
		if ( $load_scripts_globally ) {
			$gf_required_scripts[ 'common' ][] = 'gform_gravityforms_admin';
		}

		self::no_conflict_mode( $wp_scripts, $wp_required_scripts, $gf_required_scripts, 'scripts' );
	}

	/**
	 * Runs "no conflict mode".
	 *
	 * @since   Unknown
	 * @access  private
	 *
	 * @used-by GFForms::no_conflict_mode_style()
	 * @used-by GFForms::no_conflict_mode_script()
	 *
	 * @param WP_Scripts $wp_objects          WP_Scripts object.
	 * @param array      $wp_required_objects Scripts required by WordPress Core.
	 * @param array      $gf_required_objects Scripts required by Gravity Forms.
	 * @param string     $type                Determines if scripts or styles are being run through the function.
	 */
	private static function no_conflict_mode( &$wp_objects, $wp_required_objects, $gf_required_objects, $type = 'scripts' ) {

		$current_page = trim( strtolower( rgget( 'page' ) ) );
		if ( empty( $current_page ) ) {
			$current_page = trim( strtolower( rgget( 'gf_page' ) ) );
		}
		if ( empty( $current_page ) ) {
			$current_page = RG_CURRENT_PAGE;
		}

		$view         = rgempty( 'view', $_GET ) ? 'default' : rgget( 'view' );
		$page_objects = isset( $gf_required_objects[ $current_page . '_' . $view ] ) ? $gf_required_objects[ $current_page . '_' . $view ] : rgar( $gf_required_objects, $current_page );

		//disable no-conflict if $page_objects is false
		if ( $page_objects === false ) {
			return;
		}

		if ( ! is_array( $page_objects ) ) {
			$page_objects = array();
		}

		//merging wp scripts with gravity forms scripts
		$required_objects = array_merge( $wp_required_objects, $gf_required_objects['common'], $page_objects );

		//allowing addons or other products to change the list of no conflict scripts
		$required_objects = apply_filters( "gform_noconflict_{$type}", $required_objects );

		$queue = array();
		foreach ( $wp_objects->queue as $object ) {
			if ( in_array( $object, $required_objects ) ) {
				$queue[] = $object;
			}
		}
		$wp_objects->queue = $queue;

		$required_objects = self::add_script_dependencies( $wp_objects->registered, $required_objects );

		//unregistering scripts
		$registered = array();
		foreach ( $wp_objects->registered as $script_name => $script_registration ) {
			if ( in_array( $script_name, $required_objects ) ) {
				$registered[ $script_name ] = $script_registration;
			}
		}
		$wp_objects->registered = $registered;
	}

	/**
	 * Adds script dependencies needed.
	 *
	 * @since   Unknown
	 * @access  private
	 *
	 * @used-by GFForms::no_conflict_mode()
	 *
	 * @param array $registered Registered scripts.
	 * @param array $scripts    Required scripts.
	 *
	 * @return array $scripts Scripts including dependencies.
	 */
	private static function add_script_dependencies( $registered, $scripts ) {

		//gets all dependent scripts linked to the $scripts array passed
		do {
			$dependents = array();
			foreach ( $scripts as $script ) {
				$deps = isset( $registered[ $script ] ) && is_array( $registered[ $script ]->deps ) ? $registered[ $script ]->deps : array();
				foreach ( $deps as $dep ) {
					if ( ! in_array( $dep, $scripts ) && ! in_array( $dep, $dependents ) ) {
						$dependents[] = $dep;
					}
				}
			}
			$scripts = array_merge( $scripts, $dependents );
		} while ( ! empty( $dependents ) );

		return $scripts;
	}

	/**
	 * Integration with ManageWP.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $premium_update ManageWP update array.
	 *
	 * @return array $premium_update
	 */
	public static function premium_update_push( $premium_update ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$update = GFCommon::get_version_info();
		if ( rgar( $update, 'is_valid_key' ) == true && version_compare( GFCommon::$version, $update['version'], '<' ) ) {
			$gforms                = get_plugin_data( __FILE__ );
			$gforms['type']        = 'plugin';
			$gforms['slug']        = 'gravityforms/gravityforms.php';
			$gforms['new_version'] = ! rgempty( 'version', $update ) ? $update['version'] : false;
			$premium_update[]      = $gforms;
		}

		return $premium_update;
	}

	/**
	 * Integration with ManageWP.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $premium_update ManageWP update array.
	 *
	 * @return array $premium_update.
	 */
	public static function premium_update( $premium_update ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$update = GFCommon::get_version_info();
		if ( rgar( $update, 'is_valid_key' ) == true && version_compare( GFCommon::$version, $update['version'], '<' ) ) {
			$gforms         = get_plugin_data( __FILE__ );
			$gforms['slug'] = 'gravityforms/gravityforms.php'; // If not set by default, always pass theme template
			$gforms['type'] = 'plugin';
			$gforms['url']  = ! rgempty( 'url', $update ) ? $update['url'] : false; // OR provide your own callback function for managing the update

			array_push( $premium_update, $gforms );
		}

		return $premium_update;
	}

	/**
	 * Validates that Gravity Forms is doing the database upgrade, and has permissions to do so.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param null   $do_upgrade Not used.
	 * @param string $hook_extra The plugin triggering the upgrade.
	 *
	 * @return bool|WP_Error True if successful.  Otherwise WP_Error object.
	 */
	public static function validate_upgrade( $do_upgrade, $hook_extra ) {

		return gf_upgrade()->validate_upgrade( $do_upgrade, $hook_extra );
	}

	/**
	 * Download and install translations from TranslationsPress.
	 *
	 * @since 2.5
	 * @since 2.5.6 Added the $slug param.
	 *
	 * @param string $new_language The new site language, only set if user is updating their language settings.
	 * @param string $slug         The plugin or add-on slug the translations are to be installed for.
	 */
	public static function install_translations( $new_language = '', $slug = 'gravityforms' ) {
		TranslationsPress_Updater::download_package( $slug, $new_language );
	}

	/**
	 * Download and install translations from TranslationsPress when a user updates the site language setting.
	 *
	 * @since 2.5
	 *
	 * @param string $old_language The language before the user changed their language setting.
	 * @param string $new_language The new language after the user changed their language setting.
	 */
	public static function update_translations( $old_language, $new_language ) {
		if ( empty( $new_language ) || ! current_user_can( 'install_languages' ) ) {
			return;
		}

		self::install_translations( $new_language );

		if ( ! class_exists( 'GFAddOn' ) ) {
			return;
		}

		$gf_addons = GFAddOn::get_registered_addons( true );

		foreach ( $gf_addons as $gf_addon ) {
			$gf_addon->install_translations( $new_language );
		}
	}

	/**
	 * Download translations when an add-on is installed; before it is activated.
	 *
	 * @since  2.5
	 * @access public
	 *
	 * @param object $upgrader_object WP_Upgrader Instance.
	 * @param array  $hook_extra Item update data.
	 */
	public static function install_addon_translations( $upgrader_object, $hook_extra ) {
		if ( rgar( $hook_extra, 'action' ) !== 'install' || rgar( $hook_extra, 'type' ) !== 'plugin' || empty( $upgrader_object->result ) || is_wp_error( $upgrader_object->result ) ) {
			return;
		}

		$slug = rgar( $upgrader_object->result, 'destination_name' );

		if ( empty( $slug ) && ! empty( $upgrader_object->new_plugin_data ) ) {
			$slug = rgar( $upgrader_object->new_plugin_data, 'TextDomain' );
		}

		$addons_list = GFCache::get( 'addons_list' );

		if ( empty( $addons_list ) ) {
			$addons_api = GFCommon::post_to_manager( 'api.php', 'op=get_plugins', array() );
			if ( is_wp_error( $addons_api ) || empty( $addons_api['body'] ) ) {
				return;
			}
			$addons_list = maybe_unserialize( $addons_api['body'] );
			if ( ! is_array( $addons_list ) ) {
				return;
			}
			// To avoid calling the API every time a plugin is installed, store the list of add-ons as a transient.
			GFCache::set( 'addons_list', $addons_list, true, WEEK_IN_SECONDS );
		}

		if ( ! in_array( $slug, wp_list_pluck( $addons_list, 'name' ) ) ) {
			return;
		}

		self::install_translations( '', $slug );
	}


	// # PERMISSIONS ---------------------------------------------------------------------------------------------------

	/**
	 * Determines if a user has a particular capability.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array   $all_caps All capabilities.
	 * @param array   $cap      Required capability.  Stored in the [0] key.
	 * @param array   $args     Not used.
	 * @param WP_User $user     The relevant user.
	 *
	 * @return array $all_caps All capabilities.
	 */
	public static function user_has_cap( $all_caps, $cap, $args, $user = null ) {
		$gf_caps    = GFCommon::all_caps();
		$capability = rgar( $cap, 0 );
		if ( $capability != 'gform_full_access' ) {
			return $all_caps;
		}

		// Default to current user when $user parameter is not passed.
		if ( false === $user instanceof WP_User ) {
			$user = wp_get_current_user();
		}

		if ( ! self::has_members_plugin() ) {
			//give full access to administrators if the members plugin is not installed
			if ( user_can( $user, 'administrator' ) || ( is_multisite() && is_super_admin( $user->ID ) ) ) {
				$all_caps['gform_full_access'] = true;
			}
		} elseif ( user_can( $user, 'administrator' ) || ( is_multisite() && is_super_admin( $user->ID ) ) ) {

			//checking if user has any GF permission.
			$has_gf_cap = false;
			foreach ( $gf_caps as $gf_cap ) {
				if ( rgar( $all_caps, $gf_cap ) ) {
					$has_gf_cap = true;
				}
			}

			if ( ! $has_gf_cap ) {
				//give full access to administrators if none of the GF permissions are active by the Members plugin
				$all_caps['gform_full_access'] = true;
			}
		}

		return $all_caps;
	}

	/**
	 * Register the Gravity Forms capabilities group with the Members plugin.
	 *
	 * @since  2.4
	 * @access public
	 */
	public static function members_register_cap_group() {

		members_register_cap_group(
			'gravityforms',
			array(
				'label' => esc_html__( 'Gravity Forms', 'gravityforms' ),
				'icon'  => 'dashicons-gravityforms',
				'caps'  => array(),
			)
		);

	}

	/**
	 * Register the capabilities and their human readable labels with the Members plugin.
	 *
	 * @since  2.4
	 * @access public
	 */
	public static function members_register_caps() {

		$caps = array(
			'gravityforms_create_form'      => esc_html__( 'Create Forms', 'gravityforms' ),
			'gravityforms_delete_forms'     => esc_html__( 'Delete Forms', 'gravityforms' ),
			'gravityforms_edit_forms'       => esc_html__( 'Edit Forms', 'gravityforms' ),
			'gravityforms_preview_forms'    => esc_html__( 'Preview Forms', 'gravityforms' ),
			'gravityforms_view_entries'     => esc_html__( 'View Entries', 'gravityforms' ),
			'gravityforms_edit_entries'     => esc_html__( 'Edit Entries', 'gravityforms' ),
			'gravityforms_delete_entries'   => esc_html__( 'Delete Entries', 'gravityforms' ),
			'gravityforms_view_entry_notes' => esc_html__( 'View Entry Notes', 'gravityforms' ),
			'gravityforms_edit_entry_notes' => esc_html__( 'Edit Entry Notes', 'gravityforms' ),
			'gravityforms_export_entries'   => esc_html__( 'Import/Export', 'gravityforms' ),
			'gravityforms_view_settings'    => esc_html__( 'View Plugin Settings', 'gravityforms' ),
			'gravityforms_edit_settings'    => esc_html__( 'Edit Plugin Settings', 'gravityforms' ),
			'gravityforms_view_updates'     => esc_html__( 'Manage Updates', 'gravityforms' ),
			'gravityforms_view_addons'      => esc_html__( 'Manage Add-Ons', 'gravityforms' ),
			'gravityforms_system_status'    => esc_html__( 'View System Status', 'gravityforms' ),
			'gravityforms_uninstall'        => esc_html__( 'Uninstall Gravity Forms', 'gravityforms' ),
			'gravityforms_logging'          => esc_html__( 'Logging Settings', 'gravityforms' ),
			'gravityforms_api_settings'     => esc_html__( 'REST API Settings', 'gravityforms' ),
		);

		foreach ( $caps as $cap => $label ) {
			members_register_cap(
				$cap,
				array(
					'label' => $label,
					'group' => 'gravityforms',
				)
			);
		}

	}

	/**
	 * Register Gravity Forms capabilities group with User Role Editor plugin.
	 *
	 * @since  2.4
	 *
	 * @param array $groups Existing capabilities groups.
	 *
	 * @return array
	 */
	public static function filter_ure_capabilities_groups_tree( $groups = array() ) {

		$groups['gravityforms'] = array(
			'caption' => esc_html__( 'Gravity Forms', 'gravityforms' ),
			'parent'  => 'custom',
			'level'   => 2,
		);

		return $groups;

	}

	/**
	 * Register Gravity Forms capabilities with Gravity Forms group in User Role Editor plugin.
	 *
	 * @since  2.4
	 *
	 * @param array  $groups Current capability groups.
	 * @param string $cap_id Capability identifier.
	 *
	 * @return array
	 */
	public static function filter_ure_custom_capability_groups( $groups = array(), $cap_id = '' ) {

		// Get Gravity Forms capabilities.
		$caps = GFCommon::all_caps();

		// If capability belongs to Gravity Forms, register it to group.
		if ( in_array( $cap_id, $caps, true ) ) {
			$groups[] = 'gravityforms';
		}

		return $groups;

	}

	/**
	 * Tests if the upload folder is writable and displays an error message if not.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return void
	 */
	public static function check_upload_folder() {
		// Check if upload folder is writable
		$folder = RGFormsModel::get_upload_root();
		if ( empty( $folder ) ) {
			echo "<div class='error'>" . esc_html__( 'Upload folder is not writable. Export and file upload features will not be functional.', 'gravityforms' ) . '</div>';
		}
	}

	/**
	 * Checks if a Gravity Forms AJAX action is being performed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return bool True if performing a Gravity Forms AJAX request. False, otherwise.
	 */
	public static function is_gravity_ajax_action() {
		//Gravity Forms AJAX requests
		$current_action  = self::post( 'action' );
		$gf_ajax_actions = array(
			'rg_change_input_type',
			'rg_refresh_field_preview',
			'rg_add_field',
			'rg_duplicate_field',
			'rg_delete_field',
			'rg_select_export_form',
			'rg_start_export',
			'gf_upgrade_license',
			'gf_delete_custom_choice',
			'gf_save_custom_choice',
			'gf_get_notification_post_categories',
			'rg_update_lead_property',
			'delete-gf_entry',
			'rg_update_form_active',
			'rg_update_notification_active',
			'rg_update_confirmation_active',
			'gf_resend_notifications',
			'rg_dismiss_upgrade',
			'gf_save_confirmation',
			'gf_process_export',
			'gf_download_export',
			'gf_dismiss_message',
			'gf_force_upgrade',
		);

		 /**
		 * Filters the AJAX actions that are used to determine if the request is a Gravity forms AJAX request.
 		 *
 		 * @since 2.6
 		 *
 		 * @param array $gf_ajax_actions The AJAX action names.
 		 */
		$gf_ajax_actions = apply_filters( 'gform_ajax_actions', $gf_ajax_actions );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && in_array( $current_action, $gf_ajax_actions ) ) {
			return true;
		}

		// Not a Gravity Forms ajax request.
		return false;
	}

	/**
	 * Determines if the current page is part of Gravity Forms.
	 *
	 * Returns true if the current page is one of Gravity Forms page or first-party add-on page. False otherwise.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return bool
	 */
	public static function is_gravity_page() {

		// Gravity Forms pages
		$current_page   = trim( strtolower( self::get( 'page' ) ) );
		$gf_pages       = array( 'gf_edit_forms', 'gf_new_form', 'gf_entries', 'gf_settings', 'gf_export', 'gf_help', 'gf_addons', 'gf_system_status' );
		$gf_addon_pages = array( 'gravityformscoupons' );

		return in_array( $current_page, array_merge( $gf_pages, $gf_addon_pages ) );
	}

	/**
	 * Creates the "Forms" left nav.
	 *
	 * WordPress generates the page hook suffix and screen ID by passing the translated menu title through sanitize_title().
	 * Screen options and metabox preferences are stored using the screen ID therefore:
	 * 1. The page suffix or screen ID should never be hard-coded. Use get_current_screen()->id.
	 * 2. The page suffix and screen ID must never change.
	 *  e.g. When an update for Gravity Forms is available an icon will be added to the the menu title.
	 *  The HTML for the icon will be stripped entirely by sanitize_title() because the number 1 is encoded.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return void
	 */
	public static function create_menu() {
		require_once( dirname(__FILE__ ) . '/entry_list.php' );

		$has_full_access = current_user_can( 'gform_full_access' );
		$min_cap         = GFCommon::current_user_can_which( GFCommon::all_caps() );
		if ( empty( $min_cap ) ) {
			$min_cap = 'gform_full_access';
		}

		$addon_menus = array();
		$addon_menus = apply_filters( 'gform_addon_navigation', $addon_menus );

		$parent_menu = self::get_parent_menu( $addon_menus );

		// Add a top-level left nav.
		$update_icon = current_user_can( 'install_plugins' ) && GFCommon::has_update() ? "<span title='" . esc_attr( __( 'Update Available', 'gravityforms' ) ) . "' class='update-plugins count-1'><span class='update-count'>&#49;</span></span>" : '';

		$admin_icon = self::get_admin_icon_b64( GFForms::is_gravity_page() ? '#fff' : '#a0a5aa' );

		$forms_hook_suffix = add_menu_page( __( 'Forms', 'gravityforms' ), __( 'Forms', 'gravityforms' ) . $update_icon, $has_full_access ? 'gform_full_access' : $min_cap, $parent_menu['name'], $parent_menu['callback'], $admin_icon, apply_filters( 'gform_menu_position', '16.9' ) );

		add_action( 'load-' . $forms_hook_suffix, array( 'GFForms', 'load_screen_options' ) );

		// Adding submenu pages
		add_submenu_page( $parent_menu['name'], __( 'Forms', 'gravityforms' ), __( 'Forms', 'gravityforms' ), $has_full_access ? 'gform_full_access' : 'gravityforms_edit_forms', 'gf_edit_forms', array(
			'GFForms',
			'forms'
		) );

		add_submenu_page( $parent_menu['name'], __( 'New Form', 'gravityforms' ), __( 'New Form', 'gravityforms' ), $has_full_access ? 'gform_full_access' : 'gravityforms_create_form', 'gf_new_form', array(
			'GFForms',
			'new_form'
		) );

		$entries_hook_suffix = add_submenu_page( $parent_menu['name'], __( 'Entries', 'gravityforms' ), __( 'Entries', 'gravityforms' ), $has_full_access ? 'gform_full_access' : 'gravityforms_view_entries', 'gf_entries', array(
			'GFForms',
			'all_leads_page'
		) );

		add_action( 'load-' . $entries_hook_suffix, array( 'GFForms', 'load_screen_options' ) );
		add_action( 'load-' . $entries_hook_suffix, array( 'GFEntryList', 'redirect_on_restore' ) );

		if ( is_array( $addon_menus ) ) {
			foreach ( $addon_menus as $addon_menu ) {
				add_submenu_page( esc_html( $parent_menu['name'] ), esc_html( $addon_menu['label'] ), esc_html( $addon_menu['label'] ), $has_full_access ? 'gform_full_access' : $addon_menu['permission'], esc_html( $addon_menu['name'] ), $addon_menu['callback'] );
			}
		}

		add_submenu_page( $parent_menu['name'], __( 'Settings', 'gravityforms' ), __( 'Settings', 'gravityforms' ), $has_full_access ? 'gform_full_access' : 'gravityforms_view_settings', 'gf_settings', array(
			'GFForms',
			'settings_page'
		) );

		add_submenu_page( $parent_menu['name'], __( 'Import/Export', 'gravityforms' ), __( 'Import/Export', 'gravityforms' ), $has_full_access ? 'gform_full_access' : ( current_user_can( 'gravityforms_export_entries' ) ? 'gravityforms_export_entries' : 'gravityforms_edit_forms' ), 'gf_export', array(
			'GFForms',
			'export_page'
		) );

		if ( current_user_can( 'install_plugins' ) ) {
			add_submenu_page( $parent_menu['name'], __( 'Add-Ons', 'gravityforms' ), __( 'Add-Ons', 'gravityforms' ), $has_full_access ? 'gform_full_access' : 'gravityforms_view_addons', 'gf_addons', array(
				'GFForms',
				'addons_page'
			) );
		}

		add_submenu_page( $parent_menu['name'], __( 'System Status', 'gravityforms' ), __( 'System Status', 'gravityforms' ), $has_full_access ? 'gform_full_access' : 'gravityforms_system_status', 'gf_system_status', array(
			'GFForms',
			'system_status'
		) );

		add_submenu_page( $parent_menu['name'], __( 'Help', 'gravityforms' ), __( 'Help', 'gravityforms' ), $has_full_access ? 'gform_full_access' : $min_cap, 'gf_help', array(
			'GFForms',
			'help_page'
		) );

	}

	/**
	 * Gets the admin icon for the Forms menu item
	 *
	 * @since Unknown
	 * @since 2.5     Updated the logo icon.
	 *
	 * @param bool|string $color The hex color if changing the color of the icon.  Defaults to false.
	 *
	 * @return string Base64 encoded icon string.
	 */
	public static function get_admin_icon_b64( $color = false ) {

		// Replace the hex color (default was #999999) to %s; it will be replaced by the passed $color

		if ( $color ) {
			$svg_xml = '<?xml version="1.0" encoding="utf-8"?>' . self::get_admin_icon_svg( $color );
			$icon    = sprintf( 'data:image/svg+xml;base64,%s', base64_encode( sprintf( $svg_xml, $color ) ) );
		} else {
			$svg_b64 = 'PHN2ZyB3aWR0aD0iMjEiIGhlaWdodD0iMjEiIHZpZXdCb3g9IjAgMCAyMSAyMSIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPG1hc2sgaWQ9Im1hc2swIiBtYXNrLXR5cGU9ImFscGhhIiBtYXNrVW5pdHM9InVzZXJTcGFjZU9uVXNlIiB4PSIyIiB5PSIxIiB3aWR0aD0iMTciIGhlaWdodD0iMjAiPgo8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTExLjU5MDYgMi4wMzcwM0wxNy4xNzkzIDUuNDQ4MjRDMTcuODk0IDUuODg0IDE4LjQ3NjcgNi45NTI5OCAxOC40NzY3IDcuODI0NTFWMTQuNjUwM0MxOC40NzY3IDE1LjUxODUgMTcuODk0IDE2LjU4NzQgMTcuMTc5MyAxNy4wMjMyTDExLjU5MDYgMjAuNDMxQzEwLjg3OTIgMjAuODY2OCA5LjcxMDU1IDIwLjg2NjggOC45OTkwOSAyMC40MzFMMy40MTA0MSAxNy4wMTk4QzIuNjk1NzMgMTYuNTg0IDIuMTEzMDQgMTUuNTE4NSAyLjExMzA0IDE0LjY0NjlWNy44MjExQzIuMTEzMDQgNi45NTI5OCAyLjY5ODk1IDUuODg0IDMuNDEwNDEgNS40NDgyNEw4Ljk5OTA5IDIuMDM3MDNDOS43MTA1NSAxLjYwMTI2IDEwLjg3OTIgMS42MDEyNiAxMS41OTA2IDIuMDM3MDNaTTE1Ljc0OTQgOS4zNzUwM0g4LjgxMDQ5QzguMzgyOTkgOS4zNzUwMyA4LjA2MjM3IDkuNTAxNjQgNy44MDkwNCA5Ljc3MDY4QzcuMjU0ODggMTAuMzYwMiA2Ljk2MTk2IDExLjUwMzYgNi45MTg0MiAxMi4xNDA2SDEzLjc1MDVWMTAuNDI3NUgxNS43MDE5VjE0LjA5MTJINC44NDAzMUM0Ljg0MDMxIDE0LjA5MTIgNC44Nzk4OSAxMC4wMzk3IDYuMzkxOTcgOC40MzMzOUM3LjAxNzM4IDcuNzY0NzUgNy44NDA3IDcuNDI0NDkgOC44MzAyOCA3LjQyNDQ5SDE1Ljc0OTRWOS4zNzUwM1oiIGZpbGw9IndoaXRlIi8+CjwvbWFzaz4KPGcgbWFzaz0idXJsKCNtYXNrMCkiPgo8cmVjdCB4PSIwLjI5NDkyMiIgeT0iMC43NTc4MTIiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0id2hpdGUiLz4KPC9nPgo8L3N2Zz4K';

			$icon = 'data:image/svg+xml;base64,' . $svg_b64;
		}

		return $icon;
	}

	/**
	 * Returns the admin icon in SVG format.
	 *
	 * @since Unknown
	 * @since 2.5     Updated the logo icon.
	 *
	 * @param string $color The hex color if changing the color of the icon.  Defaults to #999999.
	 *
	 * @return string
	 */
	public static function get_admin_icon_svg( $color = '#999999' ) {
		$svg = '<svg width="21" height="21" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg"><mask id="mask0" mask-type="alpha" maskUnits="userSpaceOnUse" x="2" y="1" width="17" height="20"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.5906 2.03703L17.1793 5.44824C17.894 5.884 18.4767 6.95298 18.4767 7.82451V14.6503C18.4767 15.5185 17.894 16.5874 17.1793 17.0232L11.5906 20.431C10.8792 20.8668 9.71055 20.8668 8.99909 20.431L3.41041 17.0198C2.69573 16.584 2.11304 15.5185 2.11304 14.6469V7.8211C2.11304 6.95298 2.69895 5.884 3.41041 5.44824L8.99909 2.03703C9.71055 1.60126 10.8792 1.60126 11.5906 2.03703ZM15.7494 9.37503H8.81049C8.38299 9.37503 8.06237 9.50164 7.80904 9.77068C7.25488 10.3602 6.96196 11.5036 6.91842 12.1406H13.7505V10.4275H15.7019V14.0912H4.84031C4.84031 14.0912 4.87989 10.0397 6.39197 8.43339C7.01738 7.76475 7.8407 7.42449 8.83028 7.42449H15.7494V9.37503Z" fill="white"/></mask><g mask="url(#mask0)"><rect x="0.294922" y="0.757812" width="20" height="20" fill="%s"/></g></svg>';

		return sprintf( $svg, $color );
	}

	/**
	 * Returns the parent menu item.
	 *
	 * It needs to be the same as the first sub-menu (otherwise WP will duplicate the main menu as a sub-menu).
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $addon_menus Contains the add-on menu items.
	 *
	 * @return array $parent The parent menu array.
	 */
	public static function get_parent_menu( $addon_menus ) {

		if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			$parent = array( 'name' => 'gf_edit_forms', 'callback' => array( 'GFForms', 'forms' ) );
		} else if ( GFCommon::current_user_can_any( 'gravityforms_create_form' ) ) {
			$parent = array( 'name' => 'gf_new_form', 'callback' => array( 'GFForms', 'new_form' ) );
		} else if ( GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			$parent = array( 'name' => 'gf_entries', 'callback' => array( 'GFForms', 'all_leads_page' ) );
		} else if ( is_array( $addon_menus ) && sizeof( $addon_menus ) > 0 ) {
			foreach ( $addon_menus as $addon_menu ) {
				if ( GFCommon::current_user_can_any( $addon_menu['permission'] ) ) {
					$parent = array( 'name' => $addon_menu['name'], 'callback' => $addon_menu['callback'] );
					break;
				}
			}
		} else if ( GFCommon::current_user_can_any( 'gravityforms_view_settings' ) ) {
			$parent = array( 'name' => 'gf_settings', 'callback' => array( 'GFForms', 'settings_page' ) );
		} else if ( GFCommon::current_user_can_any( 'gravityforms_export_entries' ) ) {
			$parent = array( 'name' => 'gf_export', 'callback' => array( 'GFForms', 'export_page' ) );
		} else if ( GFCommon::current_user_can_any( 'gravityforms_view_addons' ) ) {
			$parent = array( 'name' => 'gf_addons', 'callback' => array( 'GFForms', 'addons_page' ) );
		} else if ( GFCommon::current_user_can_any( 'gravityforms_system_status' ) ) {
			$parent = array( 'name' => 'gf_system_status', 'callback' => array( 'GFForms', 'system_status_page' ) );
		} else if ( GFCommon::current_user_can_any( GFCommon::all_caps() ) ) {
			$parent = array( 'name' => 'gf_help', 'callback' => array( 'GFForms', 'help_page' ) );
		}

		return $parent;
	}

	/**
	 * Modifies the page title when on Gravity Forms settings pages.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $admin_title The current admin title
	 * @param string $title       Not used.
	 *
	 * @return string The modified admin title.
	 */
	public static function modify_admin_title( $admin_title, $title ) {

		$subview = rgget( 'subview' );
		$form_id = rgget( 'id' );

		if ( ! $form_id || rgget( 'page' ) != 'gf_edit_forms' || rgget( 'view' ) != 'settings' ) {
			return $admin_title;
		}

		require_once( GFCommon::get_base_path() . '/form_settings.php' );

		$setting_tabs = GFFormSettings::get_tabs( $form_id );
		$page_title   = '';

		foreach ( $setting_tabs as $tab ) {
			if ( $tab['name'] == $subview ) {
				$page_title = $tab['label'];
			}
		}

		if ( $page_title ) {
			$admin_title = sprintf( __( '%1$s &lsaquo; %2$s &#8212; WordPress', 'gravityforms' ), esc_html( $page_title ), $admin_title );
		}

		return $admin_title;
	}

	/**
	 * Parses Gravity Forms shortcode attributes and displays the form.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $attributes The shortcode attributes.
	 * @param null  $content    Defines the content of the shortcode.  Defaults to null.
	 *
	 * @return mixed|string|void
	 */
	public static function parse_shortcode( $attributes, $content = null ) {

		/**
		 * @var string $title
		 * @var string $description
		 * @var int    $id
		 * @var string $name
		 * @var string $field_values
		 * @var string $ajax
		 * @var int    $tabindex
		 * @var string $action
		 */
		extract(
			shortcode_atts(
				array(
					'title'        => true,
					'description'  => true,
					'id'           => 0,
					'name'         => '',
					'field_values' => '',
					'ajax'         => false,
					'tabindex'     => 0,
					'action'       => 'form',
				), $attributes, 'gravityforms'
			)
		);

		$shortcode_string = '';

		switch ( $action ) {
			case 'conditional':
				$shortcode_string = GFCommon::conditional_shortcode( $attributes, $content );
				break;

			default:

				// don't retrieve form markup for custom actions
				if ( $action && $action != 'form' ) {
					break;
				}

				//displaying form
				$title        = strtolower( $title ) == 'false' ? false : true;
				$description  = strtolower( $description ) == 'false' ? false : true;
				$field_values = htmlspecialchars_decode( $field_values );
				$field_values = str_replace( array( '&#038;', '&#091;', '&#093;' ), array( '&', '[', ']' ), $field_values );

				$ajax = strtolower( $ajax ) == 'true' ? true : false;

				//using name to lookup form if id is not specified
				if ( empty( $id ) ) {
					$id = $name;
				}

				parse_str( $field_values, $field_value_array ); //parsing query string like string for field values and placing them into an associative array
				$field_value_array = stripslashes_deep( $field_value_array );

				$shortcode_string = self::get_form( $id, $title, $description, false, $field_value_array, $ajax, $tabindex );

		}

		/**
		 * Filters the shortcode.
		 *
		 * @since Unknown
		 *
		 * @param string $shortcode_string The full shortcode string.
		 * @param array  $attributes       The attributes within the shortcode.
		 * @param string $content          The content of the shortcode, if available.
		 */
		$shortcode_string = apply_filters( "gform_shortcode_{$action}", $shortcode_string, $attributes, $content );

		return $shortcode_string;
	}

	/**
	 * Includes the add-on framework.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function include_addon_framework() {
		require_once( GFCommon::get_base_path() . '/includes/addon/class-gf-addon.php' );
	}

	/**
	 * Includes the feed class for the add-on framework.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function include_feed_addon_framework() {
		require_once( GFCommon::get_base_path() . '/includes/addon/class-gf-feed-addon.php' );
	}

	/**
	 * Includes the payment class for te add-on framework.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function include_payment_addon_framework() {
		require_once( GFCommon::get_base_path() . '/includes/addon/class-gf-payment-addon.php' );
	}

	/**
	 * Includes the Gravity API
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function include_gravity_api() {
		require_once( GFCommon::get_base_path() . '/includes/class-gravity-api.php' );
	}

	//-------------------------------------------------
	//----------- AJAX --------------------------------

	/**
	 * Triggers parsing of AJAX requests and outputs the response.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param null $wp Not used.
	 */
	public static function ajax_parse_request( $wp ) {
		if ( isset( $_POST['gform_ajax'] ) ) {
			die( self::get_ajax_form_response() );
		}
	}

	/**
	 * Parses the ajax submission and returns the response.
	 *
	 * @since 2.4.18
	 *
	 * @return mixed|string|void|WP_Error
	 */
	public static function get_ajax_form_response() {
		parse_str( rgpost( 'gform_ajax' ), $args );

		$form_id = isset( $args['form_id'] ) ? absint( $args['form_id'] ) : 0;

		require_once( GFCommon::get_base_path() . '/form_display.php' );

		if ( GFFormDisplay::is_submit_form_id_valid( $form_id ) ) {
			$display_title       = ! isset( $args['title'] ) || ! empty( $args['title'] ) ? true : false;
			$display_description = ! isset( $args['description'] ) || ! empty( $args['description'] ) ? true : false;
			$tabindex            = isset( $args['tabindex'] ) ? absint( $args['tabindex'] ) : 0;

			parse_str( rgpost( 'gform_field_values' ), $field_values );

			$result = GFFormDisplay::get_form( $form_id, $display_title, $display_description, false, $field_values, true, $tabindex );
		} else {
			// The form ID in the footer inputs has been tampered with; handling it like a honeypot failure and returning the default confirmation instead.
			$default_confirmation = GFFormsModel::get_default_confirmation();
			$result               = GFFormDisplay::get_ajax_postback_html( $default_confirmation['message'] );
		}

		return $result;
	}

	//------------------------------------------------------
	//------------- PAGE/POST EDIT PAGE ---------------------

	/**
	 * Determines if the "Add Form" button should be added to the page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return boolean $display_add_form_button True if the page is supported.  False otherwise.
	 */
	public static function page_supports_add_form_button() {
		$display_add_form_button = ( ! class_exists( 'GF_Block' ) || class_exists( 'Classic_Editor' ) ) && in_array( RG_CURRENT_PAGE, array(
			'post.php',
			'page.php',
			'page-new.php',
			'post-new.php',
			'customize.php',
		) );

		/**
		 * Allows overriding which pages the add form button is added to.
		 *
		 * @since Unknown
		 *
		 * @param bool $display_add_form_button Indicates if the button should be added to the current page.
		 *
		 */
		return apply_filters( 'gform_display_add_form_button', $display_add_form_button );
	}

	/**
	 * Creates the "Add Form" button.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function add_form_button() {

		$is_add_form_page = self::page_supports_add_form_button();
		if ( ! $is_add_form_page ) {
			return;
		}

		// display button matching new UI
		echo '<style>.gform_media_icon{
                background-position: center center;
			    background-repeat: no-repeat;
			    background-size: 16px auto;
			    float: left;
			    height: 16px;
			    margin: 0;
			    text-align: center;
			    width: 16px;
				padding-top:10px;
                }
                .gform_media_icon:before{
                color: #999;
			    padding: 7px 0;
			    transition: all 0.1s ease-in-out 0s;
                }
                .wp-core-ui a.gform_media_link{
                 padding-left: 0.4em;
                }
             </style>
              <a href="#" class="button gform_media_link" id="add_gform" aria-label="' . esc_attr__( 'Add Gravity Form', 'gravityforms' ) . '"><div class="gform_media_icon svg" style="background-image: url(\'' . self::get_admin_icon_b64( '#0071a1' ) . '\')"><br /></div><div style="padding-left: 20px;">' . esc_html__( 'Add Form', 'gravityforms' ) . '</div></a>';
	}

	/**
	 * Displays the popup to insert a form to a post/page.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function add_mce_popup() {
		?>
		<script>
			function InsertForm() {
				var form_id = jQuery("#add_form_id").val();
				if (form_id == "") {
					alert(<?php echo json_encode( __( 'Please select a form', 'gravityforms' ) ); ?>);
					return;
				}

				var form_name = jQuery("#add_form_id option[value='" + form_id + "']").text().replace(/[\[\]]/g, '');
				var display_title = jQuery("#display_title").is(":checked");
				var display_description = jQuery("#display_description").is(":checked");
				var ajax = jQuery("#gform_ajax").is(":checked");
				var title_qs = !display_title ? " title=\"false\"" : "";
				var description_qs = !display_description ? " description=\"false\"" : "";
				var ajax_qs = ajax ? " ajax=\"true\"" : "";

				window.send_to_editor("[gravityform id=\"" + form_id + "\" name=\"" + form_name + "\"" + title_qs + description_qs + ajax_qs + "]");
			}
		</script>

		<div id="select_gravity_form" style="display:none;">

			<div id="gform-shortcode-ui-wrap" class="wrap <?php echo GFCommon::get_browser_class() ?>">

				<div id="gform-shortcode-ui-container"></div>

			</div>


		</div>

		<?php
	}


	//------------------------------------------------------
	//------------- PLUGINS PAGE ---------------------------
	//------------------------------------------------------

	/**
	 * Creates the Settings link within the Plugins page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array  $links Links associated with the plugin.
	 * @param string $file  The plugin filename.
	 *
	 * @return array $links Links associated with the plugin, after the Settings link is added.
	 */
	public static function plugin_settings_link( $links, $file ) {
		if ( $file != plugin_basename( __FILE__ ) ) {
			return $links;
		}

		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php' ) ) . '?page=gf_settings">' . esc_html__( 'Settings', 'gravityforms' ) . '</a>' );

		return $links;
	}

	/**
	 * Displays messages for the Gravity Forms listing on the Plugins page.
	 *
	 * Displays if the key is invalid or an update is available.
	 *
	 * @since  Unknown
	 * @since  2.4.15  Update to improve multisite updates.
	 * @access public
	 *
	 * @param string $plugin_name The plugin filename.  Immediately overwritten.
	 * @param array  $plugin_data An array of plugin data.
	 */
	public static function plugin_row( $plugin_name, $plugin_data ) {

		self::maybe_display_update_notification( $plugin_name, $plugin_data );

		$add_ons = gf_upgrade()->get_min_addon_requirements();

		if ( isset( $add_ons[ $plugin_name ] ) ) {
			$plugin_path = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_name;
			if ( ! file_exists( $plugin_path ) ) {
				return;
			}
			$plugin_data     = get_plugin_data( $plugin_path, false, false );
			$current_version = $plugin_data['Version'];
			$add_on          = $add_ons[ $plugin_name ];
			$min_version     = $add_on['min_version'];
			if ( version_compare( $current_version, $min_version, '<' ) ) {
				$name = $add_on['name'];
				/* translators: 1: The name of the add-on, 2: version number. */
				$message = esc_html__( 'This version of the %1$s is not compatible with the version of Gravity Forms that is installed. Upgrade this add-on to version %2$s or greater to avoid compatibility issues and potential loss of data.', 'gravityforms' );
				echo '</tr><tr class="plugin-update-tr"><td colspan="3" style="border-left: 4px solid #dc3232;"><div class="update-message">' . sprintf( $message, $name, $min_version ) . '</div></td>';
			}
		}
	}

	/**
	 * Display Gravity Forms and add-ons update notifications if needed.
	 *
	 * @since 2.4.15
	 *
	 * @param string $plugin_name The plugin filename.  Immediately overwritten.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $slug        The add-on slug.
	 * @param string $version     The add-on version.
	 */
	public static function maybe_display_update_notification( $plugin_name, $plugin_data, $slug = '', $version = '' ) {
		if ( empty( $slug ) && $plugin_name !== 'gravityforms/gravityforms.php' ) {
			return;
		}

		if ( empty( $slug ) ) {
			$version_info = GFCommon::get_version_info();
			$slug         = 'gravityforms';
			$version      = GFCommon::$version;
		} else {
			$version_info = rgars( GFCommon::get_version_info(), 'offerings/' . $slug );
		}

		$valid_key = rgar( GFCommon::get_version_info(), 'is_valid_key' );

		$message       = '';
		// Display the message only for a multisite network. A single site install doesn't need it (WP handles it).
		if ( ( is_multisite() && ! is_network_admin() ) && version_compare( $version, rgar( $version_info, 'version' ), '<' ) ) {
			$changelog_url = wp_nonce_url( self_admin_url( 'admin-ajax.php?action=gf_get_changelog&plugin=' . $slug . '&TB_iframe=true&width=640&height=808' ) );

			if ( ! current_user_can( 'update_plugins' ) || ! $valid_key ) {
				$message .= sprintf(
					/* translators: 1: plugin name, 2: open <a> tag, 3: version number, 4: close </a> tag */
					esc_html__( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s. ', 'gravityforms' ),
					$plugin_data['Name'],
					sprintf(
						/* translators: 1: plugin name, 2: version number, 3: changelog URL */
						__( '<a class="thickbox open-plugin-details-modal" aria-label="View %1$s version %2$s details" href="%3$s">', 'gravityforms' ),
						$plugin_data['Name'],
						$version_info['version'],
						$changelog_url
					),
					$version_info['version'],
					'</a>'
				);
			} else {
				$upgrade_url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&amp;plugin=' . urlencode( $plugin_name ) ), 'upgrade-plugin_' . $plugin_name );

				$message .= sprintf(
					/* translators: 1: plugin name, 2: open <a> tag, 3: version number, 4: close </a> tag, 5: open <a> tag 6. close </a> tag */
					esc_html__( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s or %5$supdate now%6$s. ', 'gravityforms' ),
					$plugin_data['Name'],
					sprintf(
						/* translators: 1: plugin name, 2: version number, 3: changelog URL */
						__( '<a class="thickbox open-plugin-details-modal" aria-label="View %1$s version %2$s details" href="%3$s">', 'gravityforms' ),
						$plugin_data['Name'],
						$version_info['version'],
						$changelog_url
					),
					$version_info['version'],
					'</a>',
					sprintf(
						/* translators: 1: upgrade URL, 2: plugin name */
						__( '<a href="%1$s" class="update-link" aria-label="Update %2$s now">', 'gravityforms' ),
						$upgrade_url,
						$plugin_data['Name']
					),
					'</a>'
				);
			}
		}

		if ( ! $valid_key ) {
			$message .= sprintf( esc_html__( '%sRegister%s your copy of Gravity Forms to receive access to automatic upgrades and support. Need a license key? %sPurchase one now%s.', 'gravityforms' ), '<a href="' . admin_url() . 'admin.php?page=gf_settings">', '</a>', '<a href="https://www.gravityforms.com">', '</a>' );
		}

		if ( ! empty( $message ) ) {
			if ( is_network_admin() ) {
				$active_class = is_plugin_active_for_network( $plugin_name ) ? ' active' : '';
			} else {
				$active_class = is_plugin_active( $plugin_name ) ? ' active' : '';
			}

			// Get the columns for this table so we can calculate the colspan attribute.
			$screen  = get_current_screen();
			$columns = get_column_headers( $screen );

			// If something went wrong with retrieving the columns, default to 3 for colspan.
			$colspan = ! is_countable( $columns ) ? 3 : count( $columns );

			echo '<tr class="plugin-update-tr' . $active_class . '" id="' . $slug . '-update" data-slug="' . $slug . '" data-plugin="' . $plugin_name . '">';
			echo '<td colspan="' . $colspan . '" class="plugin-update colspanchange">';
			echo '<div class="update-message notice inline notice-warning notice-alt">';
			echo '<p>';
			echo $message;
			echo '</p></div></td></tr>';

			// Apply the class "update" to the plugin row to get rid of the ugly border.
			echo "
				<script type='text/javascript'> 
					jQuery('#$slug-update').prev('tr').addClass('update'); 
				</script>
				";
		}
	}

	/**
	 * Hooks into in_plugin_update_message-gravityforms/gravityforms.php and displays an update message specifically for Gravity Forms 2.3.
	 *
	 * @param $args
	 * @param $response
	 */
	public static function in_plugin_update_message( $args, $response ) {
		if ( empty( $args['update'] ) ) {
			return;
		}

		if ( version_compare( $args['new_version'], '2.3', '>=' ) && version_compare( GFForms::$version, '2.3', '<' ) ) {

			$message = esc_html__( 'IMPORTANT: As this is a major update, we strongly recommend creating a backup of your site before updating.', 'gravityforms' );

			require_once( GFCommon::get_base_path() . '/includes/system-status/class-gf-update.php' );

			$updates = GF_Update::available_updates();

			$addons_requiring_updates = array();

			foreach ( $updates as $update ) {
				if ( $update['slug'] == 'gravityforms' ) {
					continue;
				}
				$update_available = version_compare( $update['installed_version'], $update['latest_version'], '<' );
				if ( $update_available ) {
					$addons_requiring_updates[] = $update['name'] . ' ' . $update['installed_version'];
				}
			}

			if ( count( $addons_requiring_updates ) > 0 ) {
				/* translators: %s: version number */
				$message .= '<br />' . sprintf( esc_html__( "The versions of the following add-ons you're running haven't been tested with Gravity Forms %s. Please update them or confirm compatibility before updating Gravity Forms, or you may experience issues:", 'gravityforms' ), $args['new_version'] );
				$message .= ' ' . join( ', ', $addons_requiring_updates );
			}

			echo sprintf( '<br /><br /><span style="display:inline-block;background-color: #d54e21; padding: 10px; color: #f9f9f9;">%s</span><br /><br />', $message );
		}

	}

	/**
	 * Displays current version details on Plugins page
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function display_changelog() {
		if ( $_REQUEST['plugin'] != 'gravityforms' ) {
			return;
		}

		$page_text = self::get_changelog();
		echo $page_text;

		exit;
	}

	/**
	 * Get changelog with admin-ajax.php in GFForms::maybe_display_update_notification().
	 *
	 * @since 2.4.15
	 */
	public static function ajax_display_changelog() {
		check_admin_referer();

		GFForms::display_changelog();
	}

	/**
	 * Gets the changelog for the newest version
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return string $page_text The changelog. Error message if there's an issue.
	 */
	public static function get_changelog() {
		$key                = GFCommon::get_key();
		$body               = "key=$key";
		$options            = array( 'method' => 'POST', 'timeout' => 3, 'body' => $body );
		$options['headers'] = array(
			'Content-Type'   => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
			'Content-Length' => strlen( $body ),
			'User-Agent'     => 'WordPress/' . get_bloginfo( 'version' ),
		);

		$raw_response = GFCommon::post_to_manager( 'changelog.php', GFCommon::get_remote_request_params(), $options );

		if ( is_wp_error( $raw_response ) || 200 != $raw_response['response']['code'] ) {
			$page_text = sprintf( esc_html__( 'Oops!! Something went wrong. %sPlease try again or %scontact us%s.', 'gravityforms' ), '<br/>', "<a href='https://www.gravityforms.com/support/'>", '</a>' );
		} else {
			$page_text = $raw_response['body'];
			if ( substr( $page_text, 0, 10 ) != '<!--GFM-->' ) {
				$page_text = '';
			} else {
				$page_text = '<div style="background-color:white">' . $page_text . '<div>';
			}
		}

		return stripslashes( $page_text );
	}

	//------------------------------------------------------
	//-------------- DASHBOARD PAGE -------------------------

	/**
	 * Registers the dashboard widget.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function dashboard_setup() {
		/**
		 * Changes the dashboard widget title
		 *
		 * @param string $dashboard_title The dashboard widget title.
		 */
		$dashboard_title = apply_filters( 'gform_dashboard_title', __( 'Gravity Forms', 'gravityforms' ) );
		wp_add_dashboard_widget( 'rg_forms_dashboard', $dashboard_title, array( 'GFForms', 'dashboard' ) );
	}

	/**
	 * Displays the dashboard UI.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function dashboard() {
		$forms = RGFormsModel::get_form_summary();

		if ( sizeof( $forms ) > 0 ) {
			?>
			<table class="widefat gf_dashboard_view" cellspacing="0" style="border:0px;">
				<thead>
				<tr>
					<td class="gf_dashboard_form_title_header" style="text-align:left; padding:8px 18px!important; font-weight:bold;">
						<i><?php esc_html_e( 'Title', 'gravityforms' ) ?></i></td>
					<td class="gf_dashboard_entries_unread_header" style="text-align:center; padding:8px 18px!important; font-weight:bold;">
						<i><?php esc_html_e( 'Unread', 'gravityforms' ) ?></i></td>
					<td class="gf_dashboard_entries_total_header" style="text-align:center; padding:8px 18px!important; font-weight:bold;">
						<i><?php esc_html_e( 'Total', 'gravityforms' ) ?></i></td>
				</tr>
				</thead>

				<tbody class="list:user user-list">
				<?php
				foreach ( $forms as $form ) {
					if ( $form['is_trash'] ) {
						continue;
					}

					$date_display = GFCommon::format_date( $form['last_entry_date'] );
					if ( ! empty( $form['total_entries'] ) ) {

						?>
						<tr class='author-self status-inherit' valign="top">
							<td class="gf_dashboard_form_title column-title" style="padding:8px 18px;">
								<a <?php echo $form['unread_count'] > 0 ? "class='form_title_unread' style='font-weight:bold;'" : '' ?> href="admin.php?page=gf_entries&view=entries&id=<?php echo absint( $form['id'] ) ?>"><?php echo esc_html( $form['title'] ) ?></a>
							</td>
							<td class="gf_dashboard_entries_unread column-date" style="padding:8px 18px; text-align:center;">
								<a <?php echo $form['unread_count'] > 0 ? "class='form_entries_unread' style='font-weight:bold;'" : '' ?> href="admin.php?page=gf_entries&view=entries&filter=unread&id=<?php echo absint( $form['id'] ) ?>" aria-label="<?php printf( esc_attr__( 'Last Entry: %s', 'gravityforms' ), $date_display ); ?>"><?php echo absint( $form['unread_count'] ) ?></a>
							</td>
							<td class="gf_dashboard_entries_total column-date" style="padding:8px 18px; text-align:center;">
								<a href="admin.php?page=gf_entries&view=entries&id=<?php echo absint( $form['id'] ) ?>" aria-label="<?php esc_attr_e( 'View All Entries', 'gravityforms' ) ?>"><?php echo absint( $form['total_entries'] ) ?></a>
							</td>
						</tr>
						<?php
					}
				}
				?>
				</tbody>
			</table>

			<?php if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) : ?>
				<p class="textright">
				<a class="gf_dashboard_button button" href="admin.php?page=gf_edit_forms"><?php esc_html_e( 'View All Forms', 'gravityforms' ) ?></a>
			<?php endif; ?>
			</p>
			<?php
		} else {
			?>
			<div class="gf_dashboard_noforms_notice">
				<?php echo sprintf( esc_html__( "You don't have any forms. Let's go %screate one %s!", 'gravityforms' ), '<a href="admin.php?page=gf_new_form">', '</a>' ); ?>
			</div>
			<?php
		}

		if ( GFCommon::current_user_can_any( 'gravityforms_view_updates' ) && ( ! function_exists( 'is_multisite' ) || ! is_multisite() || is_super_admin() ) ) {
			//displaying update message if there is an update and user has permission
			self::dashboard_update_message();
		}
	}

	/**
	 * Displays the update message on the dashboard.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function dashboard_update_message() {
		$version_info = GFCommon::get_version_info();
		if ( empty( $version_info['version'] ) ) {
			return;
		}

		//don't display a message if use has dismissed the message for this version
		$ary_dismissed = get_option( 'gf_dismissed_upgrades' );

		$is_dismissed = ! empty( $ary_dismissed ) && in_array( $version_info['version'], $ary_dismissed );

		if ( $is_dismissed ) {
			return;
		}

		if ( version_compare( GFForms::$version, $version_info['version'], '<' ) ) {
			$message = sprintf( esc_html__( 'There is an update available for Gravity Forms. %sView Details%s', 'gravityforms' ), "<a href='admin.php?page=gf_system_status&subview=updates'>", '</a>' );
			?>
			<div class='updated' style='padding:15px; position:relative;' id='gf_dashboard_message'><?php echo $message ?>
				<a href="javascript:void(0);" onclick="GFDismissUpgrade();" onkeypress="GFDismissUpgrade();" style='float:right;'><?php esc_html_e( 'Dismiss', 'gravityforms' ) ?></a>
			</div>
			<script type="text/javascript">
				function GFDismissUpgrade() {
					jQuery("#gf_dashboard_message").slideUp();
					jQuery.post(ajaxurl, {
						action : 'rg_dismiss_upgrade',
						version: <?php echo json_encode( $version_info['version'] ); ?>});
				}
			</script>
			<?php
		}
	}

	/**
	 * Dismisses the dashboard update message.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function dashboard_dismiss_upgrade() {
		$ary = get_option( 'gf_dismissed_upgrades' );
		if ( ! is_array( $ary ) ) {
			$ary = array();
		}

		$ary[] = $_POST['version'];
		update_option( 'gf_dismissed_upgrades', $ary );
	}


	//------------------------------------------------------
	//--------------- ALL OTHER PAGES ----------------------

	/**
	 * Registers Gravity Forms scripts.
	 *
	 * If SCRIPT_DEBUG constant is set, uses the un-minified version.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function register_scripts() {

		$base_url = GFCommon::get_base_url();
		$version  = GFForms::$version;
		$min      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		wp_register_script( 'gform_chosen', $base_url . "/js/chosen.jquery.min.js", array( 'jquery' ), $version );
		wp_register_script( 'gform_selectwoo', $base_url . "/js/vendor/selectWoo.full.js", array( 'jquery' ), $version );
		wp_register_script( 'gform_simplebar', $base_url . "/js/vendor/simplebar.js", array( 'jquery' ), $version );
		wp_register_script( 'gform_promises_polyfill', 'https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js', array(), $version );
		wp_register_script( 'gform_conditional_logic', $base_url . "/js/conditional_logic{$min}.js", array(
			'jquery',
			'gform_gravityforms'
		), $version );
		wp_register_script( 'gform_page_conditional_logic', $base_url . "/js/page_conditional_logic{$min}.js", array(
			'jquery',
			'gform_gravityforms'
		), $version );
		wp_register_script( 'gform_datepicker_legacy', $base_url . "/js/datepicker-legacy{$min}.js", array(), $version, true );
		wp_register_script( 'gform_datepicker_init', $base_url . "/js/datepicker{$min}.js", array(
			'jquery',
			'jquery-ui-datepicker',
			'gform_gravityforms',
			'gform_datepicker_legacy',
		), $version, true );
		wp_register_script( 'gform_form_editor_conditional_flyout', $base_url . "/js/components/form_editor/conditional_flyout/conditional_flyout{$min}.js", array(
			'jquery',
			'gform_promises_polyfill',
			'gform_gravityforms',
		), $version );
		wp_register_script( 'gform_form_admin', $base_url . "/js/form_admin{$min}.js", array(
			'jquery',
			'jquery-ui-autocomplete',
			'gform_placeholder',
			'gform_gravityforms',
			'gform_form_editor_conditional_flyout',
		), $version );
		wp_register_script( 'gform_layout_editor', $base_url . "/js/layout_editor{$min}.js", array(
			'jquery-ui-draggable',
			'jquery-ui-resizable',
			'jquery-ui-droppable',
			'jquery-touch-punch',
		), $version, true );
		wp_register_script( 'gform_form_editor', $base_url . "/js/form_editor{$min}.js", array(
			'jquery',
			'gform_json',
			'gform_placeholder',
			'gform_layout_editor',
		), $version, true );
		wp_register_script( 'gform_forms', $base_url . "/js/forms{$min}.js", array( 'jquery' ), $version );
		wp_register_script( 'gform_gravityforms_utils', $base_url . "/assets/js/dist/utils{$min}.js", array( 'gform_gravityforms_admin_vendors' ), $version, false );
		wp_register_script( 'gform_gravityforms_admin_components', $base_url . "/assets/js/dist/admin-components{$min}.js", array( 'gform_gravityforms_utils' ), $version, false );
		wp_register_script( 'gform_gravityforms', $base_url . "/js/gravityforms{$min}.js", array(
			'jquery',
			'gform_json',
		), $version, false );
		wp_register_script( 'gform_json', $base_url . "/js/jquery.json{$min}.js", array( 'jquery' ), $version, true );
		wp_register_script( 'gform_masked_input', $base_url . "/js/jquery.maskedinput{$min}.js", array( 'jquery' ), $version, true );
		wp_register_script( 'gform_menu', $base_url . "/js/menu{$min}.js", array( 'jquery' ), $version );
		wp_register_script( 'gform_placeholder', $base_url . '/js/placeholders.jquery.min.js', array( 'jquery' ), $version, true );
		wp_register_script( 'gform_tooltip_init', $base_url . "/js/tooltip_init{$min}.js", array( 'jquery-ui-tooltip' ), $version );
		wp_register_script( 'gform_textarea_counter', $base_url . "/js/jquery.textareaCounter.plugin{$min}.js", array( 'jquery' ), $version, true );
		wp_register_script( 'gform_field_filter', $base_url . "/js/gf_field_filter{$min}.js", array(
			'jquery',
			'gform_datepicker_init'
		), $version );
		wp_register_script( 'gform_shortcode_ui', $base_url . "/js/shortcode-ui{$min}.js", array(
			'jquery',
			'wp-backbone',
			'gform_gravityforms'
		), $version, true );
		wp_register_script( 'gform_gravityforms_admin_vendors', $base_url . "/assets/js/dist/vendor-admin{$min}.js", array(), $version, true );
		wp_register_script( 'gform_gravityforms_admin', $base_url . "/assets/js/dist/scripts-admin{$min}.js", array(
			'gform_gravityforms_admin_components',
            'gform_gravityforms_admin_vendors',
			'gform_simplebar',
		), $version, true );
		wp_register_script( 'gform_gravityforms_theme_vendors', $base_url . "/assets/js/dist/vendor-theme{$min}.js", array(
			'gform_gravityforms_utils',
			'gform_gravityforms',
			'gform_simplebar',
		), $version, true );
		wp_register_script( 'gform_gravityforms_theme', $base_url . "/assets/js/dist/scripts-theme{$min}.js", array(
			'gform_gravityforms_theme_vendors',
		), $version, true );
		wp_register_script( 'gform_system_report_clipboard', $base_url . '/includes/system-status/js/clipboard.min.js', array( 'jquery' ), $version, true );
		wp_register_script( 'gform_preview', $base_url . "/js/preview{$min}.js", array( 'jquery' ), $version, false );
		wp_register_script( 'gform_auto_updates', $base_url . "/js/auto_update{$min}.js", array( 'jquery' ), $version, true );
		wp_register_script( 'gform_plugin_settings', $base_url . "/js/plugin_settings{$min}.js", array(
			'jquery',
			'gform_gravityforms',
		), $version );

		wp_register_style( 'gform_admin_components', $base_url . "/assets/css/dist/admin-components{$min}.css", array( 'gform_admin_css_utilities' ), $version );
		wp_register_style( 'gform_admin_css_utilities', $base_url . "/assets/css/dist/admin-css-utilities{$min}.css", array(), $version );
		wp_register_style( 'gform_admin_ie11', $base_url . "/assets/css/dist/admin-ie11{$min}.css", null, $version );
		wp_register_style( 'gform_admin_icons', $base_url . "/assets/css/dist/admin-icons{$min}.css", array(), $version );
		wp_register_style( 'gform_admin', $base_url . "/assets/css/dist/admin{$min}.css", array(), $version );
		wp_register_style( 'gform_chosen', $base_url . "/legacy/css/chosen{$min}.css", array(), $version );
		wp_register_style( 'gform_shortcode_ui', $base_url . "/css/shortcode-ui{$min}.css", array(), $version );
		wp_register_style( 'gform_font_awesome', $base_url . "/assets/css/dist/font-awesome{$min}.css", null, $version );
		wp_register_style( 'gform_dashicons', $base_url . "/css/dashicons{$min}.css", array(), $version );
		wp_register_style( 'gform_settings', $base_url . "/assets/css/dist/settings{$min}.css", array(), $version );
		wp_register_style( 'gform_editor', $base_url . "/assets/css/dist/editor{$min}.css", array(), $version );
		wp_register_style( 'gform_admin_theme', $base_url . "/assets/css/dist/admin-theme{$min}.css", array(), $version );

		wp_register_style( 'gform_theme_components', $base_url . "/assets/css/dist/theme-components{$min}.css", array(), $version );
		wp_register_style( 'gforms_reset_css', $base_url . "/legacy/css/formreset{$min}.css", null, $version );
		wp_register_style( 'gforms_datepicker_css', $base_url . "/legacy/css/datepicker{$min}.css", null, $version );
		wp_register_style( 'gforms_formsmain_css', $base_url . "/legacy/css/formsmain{$min}.css", null, $version );
		wp_register_style( 'gforms_ready_class_css', $base_url . "/legacy/css/readyclass{$min}.css", null, $version );
		wp_register_style( 'gforms_browsers_css', $base_url . "/legacy/css/browsers{$min}.css", null, $version );
		wp_register_style( 'gforms_rtl_css', $base_url . "/legacy/css/rtl{$min}.css", null, $version );

		wp_register_style( 'gform_theme_ie11', $base_url . "/assets/css/dist/theme-ie11{$min}.css", null, $version );
		wp_register_style( 'gform_basic', $base_url . "/assets/css/dist/basic{$min}.css", null, $version );
		wp_register_style( 'gform_theme', $base_url . "/assets/css/dist/theme{$min}.css", array( 'gform_theme_components', 'gform_theme_ie11' ), $version );
	}

	/**
	 * Initialize all the actions and filters needed to output the JS hooks code.
	 *
	 * @since  2.5.2
	 * @access public
	 */
	public static function init_hook_vars() {
		$prio = 9999;
		$actions = array(
			'wp_enqueue_scripts',
			'gform_preview_header',
			'admin_enqueue_scripts'
		);

		// Localize our hook vars JS as late as possible to allow changes to enqueue processes.
		foreach( $actions as $action ) {
			add_action( $action, function() {
				self::localize_hook_vars();
			}, $prio );
		}

		// Append hooks to a form being output in a widget or elsewhere that isn't page content.
		add_filter( 'gform_get_form_filter', array( 'GFForms', 'maybe_prepend_hooks_js_script'), $prio );
		add_filter( 'gform_get_form_confirmation_filter', array( 'GFForms', 'maybe_prepend_hooks_js_script'), $prio );
	}

	 /**
	 * Determines if context requires the hooks javascript to be written to the page and prepends
	 * it to the form string if so
	 * @param $form_string string String containing the form markup.
	 *
	 * @since 2.5.14
	 *
	 * @return string Returns the original form string, or the form string prepended with the hooks scripts.
	 */
	public static function maybe_prepend_hooks_js_script( $form_string ) {

		$is_gf_ajax = ! empty( rgpost( 'gform_ajax' ) );
		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

		if ( $doing_ajax || $is_gf_ajax ) {
			return $form_string;
		}

		$needed = GFCommon::requires_gf_hooks_javascript();

		if ( ! $needed ) {
			return $form_string;
		}

		$scripts = GFCommon::get_hooks_javascript_code();

		if ( empty( $scripts ) ) {
			return $form_string;
		}

		return '<script type="text/javascript">' . $scripts . '</script>' . $form_string;
	}

	/**
	 * Add various actions to manually output the JS hooks code.
	 *
	 * @since  2.5.2
	 * @access public
	 */
	public static function load_hooks_with_actions() {
		add_action( 'gform_preview_header', array( 'GFCommon', 'output_hooks_javascript' ) );
		add_action( 'wp_head', array( 'GFCommon', 'output_hooks_javascript' ) );
		add_action( 'admin_head', array( 'GFCommon', 'output_hooks_javascript' ) );
		add_action( 'gform_pre_print_scripts', array( 'GFCommon', 'output_hooks_javascript' ) );
	}

	/**
	 * Use wp_add_inline_script to output the hooks JS programmatically.
	 *
	 * @since  2.5.2
	 * @access public
	 */
	public static function load_hooks_with_inline_script() {
		$needed = GFCommon::requires_gf_hooks_javascript();
		if ( ! $needed ) {
			return;
		}

		$hooks_code = GFCommon::get_hooks_javascript_code();
		wp_add_inline_script( 'gform_gravityforms', $hooks_code, 'before' );
	}

	/**
	 * Localize the JS hook vars we need for addAction, etc, taking into account context.
	 *
	 * @since  2.5.3
	 * @access public
	 */
	public static function localize_hook_vars() {
		/**
		 * Allow plugins to force the hook vars to output no matter what. Useful for certain edge-cases.
		 *
		 * @since  2.5.2
		 *
		 * @param bool $force_output Whether to force the script output.
		 *
		 * @return bool
		 */
		$force_output = apply_filters( 'gform_force_hooks_js_output', false );
		$is_enqueued  = wp_script_is( 'gform_gravityforms', 'enqueued' );
		$script       = wp_scripts()->query( 'gform_gravityforms' );

		if ( ( $is_enqueued || $force_output ) && ! function_exists( 'wp_add_inline_script' ) ) {
			self::load_hooks_with_actions();
			return;
		}

		if ( ! $is_enqueued && ! $force_output ) {
			return;
		}

		// if the script is enqueued in the footer, simply output the scripts in the header to ensure they exist,
		// otherwise, localize via wp_add_inline_script().
		if ( ! empty( $script->extra['group'] ) || empty ( $script ) ) {
			self::load_hooks_with_actions();
		} else {
			self::load_hooks_with_inline_script();
		}
	}

	/**
	 * Enqueues registered Gravity Forms scripts.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param null $hook Not used.
	 */
	public static function enqueue_admin_scripts( $hook ) {

		$scripts = array();
		$page    = self::get_page();

		switch ( $page ) {
			case 'new_form' :
			case 'form_list':
				$scripts = array(
					'gform_simplebar',
					'gform_gravityforms',
					'gform_gravityforms_admin',
					'gform_json',
					'gform_form_admin',
					'thickbox',
					'sack',
				);
				break;

			case 'form_settings':
				$scripts = array(
					'gform_simplebar',
					'gform_gravityforms',
					'gform_gravityforms_admin',
					'gform_forms',
					'gform_json',
					'gform_form_admin',
					'gform_placeholder',
					'jquery-ui-datepicker',
					'gform_masked_input',
					'jquery-ui-sortable',
					'sack',
				);
				break;

            case 'personal_data':
            case 'form_settings_' . rgget( 'subview' ):
                $scripts = array(
					'gform_gravityforms_admin',
				);
				break;

			case 'settings':
				$scripts = array(
					'gform_plugin_settings',
					'gform_gravityforms_admin',
				);
				break;

			case 'form_editor':
				$thickbox = 'thickbox';
				$scripts  = array(
					'gform_simplebar',
					$thickbox,
					'jquery-ui-core',
					'jquery-ui-sortable',
					'jquery-ui-draggable',
					'jquery-ui-droppable',
					'jquery-ui-tabs',
					'jquery-ui-accordion',
					'gform_gravityforms',
					'gform_gravityforms_admin',
					'gform_forms',
					'gform_json',
					'gform_form_admin',
					'gform_placeholder',
					'jquery-ui-autocomplete',
					'sack',
				);

				if ( wp_is_mobile() ) {
					$scripts[] = 'jquery-touch-punch';
				}

				break;

			case 'entry_detail':
				$scripts = array(
					'gform_simplebar',
					'gform_gravityforms',
					'gform_gravityforms_admin',
					'gform_json',
					'gform_form_admin',
					'sack',
					'postbox',
				);
				break;

			case 'entry_detail_edit':
				$scripts = array(
					'gform_simplebar',
					'gform_gravityforms',
					'gform_gravityforms_admin',
					'gform_form_admin',
					'plupload-all',
					'sack',
					'postbox',
				);
				break;

			case 'entry_list':
			case 'results':
				$scripts = array(
					'gform_simplebar',
					'wp-lists',
					'wp-ajax-response',
					'thickbox',
					'gform_json',
					'gform_field_filter',
					'gform_form_admin',
					'gform_gravityforms_admin',
					'sack',
				);
				break;

			case 'notification_list':
				$scripts = array(
					'gform_forms',
					'gform_json',
					'gform_form_admin',
					'gform_gravityforms_admin',
					'sack',
				);
				break;

			case 'notification_new':
			case 'notification_edit':
				$scripts = array(
					'gform_simplebar',
					'jquery-ui-autocomplete',
					'gform_gravityforms',
					'gform_gravityforms_admin',
					'gform_placeholder',
					'gform_form_admin',
					'gform_forms',
					'gform_json',
					'sack',
				);
				break;

			case 'confirmation':
				$scripts = array(
					'gform_simplebar',
					'gform_form_admin',
					'gform_forms',
					'gform_gravityforms',
					'gform_gravityforms_admin',
					'gform_placeholder',
					'gform_json',
					'wp-pointer',
					'sack',
				);
				break;

			case 'addons':
				$scripts = array(
					'thickbox',
					'gform_gravityforms_admin',
					'sack',
				);
				break;

			case 'export_entry':
				$scripts = array(
					'jquery-ui-datepicker',
					'gform_form_admin',
					'gform_gravityforms_admin',
					'gform_field_filter',
					'sack',
				);
				break;
			case 'updates' :
				$scripts = array(
					'thickbox',
					'gform_gravityforms_admin',
					'sack',
				);
				break;
			case 'system_status':
				$scripts = array(
					'gform_system_report_clipboard',
					'gform_gravityforms_admin',
					'thickbox',
					'gform_placeholder',
				);
				break;

		}

		if ( 'plugins.php' === $hook ) {
			wp_enqueue_script( 'gform_auto_updates' );
			wp_localize_script(
				'gform_auto_updates',
				'gf_update_ajax',
				array(
					'ajaxurl'      => admin_url( 'admin-ajax.php' ),
					'enable_text'  => __( 'Enable auto-updates', 'gravityforms' ),
					'disable_text' => __( 'Disable auto-updates', 'gravityforms' ),
				)
			);
		}

		if ( self::page_supports_add_form_button() ) {
			wp_enqueue_script( 'gform_shortcode_ui' );
			wp_enqueue_style( 'gform_shortcode_ui' );
			wp_localize_script( 'gform_shortcode_ui', 'gfShortcodeUIData', array(
				'shortcodes'      => self::get_shortcodes(),
				'previewNonce'    => wp_create_nonce( 'gf-shortcode-ui-preview' ),

				/**
				 * Allows the enabling (false) or disabling (true) of a shortcode preview of a form
				 *
				 * @param bool $preview_disabled Defaults to true.  False to enable.
				 */
				'previewDisabled' => apply_filters( 'gform_shortcode_preview_disabled', true ),
				'strings'         => array(
					'pleaseSelectAForm'   => wp_strip_all_tags( __( 'Please select a form.', 'gravityforms' ) ),
					'errorLoadingPreview' => wp_strip_all_tags( __( 'Failed to load the preview for this form.', 'gravityforms' ) ),
				)
			) );
		}

		if ( $page === 'form_editor' ) {
			$form_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
			$form_strings = array(
				'requiredIndicator' => GFFormsModel::get_required_indicator( $form_id ),
				'defaultSubmit'     => __( 'Submit', 'gravityforms' ),
			);
			wp_localize_script( 'gform_form_editor', 'gform_form_strings', $form_strings );
		}

		if ( self::has_members_plugin() && rgget( 'page' ) === 'roles' ) {
		    wp_enqueue_style( 'gform_dashicons' );
        }

		if ( empty( $scripts ) ) {
			return;
		}

		foreach ( $scripts as $script ) {
			wp_enqueue_script( $script );
		}

		GFCommon::localize_gform_gravityforms_multifile();
		GFCommon::localize_legacy_check( 'gform_layout_editor' );

	}

	/**
	 * Gets current page name.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return bool|string Page name or false.
	 *   Page names:
	 *
	 *   new_form
	 *   form_list
	 *   form_editor
	 *   form_settings
	 *   confirmation
	 *   notification_list
	 *   notification_new
	 *   notification_edit
	 *   entry_list
	 *   entry_detail
	 *   entry_detail_edit
	 *   settings
	 *   addons
	 *   export_entry
	 *   export_form
	 *   import_form
	 *   updates
	 */
	public static function get_page() {

		if ( rgget( 'page' ) == 'gf_new_form' ) {
			return 'new_form';
		}

		if ( rgget( 'page' ) == 'gf_edit_forms' && ! rgget( 'id' ) ) {
			return 'form_list';
		}

		if ( rgget( 'page' ) == 'gf_edit_forms' && ! rgget( 'view' ) ) {
			return 'form_editor';
		}

		if ( rgget( 'page' ) == 'gf_edit_forms' && rgget( 'view' ) == 'settings' && ( ! rgget( 'subview' ) || rgget( 'subview' ) == 'settings' ) ) {
			return 'form_settings';
		}

		if ( rgget( 'page' ) == 'gf_edit_forms' && rgget( 'view' ) == 'settings' && rgget( 'subview' ) == 'personal-data' ) {
			return 'personal_data';
		}

		if ( rgget( 'page' ) == 'gf_edit_forms' && rgget( 'view' ) == 'settings' && rgget( 'subview' ) == 'confirmation' ) {
			return 'confirmation';
		}

		if ( rgget( 'page' ) == 'gf_edit_forms' && rgget( 'view' ) == 'settings' && rgget( 'subview' ) == 'notification' && rgget( 'nid' ) ) {
			return 'notification_edit';
		}

		if ( rgget( 'page' ) == 'gf_edit_forms' && rgget( 'view' ) == 'settings' && rgget( 'subview' ) == 'notification' && isset( $_GET['nid'] ) ) {
			return 'notification_edit';
		}

		if ( rgget( 'page' ) == 'gf_edit_forms' && rgget( 'view' ) == 'settings' && rgget( 'subview' ) == 'notification' ) {
			return 'notification_list';
		}

		if ( rgget( 'page' ) == 'gf_edit_forms' && rgget( 'view' ) == 'settings' && rgget( 'subview' ) ) {
			return 'form_settings_' . rgget( 'subview' );
		}

		if ( rgget( 'page' ) == 'gf_entries' && ( ! rgget( 'view' ) || rgget( 'view' ) == 'entries' ) ) {
			return 'entry_list';
		}

		if ( rgget( 'page' ) == 'gf_entries' && rgget( 'view' ) == 'entry' && isset( $_POST['screen_mode'] ) && $_POST['screen_mode'] == 'edit' ) {
			return 'entry_detail_edit';
		}

		if ( rgget( 'page' ) == 'gf_entries' && rgget( 'view' ) == 'entry' ) {
			return 'entry_detail';
		}

		if ( rgget( 'page' ) == 'gf_settings' ) {
			return 'settings';
		}

		if ( rgget( 'page' ) == 'gf_addons' ) {
			return 'addons';
		}

		if ( rgget( 'page' ) == 'gf_entries' && strpos( rgget( 'view' ), 'gf_results' ) !== false ) {
			return 'results';
		}

		if ( rgget( 'page' ) == 'gf_export' && ( rgget( 'view' ) == 'export_entry' || ! isset( $_GET['view'] ) ) ) {
			return 'export_entry';
		}

		if ( rgget( 'page' ) == 'gf_export' && rgget( 'view' ) == 'export_form' ) {
			return 'export_form';
		}

		if ( rgget( 'page' ) == 'gf_export' && rgget( 'view' ) == 'import_form' ) {
			return 'import_form';
		}

		if ( rgget( 'page' ) == 'gf_system_status' ) {
			return rgget( 'subview' ) === 'updates' ? 'updates' : 'system_status';
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( ( isset( $_POST['form_id'] ) && rgpost( 'action' ) === 'rg_select_export_form' ) || ( isset( $_POST['export_form'] ) && rgpost( 'action' ) === 'gf_process_export' ) ) ) {
			return 'export_entry_ajax';
		}

		return false;
	}

	/**
	 * Gets the form.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDisplay::get_form()
	 * @uses   GFCommon::get_base_path()
	 */
	public static function get_form( $form_id, $display_title = true, $display_description = true, $force_display = false, $field_values = null, $ajax = false, $tabindex = 0 ) {
		require_once( GFCommon::get_base_path() . '/form_display.php' );

		return GFFormDisplay::get_form( $form_id, $display_title, $display_description, $force_display, $field_values, $ajax, $tabindex );
	}

	/**
	 * Runs when the Forms menu item is clicked.
	 *
	 * Checks to see if the installation wizard should be displayed instead.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function new_form() {

		if ( self::maybe_display_wizard() ) {
			return;
		};

		self::form_list_page();
	}

	/**
	 * Enqueues scripts
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDisplay::enqueue_scripts()
	 */
	public static function enqueue_scripts() {
		require_once( GFCommon::get_base_path() . '/form_display.php' );
		GFFormDisplay::enqueue_scripts();
	}

	/**
	 * Prints form scripts.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDisplay::print_form_scripts()
	 */
	public static function print_form_scripts( $form, $ajax ) {
		require_once( GFCommon::get_base_path() . '/form_display.php' );
		GFFormDisplay::print_form_scripts( $form, $ajax );
	}

	/**
	 * Displays the Forms page
	 *
	 * Passes everything off to GFFormDetail::forms_page
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDetail::forms_page()
	 */
	public static function forms_page( $form_id ) {
		$styles = array( 'jquery-ui-styles', 'gform_admin', 'gform_settings', 'gform_editor', 'gform_admin_theme' );
		if ( GFCommon::is_legacy_markup_enabled( $form_id ) ) {
		    $styles[] = 'gforms_ready_class_css';
		}
		wp_print_styles( $styles );

		GFFormDetail::forms_page( $form_id );
	}

	/**
	 * Runs the Gravity Forms settings page.
	 *
	 * Checks to see if the installation wizard should be displayed.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFSettings::settings_page()
	 */
	public static function settings_page() {

		if ( self::maybe_display_wizard() ) {
			return;
		};

		require_once( GFCommon::get_base_path() . '/settings.php' );
		GFSettings::settings_page();
	}

	/**
	 * Runs the Gravity Forms system status page.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @uses   GFSystemStatus::system_status_page()
	 */
	public static function system_status() {

		require_once( GFCommon::get_base_path() . '/includes/system-status/class-gf-system-status.php' );
		require_once( GFCommon::get_base_path() . '/includes/system-status/class-gf-system-report.php' );
		require_once( GFCommon::get_base_path() . '/includes/system-status/class-gf-update.php' );
		GF_System_Status::system_status_page();
	}

	/**
	 * Adds pages to the Gravity Forms Settings page
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFSettings::add_settings_page()
	 */
	public static function add_settings_page( $name, $handle = '', $icon_path = '' ) {
		require_once( GFCommon::get_base_path() . '/settings.php' );
		GFSettings::add_settings_page( $name, $handle, $icon_path );
	}

	/**
	 * Displays the help page
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFHelp::help_page()
	 */
	public static function help_page() {
		require_once( GFCommon::get_base_path() . '/help.php' );
		GFHelp::help_page();
	}

	/**
	 * Displays the Gravity Forms Export page
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFForms::maybe_display_wizard()
	 * @uses   GFExport::export_page()
	 */
	public static function export_page() {

		if ( self::maybe_display_wizard() ) {
			return;
		};

		require_once( GFCommon::get_base_path() . '/export.php' );
		GFExport::export_page();
	}

	/**
	 * Target for the wp_ajax_gf_process_export ajax action requested from the export entries page.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @uses   GFCommon::get_base_path()
	 * @uses   GFExport::ajax_process_export()
	 */
	public static function ajax_process_export() {

		require_once( GFCommon::get_base_path() . '/export.php' );
		GFExport::ajax_process_export();
	}

	/**
	 * Target for the wp_ajax_gf_download_export ajax action requested from the export entries page.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @uses   GFCommon::get_base_path()
	 * @uses   GFExport::ajax_download_export()
	 */
	public static function ajax_download_export() {

		require_once( GFCommon::get_base_path() . '/export.php' );
		GFExport::ajax_download_export();
	}

	/**
	 * Target for the wp_ajax_gf_dismiss_message ajax action requested from the Gravity Forms admin pages.
	 *
	 * @since  2.0.0
	 * @access public
	 *
	 * @uses   GFCommon::dismiss_message()
	 */
	public static function ajax_dismiss_message() {

		check_admin_referer( 'gf_dismissible_nonce', 'nonce' );

		$key = rgget( 'message_key' );
		$key = sanitize_key( $key );


		GFCommon::dismiss_message( $key );
	}

	/**
	 * Target for the wp_ajax_gf_disable_logging AJAX action requested from WordPress admin pages.
	 *
	 * @since  2.2.4.2
	 * @access public
	 *
	 * @uses   GFCommon::get_base_path()
	 * @uses   GFSettings::disable_logging()
	 */
	public static function ajax_disable_logging() {

		// Verify nonce.
		check_admin_referer( 'gf_disable_logging_nonce', 'nonce' );

		// Load Settings class.
		if ( ! class_exists( 'GFSettings' ) ) {
			require_once( GFCommon::get_base_path() . '/settings.php' );
		}

		// Disable logging.
		$disabled = GFSettings::disable_logging();

		if ( $disabled ) {
			wp_send_json_success( esc_html__( 'Logging disabled.', 'gravityforms' ) );
		} else {
			wp_send_json_error( esc_html__( 'Unable to disable logging.', 'gravityforms' ) );
		}

	}

	/**
	 * Target for the wp_ajax_gf_force_upgrade ajax action requested from the System Status page.
	 *
	 * Outputs a JSON string with the status and then triggers the background upgrader usually handled by the cron healthcheck.
	 *
	 * @since 2.3.0.4
	 */
	public static function ajax_force_upgrade() {

		check_ajax_referer( 'gf_force_upgrade', 'nonce' );

		if ( ! GFCommon::current_user_can_any( 'gravityforms_uninstall' ) ) {
			wp_die( -1, 403 );
		}

		$status_label = get_option( 'gform_upgrade_status' );

		if ( empty( $status_label ) ) {
			$status = 'complete';
			$status_label =  __( 'Finished', 'gravityforms' );
			$percent_complete = 100;
		} else {
			$status = 'in_progress';
			require_once( GFCommon::get_base_path() . '/includes/system-status/class-gf-system-report.php' );
			$percent_complete = GF_System_Report::get_upgrade_percent_complete();
		}

		$response = json_encode(
			array(
				'status' => $status,
				'status_label' => $status_label,
			    'percent' => (string) $percent_complete,
			)
		);

		echo $response;

		ob_end_flush();

		// Simuate the healthcheck cron.
		GFForms::$background_upgrader->handle_cron_healthcheck();

		// The healthcheck task will terminate anyway but exit just in case.
		exit;
	}

	/**
	 * Runs the add-ons page
	 *
	 * If the display wizard needs to be displayed, do that instead.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function addons_page() {

		GFCommon::gf_header();

		if ( self::maybe_display_wizard() ) {
			return;
		};

		wp_print_styles( array( 'thickbox', 'gform_settings' ) );

		$plugins           = get_plugins();
		$installed_plugins = array();
		foreach ( $plugins as $key => $plugin ) {
			$is_active                            = is_plugin_active( $key );
			$installed_plugin                     = array(
				'plugin'    => $key,
				'name'      => $plugin['Name'],
				'is_active' => $is_active
			);
			$installed_plugin['activation_url']   = $is_active ? '' : wp_nonce_url( "plugins.php?action=activate&plugin={$key}", "activate-plugin_{$key}" );
			$installed_plugin['deactivation_url'] = ! $is_active ? '' : wp_nonce_url( "plugins.php?action=deactivate&plugin={$key}", "deactivate-plugin_{$key}" );

			$installed_plugins[] = $installed_plugin;
		}

		$nonces = self::get_addon_nonces();

		$body    = array(
			'plugins' => urlencode( serialize( $installed_plugins ) ),
			'nonces'  => urlencode( serialize( $nonces ) ),
			'key'     => GFCommon::get_key()
		);
		$options = array( 'body' => $body, 'headers' => array(), 'timeout' => 15 );

		$raw_response = GFCommon::post_to_manager( 'api.php', "op=plugin_browser&{$_SERVER['QUERY_STRING']}", $options );

		if ( is_wp_error( $raw_response ) || $raw_response['response']['code'] != 200 ) {
			echo "<div class='error' style='margin-top:50px; padding:20px;'>" . esc_html__( 'Add-On browser is currently unavailable. Please try again later.', 'gravityforms' ) . '</div>';
		} else {
			echo GFCommon::get_remote_message();
			echo $raw_response['body'];
		}
	}

	/**
	 * Gets all add-on information.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $api    The API URL.
	 * @param string $action The action needed.  Determines the view.
	 * @param object $args   Additional arguments sent to the API
	 *
	 * @return bool|object API object if successful.  False if error.
	 */
	public static function get_addon_info( $api, $action, $args ) {

		if ( $action == 'plugin_information' && empty( $api ) && ( ! rgempty( 'rg', $_GET ) || $args->slug == 'gravityforms' ) ) {
			$key          = GFCommon::get_key();
			$raw_response = GFCommon::post_to_manager( 'api.php', "op=get_plugin&slug={$args->slug}&key={$key}", array() );

			if ( is_wp_error( $raw_response ) || $raw_response['response']['code'] != 200 ) {
				return false;
			}

			$plugin = unserialize( $raw_response['body'] );

			$api                = new stdClass();
			$api->name          = $plugin['title'];
			$api->version       = $plugin['version'];
			$api->download_link = $plugin['download_url'];
			$api->tested        = '10.0';

		}

		return $api;
	}

	/**
	 * Creates nonces for add-on installation pages.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array|bool $nonces The nonces if the API response is fine.  Otherwise, false.
	 */
	public static function get_addon_nonces() {

		$raw_response = GFCommon::post_to_manager( 'api.php', 'op=get_plugins', array() );

		if ( is_wp_error( $raw_response ) || $raw_response['response']['code'] != 200 ) {
			return false;
		}

		$addons = unserialize( $raw_response['body'] );
		$nonces = array();
		foreach ( $addons as $addon ) {
			$nonces[ $addon['key'] ] = wp_create_nonce( "install-plugin_{$addon['key']}" );
		}

		return $nonces;
	}

	/**
	 * Begins exports.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFExport::start_export()
	 */
	public static function start_export() {
		require_once( GFCommon::get_base_path() . '/export.php' );
		GFExport::start_export();
	}

	/**
	 * Get all post categories as option configs.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	public static function get_post_category_options() {
		$categories = get_categories(
			array(
				'hide_empty' => false,
			)
		);

		$response = array();

		foreach ( $categories as $cat ) {
			$response[] = array(
				'term_id' => $cat->term_id,
				'label'   => $cat->name,
			);
		}

		return $response;
	}

	/**
	 * Gets the post categories.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDetail::get_post_category_values()
	 */
	public static function get_post_category_values() {
		require_once( GFCommon::get_base_path() . '/form_detail.php' );
		GFFormDetail::get_post_category_values();
	}

	/**
	 * Get the address rule options for conditional logic.
	 *
	 * @since 2.5
	 *
	 * @param $form_id
	 *
	 * @return array Array of options.
	*/
	public static function get_address_rule_value_options( $form_id ) {
		$address_field = new GF_Field_Address();
		$address_types = $address_field->get_address_types( $form_id );
		$options = [];

		foreach ( $address_types as $type => $data ) {
			if ( $type === 'international' ) {
				$options[ $type ] = $address_field->get_countries();
				continue;
			}

			$options[ $type ] = $data['states'];
		}

		$options['international'] = $address_field->get_countries();

		return $options;
	}

	/**
	 * Gets and displays the rules for an address field, depending on the address type.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function get_address_rule_values_select() {

		$address_type = rgpost( 'address_type' );
		$value        = rgpost( 'value' );
		$id           = sanitize_text_field( rgpost( 'id' ) );
		$form_id      = absint( rgpost( 'form_id' ) );

		$address_field = new GF_Field_Address();
		$address_types = $address_field->get_address_types( $form_id );
		$markup        = '';

		$type_obj = $address_type && isset( $address_types[ $address_type ] ) ? $address_types[ $address_type ] : 'international';

		switch ( $address_type ) {
			case 'international':
				$items = $address_field->get_countries();
				break;
			default:
				$items = $type_obj['states'];
		}

		$markup = sprintf( '<select id="%1$s" name="%1$s" class="gfield_rule_select gfield_rule_value_dropdown">%2$s</select>', esc_attr( $id ), $address_field->get_state_dropdown( $items, $value ) );

		echo $markup;

		die();

	}

	/**
	 * Gets post categories for display in Notifications.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFNotification::get_post_category_values()
	 */
	public static function get_notification_post_category_values() {
		require_once( GFCommon::get_base_path() . '/notification.php' );
		GFNotification::get_post_category_values();
	}

	/**
	 * Fires off the entries page.
	 *
	 * Checks if the installation wizard is needed.  If so, does that instead.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFForms::maybe_display_wizard()
	 * @uses   GFEntryDetail::lead_detail_page()
	 * @uses   GFEntryList::all_entries_page()
	 */
	public static function all_leads_page() {

		if ( self::maybe_display_wizard() ) {
			return;
		};

		$view    = rgget( 'view' );
		$lead_id = rgget( 'lid' );

		if ( $view == 'entry' && ( rgget( 'lid' ) || ! rgblank( rgget( 'pos' ) ) ) ) {
			require_once( GFCommon::get_base_path() . '/entry_detail.php' );
			GFEntryDetail::lead_detail_page();
		} else if ( $view == 'entries' || empty( $view ) ) {
			require_once( GFCommon::get_base_path() . '/entry_list.php' );
			GFEntryList::all_entries_page();
		} else {
			$form_id = rgget( 'id' );
			$form_id = absint( $form_id );
			/**
			 * Fires when viewing entries of a certain form
			 *
			 * @since Unknown
			 *
			 * @param string $view    The current view/entry type
			 * @param string $form_id The current form ID
			 * @param string $lead_id The current entry ID
			 */
			do_action( 'gform_entries_view', $view, $form_id, $lead_id );
		}

	}

	/**
	 * Gets the Form List page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormList::form_list_page()
	 */
	public static function form_list_page() {
		require_once( GFCommon::get_base_path() . '/form_list.php' );
		GFFormList::form_list_page();
	}

	/**
	 * Handles the view when accessing specific forms
	 *
	 * If needed, displays the installation wizard instead.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFForms::maybe_display_wizard()
	 * @uses   GFCommon::ensure_wp_version()
	 * @uses   GFForms::get()
	 * @uses   GFEntryList::leads_page()
	 * @uses   GFEntryDetail::lead_detail_page()
	 * @uses   GFFormSettings::form_settings_page()
	 * @uses   GFForms::forms_page()
	 * @uses   GFForms::form_list_page()
	 */
	public static function forms() {
		if ( ! GFCommon::ensure_wp_version() ) {
			return;
		}

		if ( self::maybe_display_wizard() ) {
			return;
		};

		$id   = RGForms::get( 'id' );
		$view = RGForms::get( 'view' );

		if ( $view == 'entries' ) {
			require_once( GFCommon::get_base_path() . '/entry_list.php' );
			GFEntryList::leads_page( $id );
		} else if ( $view == 'entry' ) {
			require_once( GFCommon::get_base_path() . '/entry_detail.php' );
			GFEntryDetail::lead_detail_page();
		} else if ( $view == 'notification' ) {
			require_once( GFCommon::get_base_path() . '/notification.php' );
			//GFNotification::notification_page($id);
		} else if ( $view == 'settings' ) {
			require_once( GFCommon::get_base_path() . '/form_settings.php' );
			GFFormSettings::form_settings_page( $id );
		} else if ( empty( $view ) ) {
			if ( is_numeric( $id ) ) {
				self::forms_page( $id );
			} else {
				self::form_list_page();
			}
		}

		/**
		 * Fires an action based on the form view
		 *
		 * @since Unknown
		 *
		 * @param string $view The current view
		 * @param string $id   The form ID
		 */
		do_action( 'gform_view', $view, $id );

	}

	/**
	 * Obtains $_GET values or values from an array.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $name  The ID of a specific value.
	 * @param array  $array An optional array to search through.  Defaults to null.
	 *
	 * @return string The value.  Empty if not found.
	 */
	public static function get( $name, $array = null ) {
		if ( ! isset( $array ) ) {
			$array = $_GET;
		}

		if ( isset( $array[ $name ] ) ) {
			return $array[ $name ];
		}

		return '';
	}

	/**
	 * Obtains $_POST values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $name            The ID of the value to obtain
	 * @param bool   $do_stripslashes If stripslashes_deep should be run on the result.  Defaults to true.
	 *
	 * @return string The value.  Empty if not found.
	 */
	public static function post( $name, $do_stripslashes = true ) {

		if ( isset( $_POST[ $name ] ) ) {
			return $do_stripslashes ? stripslashes_deep( $_POST[ $name ] ) : $_POST[ $name ];
		}

		return '';
	}

	/**
	 * Resends failed notifications
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFCommon::send_notification()
	 */
	public static function resend_notifications() {

		check_admin_referer( 'gf_resend_notifications', 'gf_resend_notifications' );
		$form_id = absint( rgpost( 'formId' ) );
		$leads   = rgpost( 'leadIds' ); // may be a single ID or an array of IDs
		if ( 0 == $leads ) {
			// get all the lead ids for the current filter / search
			$filter = rgpost( 'filter' );
			$search = rgpost( 'search' );
			$star   = $filter == 'star' ? 1 : null;
			$read   = $filter == 'unread' ? 0 : null;
			$status = in_array( $filter, array( 'trash', 'spam' ) ) ? $filter : 'active';

			$search_criteria['status'] = $status;

			if ( $star ) {
				$search_criteria['field_filters'][] = array( 'key' => 'is_starred', 'value' => (bool) $star );
			}
			if ( ! is_null( $read ) ) {
				$search_criteria['field_filters'][] = array( 'key' => 'is_read', 'value' => (bool) $read );
			}

			$search_field_id = rgpost( 'fieldId' );

			if ( isset( $_POST['fieldId'] ) && $_POST['fieldId'] !== '' ) {
				$key            = $search_field_id;
				$val            = $search;
				$strpos_row_key = strpos( $search_field_id, '|' );
				if ( $strpos_row_key !== false ) { //multi-row
					$key_array = explode( '|', $search_field_id );
					$key       = $key_array[0];
					$val       = $key_array[1] . ':' . $val;
				}
				$search_criteria['field_filters'][] = array(
					'key'      => $key,
					'operator' => rgempty( 'operator', $_POST ) ? 'is' : rgpost( 'operator' ),
					'value'    => $val,
				);
			}

			$leads = GFFormsModel::search_lead_ids( $form_id, $search_criteria );
		} else {
			$leads = ! is_array( $leads ) ? array( $leads ) : $leads;
		}

		/**
		 * Filters the notifications to be re-sent
		 *
		 * @since Unknown
		 *
		 * @param array $form_meta The Form Object
		 * @param array $leads     The entry IDs
		 */
		$form = gf_apply_filters( array(
			'gform_before_resend_notifications',
			$form_id
		), RGFormsModel::get_form_meta( $form_id ), $leads );

		if ( empty( $leads ) || empty( $form ) ) {
			esc_html_e( 'There was an error while resending the notifications.', 'gravityforms' );
			die();
		};

		$notifications = json_decode( rgpost( 'notifications' ) );
		if ( ! is_array( $notifications ) ) {
			die( esc_html__( 'No notifications have been selected. Please select a notification to be sent.', 'gravityforms' ) );
		}

		if ( ! rgempty( 'sendTo', $_POST ) && ! GFCommon::is_valid_email_list( rgpost( 'sendTo' ) ) ) {
			die( sprintf( esc_html__( 'The %sSend To%s email address provided is not valid.', 'gravityforms' ), '<strong>', '</strong>' ) );
		}

		foreach ( $leads as $lead_id ) {

			$lead = RGFormsModel::get_lead( $lead_id );
			foreach ( $notifications as $notification_id ) {
				$notification = $form['notifications'][ $notification_id ];
				if ( ! $notification ) {
					continue;
				}

				//overriding To email if one was specified
				if ( rgpost( 'sendTo' ) ) {
					$notification['to']     = rgpost( 'sendTo' );
					$notification['toType'] = 'email';
				}

				/**
				 * Allow the resend notification email to be skipped
				 *
				 * @since 2.3
				 *
				 * @param bool  $abort_email  Should we prevent this email being sent?
				 * @param array $notification The current notification object.
				 * @param array $form         The current form object.
				 * @param array $lead         The current entry object.
				 */
				$abort_email = apply_filters( 'gform_disable_resend_notification', false, $notification, $form, $lead );

				if ( ! $abort_email ) {
					GFCommon::send_notification( $notification, $form, $lead );
				}

				/**
				 * Fires after the current notification processing is finished
				 *
				 * @since 2.3
				 *
				 * @param array $notification The current notification object.
				 * @param array $form         The current form object.
				 * @param array $lead         The current entry object.
				 */
				do_action( 'gform_post_resend_notification', $notification, $form, $lead );
			}
		}

		/**
		 * Fires after the resend notifications processing is finished
		 *
		 * @since 2.3
		 *
		 * @param array $form The current form object.
		 * @param array $lead The current entry object.
		 */
		do_action( 'gform_post_resend_all_notifications', $form, $lead );

		die();
	}

	//-------------------------------------------------
	//----------- AJAX CALLS --------------------------

	/**
	 * Gets the CAPTCHA image for the form editor and displays it.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function captcha_image() {
		$field_properties = array(
			'type'                         => 'captcha',
			'simpleCaptchaSize'            => $_GET['size'],
			'simpleCaptchaFontColor'       => $_GET['fg'],
			'simpleCaptchaBackgroundColor' => $_GET['bg']
		);
		/* @var GF_Field_CAPTCHA $field */
		$field = GF_Fields::create( $field_properties );
		if ( $_GET['type'] == 'math' ) {
			$captcha = $field->get_math_captcha( $_GET['pos'] );
		} else {
			$captcha = $field->get_captcha();
		}

		@ini_set( 'memory_limit', '256M' );
		$image = imagecreatefrompng( $captcha['path'] );

		include_once( ABSPATH . 'wp-admin/includes/image-edit.php' );
		wp_stream_image( $image, 'image/png', 0 );
		imagedestroy( $image );
		die();
	}

	/**
	 * Updates the form status (active/inactive).
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormsModel::update_form_active()
	 */
	public static function update_form_active() {
		check_ajax_referer( 'rg_update_form_active', 'rg_update_form_active' );

		if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			GFFormsModel::update_form_active( $_POST['form_id'], $_POST['is_active'] );
		} else {
			wp_die( -1, 403 );
		}
	}

	/**
	 * Updates the notification status (active/inactive).
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormsModel::update_notification_active()
	 */
	public static function update_notification_active() {
		check_ajax_referer( 'rg_update_notification_active', 'rg_update_notification_active' );

		if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			GFFormsModel::update_notification_active( $_POST['form_id'], $_POST['notification_id'], $_POST['is_active'] );
		} else {
			wp_die( -1, 403 );
		}
	}

	/**
	 * Updates the confirmation status (active/inactive).
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @since  GFFormsModel::update_confirmation_active()
	 */
	public static function update_confirmation_active() {
		check_ajax_referer( 'rg_update_confirmation_active', 'rg_update_confirmation_active' );

		if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			GFFormsModel::update_confirmation_active( $_POST['form_id'], $_POST['confirmation_id'], $_POST['is_active'] );
		} else {
			wp_die( -1, 403 );
		}
	}

	/**
	 * Updates the entry properties.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFFormsModel::update_entry_property()
	 */
	public static function update_lead_property() {
		check_ajax_referer( 'rg_update_lead_property', 'rg_update_lead_property' );
		if ( GFCommon::current_user_can_any( 'gravityforms_edit_entries' ) ) {
			GFFormsModel::update_entry_property( $_POST['lead_id'], $_POST['name'], $_POST['value'] );
		} else {
			wp_die( -1, 403 );
		}
	}

	/**
	 * Updates the entry status.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormsModel::update_lead_property()
	 * @uses   GFFormsModel::delete_lead()
	 */
	public static function update_lead_status() {
		check_ajax_referer( 'gf_delete_entry' );

		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_entries' ) ) {
			wp_die( -1, 403 );
		}

		$status  = rgpost( 'status' );
		$lead_id = rgpost( 'entry' );

		$entry = GFAPI::get_entry( $lead_id );
		$form  = GFAPI::get_form( $entry['form_id'] );

		switch ( $status ) {
			case 'unspam':
				GFFormsModel::update_entry_property( $lead_id, 'status', 'active' );
				break;

			case 'restore':
				if ( GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
					GFFormsModel::update_entry_property( $lead_id, 'status', 'active' );
				}
				break;

			case 'delete':
				if ( GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
					GFFormsModel::delete_entry( $lead_id );
				}
				break;

			case 'trash':
				if ( GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
					GFFormsModel::update_entry_property( $lead_id, 'status', 'trash' );
				}
				break;

			default :
				GFFormsModel::update_entry_property( $lead_id, 'status', $status );
				break;
		}
		require_once( 'entry_list.php' );


		$filter_links = GFEntryList::get_filter_links( $form );

		$counts = array();
		foreach ( $filter_links as $filter_link ) {
			$id                       = $filter_link['id'] == '' ? 'all' : $filter_link['id'];
			$counts[ $id . '_count' ] = $filter_link['count'];
		}

		$x = new WP_Ajax_Response();
		$x->add( array(
			'what'         => 'gf_entry',
			'id'           => $lead_id,
			'supplemental' => $counts,
		) );
		$x->send();
	}

	// Settings
	/**
	 * Runs the license upgrade.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFSettings::upgrade_license()
	 */
	public static function upgrade_license() {
		require_once( GFCommon::get_base_path() . '/settings.php' );
		GFSettings::upgrade_license();
	}

	// Form detail
	/**
	 * Saves the form in the form editor.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDetail::save_form()
	 */
	public static function save_form() {
		require_once( GFCommon::get_base_path() . '/form_detail.php' );
		GFFormDetail::save_form();
	}

	/**
	 * Adds fields in the form editor.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDetail::add_field()
	 */
	public static function add_field() {
		require_once( GFCommon::get_base_path() . '/form_detail.php' );
		GFFormDetail::add_field();
	}

	/**
	 * Duplicates fields in the form editor.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDetail::duplicate_field()
	 */
	public static function duplicate_field() {
		require_once( GFCommon::get_base_path() . '/form_detail.php' );
		GFFormDetail::duplicate_field();
	}

	/**
	 * Deletes fields in the form editor.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   \GFFormDetail::delete_field()
	 */
	public static function delete_field() {
		require_once( GFCommon::get_base_path() . '/form_detail.php' );
		GFFormDetail::delete_field();
	}

	/**
	 * Changes the input type in the form editor.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDetail::change_input_type()
	 */
	public static function change_input_type() {
		require_once( GFCommon::get_base_path() . '/form_detail.php' );
		GFFormDetail::change_input_type();
	}

	/**
	 * Refreshes the field preview.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   \GFFormDetail::refresh_field_preview
	 */
	public static function refresh_field_preview() {
		require_once( GFCommon::get_base_path() . '/form_detail.php' );
		GFFormDetail::refresh_field_preview();
	}

	/**
	 * Deletes custom choices from radio/checkbox/select/etc fields.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDetail::delete_custom_choice()
	 */
	public static function delete_custom_choice() {
		require_once( GFCommon::get_base_path() . '/form_detail.php' );
		GFFormDetail::delete_custom_choice();
	}

	/**
	 * Saves custom choices from radio/checkbox/select/etc fields.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormDetail::save_custom_choice()
	 */
	public static function save_custom_choice() {
		require_once( GFCommon::get_base_path() . '/form_detail.php' );
		GFFormDetail::save_custom_choice();
	}

	/**
	 * Deletes a file from the entry detail view.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormsModel::delete_file()
	 */
	public static function delete_file() {
		check_ajax_referer( 'rg_delete_file', 'rg_delete_file' );

		if ( ! GFCommon::current_user_can_any( 'gravityforms_delete_entries' ) ) {
			wp_die( -1, 403 );
		}

		$lead_id    = intval( $_POST['lead_id'] );
		$field_id   = intval( $_POST['field_id'] );
		$file_index = intval( $_POST['file_index'] );

		RGFormsModel::delete_file( $lead_id, $field_id, $file_index );
		die( "EndDeleteFile($field_id, $file_index);" );
	}

	/**
	 * Gets the form export data.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormsModel::get_form_meta()
	 */
	public static function select_export_form() {
		check_ajax_referer( 'rg_select_export_form', 'rg_select_export_form' );

		if ( ! GFCommon::current_user_can_any( 'gravityforms_export_entries' ) ) {
			wp_die( -1, 403 );
		}

		$form_id = intval( $_POST['form_id'] );
		$form    = RGFormsModel::get_form_meta( $form_id );

		/**
		 * Filters through the Form Export Page
		 *
		 * @since Unknown
		 *
		 * @param int $form The Form Object of the form to export
		 */
		$form = gf_apply_filters( array( 'gform_form_export_page', $form_id ), $form );

		$filter_settings      = GFCommon::get_field_filter_settings( $form );
		$filter_settings_json = json_encode( $filter_settings );
		$fields               = array();

		$form = GFExport::add_default_export_fields( $form );

		if ( is_array( $form['fields'] ) ) {
			/* @var GF_Field $field */
			foreach ( $form['fields'] as $field ) {
				$inputs = $field->get_entry_inputs();
				if ( is_array( $inputs ) ) {
					foreach ( $inputs as $input ) {
						$fields[] = array( $input['id'], GFCommon::get_label( $field, $input['id'] ) );
					}
				} else if ( ! $field->displayOnly ) {
					$fields[] = array( $field->id, GFCommon::get_label( $field ) );
				}
			}
		}
		$field_json = GFCommon::json_encode( $fields );

		die( "EndSelectExportForm($field_json, $filter_settings_json);" );
	}

	/**
	 * Saves a form confirmation.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormSettings::save_confirmation()
	 */
	//	public static function save_confirmation() {
	//		require_once( GFCommon::get_base_path() . '/form_settings.php' );
	//		GFFormSettings::save_confirmation();
	//	}

	/**
	 * Saves the form title.
	 *
	 * Called via AJAX.
	 *
	 * @since  2.0.2.5
	 * @access public
	 *
	 * @uses   GFFormSettings::save_form_title()
	 */
	public static function save_form_title() {
		require_once( GFCommon::get_base_path() . '/form_settings.php' );
		GFFormSettings::save_form_title();
	}

	/**
	 * Deletes a form confirmation.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormSettings::delete_confirmation()
	 */
	public static function delete_confirmation() {
		require_once( GFCommon::get_base_path() . '/form_settings.php' );
		GFFormSettings::delete_confirmation();
	}

	// Form list
	/**
	 * Saves a new form.
	 *
	 * Called via AJAX.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormList::save_new_form()
	 */
	public static function save_new_form() {
		require_once( GFCommon::get_base_path() . '/form_list.php' );
		GFFormList::save_new_form();
	}

	/**
	 * Used to check that background tasks are working.
	 *
	 * @since 2.3
	 */
	public static function check_background_tasks() {
		check_ajax_referer( 'gf_check_background_tasks', 'nonce' );
		echo 'ok';
		die();
	}

	/**
	 * Displays the edit title popup.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $form The Form Object.
	 */
	public static function edit_form_title( $form ) {

		//Only allow users with form edit permissions to edit forms
		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			return;
		}

		?>

		<div id="edit-title-container" class="add_field_button_container">
			<div class="button-title-link gf_button_title_active">
				<div id="edit-title-header">
					<?php esc_html_e( 'Form Title', 'gravityforms' ); ?>
					<span id="edit-title-close" onclick="GF_CloseEditTitle();"><i class="fa fa-times"></i></span>
				</div>
			</div>
			<div class="add-buttons">
				<input type="text" id='edit-title-input' value='<?php echo esc_attr( $form['title'] ); ?>' />

				<div class="edit-form-footer">
					<input type="button" value="<?php esc_html_e( 'Update', 'gravityforms' ); ?>" class="button-primary" onclick="GF_SaveTitle();" />
					<span id="gform_settings_page_title_error"></span>
				</div>
			</div>

		</div>

		<script type="text/javascript">
			function GF_ShowEditTitle() {
				jQuery('#edit-title-container').css('visibility', 'visible')
					.find('#edit-title-input').focus();
			}

			function GF_CloseEditTitle() {
				jQuery('#edit-title-container').css('visibility', 'hidden');

				jQuery('#edit-title-input').val( jQuery('#gform_settings_page_title').text() );
				jQuery('#gform_settings_page_title_error').text('');
			}

			function GF_SaveTitle(){

				var title = jQuery( '#edit-title-input' ).val();

				jQuery.post(ajaxurl, {
					action       : "gf_save_title",
					gf_save_title: '<?php echo wp_create_nonce( 'gf_save_title' ); ?>',
					title        : jQuery.toJSON(title),
					formId       : '<?php echo absint( $form['id'] ); ?>'
				})
					.done(function (data) {

						result = jQuery.parseJSON(data);
						var isValid = result && result.isValid;

						if ( !isValid ) {

							var errorMessage = result ? result.message : '<?php esc_attr_e( 'Oops! There was an error saving the form title. Please refresh the page and try again.', 'gravityforms' ); ?>' ;

							jQuery('#gform_settings_page_title_error').text(errorMessage);
						}
						else {
							var title = jQuery('#edit-title-input').val();
							jQuery('#gform_settings_page_title').text(title);
							jQuery('#form_title_input').val(title);
							<?php echo GFCommon::is_form_editor() ? 'form.title = title;' : ''; ?>

							GF_CloseEditTitle();
						}


					})
					.fail(function () {
						alert('<?php esc_attr_e( 'Oops! There was an error saving the form title. Please refresh the page and try again.', 'gravityforms' ); ?>');
						GF_CloseEditTitle();
					});

			}

			function GF_IsOutsideTitleWindow(element) {
				var parents = jQuery(element).parents('#edit-title-container');
				return parents.length == 0;
			}

			jQuery(document).mousedown(function (event) {
				if (GF_IsOutsideTitleWindow(event.target)) {
					GF_CloseEditTitle();
				}
			});

			jQuery(document).ready(function () {
				jQuery('#edit-title-input').keypress(function (event) {
					if (event.keyCode == 13) {
						GF_SaveTitle();
					}
				});
			});

		</script>

		<?php
	}

	/**
	 * Displays the form switcher dropdown.
	 *
	 * @param string $title The form title
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function form_switcher( $title = '' ) {

		/**
		 * Get forms to be displayed in Form Switcher dropdown.
		 *
		 * @since 2.4.16
		 *
		 * @param array $all_forms All available Form objects, sorted by title.
		 */
		$all_forms = apply_filters( 'gform_form_switcher_forms', GFFormsModel::get_forms( null, 'title' ) );

		// Sort forms by active state.
		$forms              = array( 'active' => array(), 'inactive' => array(), );
		$results_page_forms = array();

		foreach ( $all_forms as $form ) {

			$results_slug = self::get_form_switcher_results_page_slug( $form );
			if ( $results_slug ) {
				$results_page_forms[ $form->id ] = $results_slug;
			}

			if ( '1' === $form->is_active ) {
				$forms['active'][] = $form;
			} elseif ( '0' === $form->is_active ) {
				$forms['inactive'][] = $form;
			}

			if ( '1' === $form->is_active ) {
				$forms['active'][] = $form;
			} else if ( '0' === $form->is_active ) {
				$forms['inactive'][] = $form;
			}
		}

		?>

		<article class="gform-dropdown" data-js="gform-form-switcher">
			<span
				class="gform-visually-hidden"
				id="gform-form-switcher-label"
			><?php esc_attr_e( 'Select a different form', 'gravityforms' ) ?></span>
			<button
				aria-expanded="false"
				aria-haspopup="listbox"
				aria-labelledby="gform-form-switcher-label gform-form-switcher-control"
				class="gform-dropdown__control"
				data-js="gform-dropdown-control"
				id="gform-form-switcher-control"
			>
				<span class="gform-dropdown__control-text" data-js="gform-dropdown-control-text"><?php echo esc_html( $title ); ?></span>
				<i class="gform-spinner gform-dropdown__spinner"></i>
				<i class="gform-icon gform-icon--chevron gform-dropdown__chevron"></i>
			</button>
			<div
				aria-labelledby="gform-form-switcher-label"
				class="gform-dropdown__container"
				role="listbox"
				tabindex="-1"
			>
				<div class="gform-dropdown__search">
					<label for="gform-form-switcher-search" class="gform-visually-hidden"><?php esc_attr_e( 'Search forms', 'gravityforms' ) ?></label>
					<input
						id="gform-form-switcher-search"
						type="text" class="gform-input gform-dropdown__search-input"
						placeholder="<?php esc_attr_e( 'Search for form', 'gravityforms' ) ?>"
						data-js="gform-dropdown-search"
					/>
					<i class="gform-icon gform-icon--search gform-dropdown__search-icon"></i>
				</div>
				<div class="gform-dropdown__list-container" data-simplebar<?php echo is_rtl() ? ' data-simplebar-direction="rtl"' : ''; ?>>
					<ul class="gform-dropdown__list">
					<?php
						foreach ( $all_forms as $form_info ) {
							printf(
								'
									<li class="gform-dropdown__item">
										<button class="gform-dropdown__trigger" data-js="gform-dropdown-trigger" data-value="%1$d" data-results-slug="%2$s" >
											<span class="gform-dropdown__trigger-text" data-value="%1$d">%3$s</span>
										</button>
									</li>
									',
								absint( $form_info->id ),
								rgar( $results_page_forms, $form_info->id ),
								esc_html( $form_info->title )
							);
						}
					?>
					</ul>
				</div>
			</div>
		</article>

		<script type="text/javascript">

			function GF_ReplaceQuery(key, newValue) {
				var new_query = "";
				var query = document.location.search.substring(1);
				var ary = query.split("&");
				var has_key = false;
				for (i = 0; i < ary.length; i++) {
					var key_value = ary[i].split("=");

					if (key_value[0] == key) {
						new_query += key + "=" + newValue + "&";
						has_key = true;
					}
					else if (key_value[0] != "display_settings") {
						new_query += key_value[0] + "=" + key_value[1] + "&";
					}
				}

				if (new_query.length > 0)
					new_query = new_query.substring(0, new_query.length - 1);

				if (!has_key)
					new_query += new_query.length > 0 ? "&" + key + "=" + newValue : "?" + key + "=" + newValue;

				return new_query;
			}

			function GF_RemoveQuery(key, query) {
				var new_query = "";
				if (query == "") {
					query = document.location.search.substring(1);
				}
				var ary = query.split("&");
				for (i = 0; i < ary.length; i++) {
					var key_value = ary[i].split("=");

					if (key_value[0] != key) {
						new_query += key_value[0] + "=" + key_value[1] + "&";
					}
				}

				if (new_query.length > 0)
					new_query = new_query.substring(0, new_query.length - 1);

				return new_query;
			}

			function GF_SwitchForm(id) {
				if (id.length > 0) {
					id = parseInt(id);
					var query = GF_ReplaceQuery('id', id);

					//remove paging from querystring when changing forms
					var new_query = GF_RemoveQuery('paged', query);
					new_query = new_query.replace('gf_new_form', 'gf_edit_forms');

					//remove filter vars from querystring when changing forms
					new_query = GF_RemoveQuery('s', new_query);
					new_query = GF_RemoveQuery('operator', new_query);
					new_query = GF_RemoveQuery('type', new_query);
					new_query = GF_RemoveQuery('field_id', new_query);
					new_query = GF_RemoveQuery('lid', new_query);
					new_query = GF_RemoveQuery('filter', new_query);
					new_query = GF_RemoveQuery('pos', new_query);

					//When switching forms within any form settings tab, go back to main form settings tab
					var is_form_settings = new_query.indexOf('page=gf_edit_forms') >= 0 && new_query.indexOf('view=settings') >= 0;
					if (is_form_settings) {
						//going back to main form settings tab
						new_query = 'page=gf_edit_forms&view=settings&id=' + id;
					}

					// Check if this is the results page of an add-on that supports results
					var is_results_page =  new_query.indexOf( 'gf_results_' ) >= 0;
					var is_form_entries = ( new_query.indexOf('page=gf_entries') >= 0 ) && ! is_results_page;
					//When switching forms within any form entries tab, go back to main form entries tab, if it is not aa results page.
					if ( is_form_entries ) {
						//going back to main form settings tab
						new_query = 'page=gf_entries&id='+ id;
					}

					// If this is a results/sales page, we need to decide if the form the user is being redirected to also has a results/sales page/
					if ( is_results_page ) {
						// Get the clicked item.
						var $clicked_item = jQuery( '.gform-dropdown__trigger[data-value=' + id + ']' );
						// If the clicked item has the data-results-slug with a value then this form has a results/sales page
						// this attribute is added while rendering the view, check GF_Form_Switcher::render()
						if ( $clicked_item &&  ( $clicked_item.data( 'results-slug') ) !== '' && ( $clicked_item.data( 'results-slug') ) !== undefined ) {
							new_query = 'page=gf_entries&view=gf_results_' + $clicked_item.data( 'results-slug')  + '&id=' + id;
						} else {
							// No results/sales redirect to entries list.
							new_query = 'page=gf_entries&id=' + id;
						}
					}

					document.location = '?' + new_query;
				}
			}

			function ToggleFormSettings() {
				FieldClick(jQuery('#gform_heading')[0]);
			}

			gform.instances.formSwitcher = new gform.components.dropdown( {
				detectTitleLength: true,
				onItemSelect: GF_SwitchForm,
				reveal: 'hover',
				selector: 'gform-form-switcher',
				showSpinner: true,
			} );

			jQuery(document).ready(function () {
				if (document.location.search.indexOf("display_settings") > 0)
					ToggleFormSettings()

				jQuery('a.gf_toolbar_disabled').click(function (event) {
					event.preventDefault();
				});
			});


		</script>
		<?php
	}

	/**
	 * Checks if the form has a results/sales page and returns the slug of the add-on that implements the page.
	 *
	 * @since 2.5.13
	 *
	 * @param Object|array $form The form object
	 *
	 * @return string|int  The slug string if found, 0 if not found
	 */
	protected static function get_form_switcher_results_page_slug( $form ) {

		// can't store boolean in cache.
		$results_addon_slug = 0;

		if ( is_a( $form,'stdClass') ) {
			$form = (array) $form;
		}

		$cached_result =  GFCache::get( 'has_results_page_' . $form['id'] );
		if ( $cached_result !== false ) {
			return $cached_result;
		}

		$form = GFAPI::get_form( rgar( $form, 'id' ) );

		if ( rgar( $form, 'id' ) && rgar( $form, 'fields' ) ) {

			foreach ( GFAddOn::get_results_addon() as $results_addon  ) {

				$addon = rgars( $results_addon, 'callbacks/fields/0');

				if (
					$addon
					 && is_a( $addon, 'GFAddOn' )
					 && method_exists( $addon, 'results_fields')
					 && $addon->results_fields( $form )
				) {
						$results_addon_slug = $addon->get_slug();
				  }
			}

		}

		GFCache::set( 'has_results_page_' . rgar( $form, 'id' ), $results_addon_slug, true, HOUR_IN_SECONDS );

		return $results_addon_slug;

	}

	/**
	*
	* Displays header for admin settings pages.
	*
	* @since 2.5
	*
	* @param array $tabs
	* @param bool $toolbar
	*
	*/
	public static function admin_header( $tabs = array(), $toolbar = true ) {
		// Print admin styles.
		wp_print_styles( array( 'jquery-ui-styles', 'gform_admin', 'gform_settings', 'wp-pointer' ) );

		// Set class for display mode on entries list page.
		$view_class = null;
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'gf_entries' && ! isset( $_GET['lid'] ) ) {
			if ( class_exists( 'GFEntryList' ) ) {
				$option_values = GFEntryList::get_screen_options_values();
				$view_class    = ( $option_values['display_mode'] === 'full_width' ) ? ' gform_form_settings_wrap--full-width' : null;
			}
		}
		?>
		<div class="wrap gforms_edit_form gforms_form_settings_wrap <?php echo GFCommon::get_browser_class() . $view_class; ?>">

		<?php GFCommon::gf_header(); ?>

		<?php

			if ( $toolbar ) {
				GFForms::top_toolbar();
			}

			$wrapper_classes = ! empty( $tabs ) ? 'gform-settings__wrapper' : 'gform-settings__wrapper gform-settings__wrapper--full';
		?>

			<?php echo GFCommon::get_remote_message(); ?>

			<?php GFCommon::notices_section(); ?>

			<div class="<?php echo $wrapper_classes; ?>">
			<?php
				GFCommon::display_dismissible_message();
				GFCommon::display_admin_message();
			?>
				<?php if ( ! empty( $tabs ) ) { ?>
				<nav class="gform-settings__navigation">
					<?php
						$current_tab = rgempty( 'subview', $_GET ) ? '' : rgget( 'subview' );
						$active_class = null;
						foreach ( $tabs as $tab ) {

							if ( rgar( $tab, 'capabilities' ) && ! GFCommon::current_user_can_any( $tab['capabilities'] ) ) {
								continue;
							}

							$query = array( 'subview' => $tab['name'] );

							if ( isset( $tab['query'] ) ) {
								$query = array_merge( $query, $tab['query'] );
							}

							$url = add_query_arg( $query );

							// Get tab icon.
							$icon_markup = GFCommon::get_icon_markup( $tab, 'gform-icon--cog' );

							if ( $current_tab === $tab['name'] || ( empty( $current_tab ) && is_null( $active_class ) ) ) {
								$active_class = 'class="active"';
							} else {
								$active_class = '';
							}

							printf(
								'<a href="%s"%s><span class="icon">%s</span> <span class="label">%s</span></a>',
								esc_url( $url ),
								$active_class,
								$icon_markup,
								esc_html( $tab['label'] )
							);
						}
					?>
				</nav>
				<?php } ?>

				<div class="gform-settings__content" <?php echo isset( $current_tab ) ? 'id="tab_' . esc_attr( $current_tab ) . '"' : ''; ?>>
		<?php
	}

	/**
	* Displays footer for admin settings pages.
	*
	* @since 2.5
	*
	*/
	public static function admin_footer() {
		if ( ! class_exists( 'GFSettings' ) ) {
			require_once( GFCommon::get_base_path() . '/settings.php' );
		}
		GFSettings::page_footer();
	}

	/**
	 * Add a special body class when within the wp-admin area.
	 *
	 * @since 2.5
	 *
	 * @param string $body_classes The current body classes.
	 *
	 * @return string
	 */
	public static function add_admin_body_class( $body_classes ) {
		$classes = explode( ' ', $body_classes );
		$classes = array_merge( $classes, array( 'gform-admin' ) );

		if ( GFCommon::is_form_editor() && wp_style_is( 'jetpack-admin-menu' ) && ! is_rtl() ) {
			$classes[] = 'gform-jetpack-admin-menu';
		}

		if ( self::is_gravity_page() ) {
			$classes[] = 'gform-admin-screen';
		}

		return implode( ' ', $classes );
	}

	/**
	 * Displays the top toolbar within Gravity Forms pages.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses   GFFormsModel::get_forms()
	 * @uses   GFForms::get_toolbar_menu_items()
	 * @uses   GFForms::format_toolbar_menu_items()
	 */
	public static function top_toolbar() {

		$forms = RGFormsModel::get_forms( null, 'title' );
		$id    = rgempty( 'id', $_GET ) ? count( $forms ) > 0 ? $forms[0]->id : '0' : rgget( 'id' );

		// Get form.
		$form = GFAPI::get_form( $id );

		?>
		<div id="gform-form-toolbar" class="gform-form-toolbar">

			<div class="gform-form-toolbar__container">

				<div class="gform-form-toolbar__form-title">
					<?php self::form_switcher( $form['title'] ); ?>
				</div>

				<ul id="gform-form-toolbar__menu" class="gform-form-toolbar__menu">
					<?php
					$menu_items = apply_filters( 'gform_toolbar_menu', self::get_toolbar_menu_items( $id ), $id );
					foreach ( $menu_items as $key => $item ) {
						if ( in_array( $key, array( 'edit', 'settings', 'entries' ) ) ) {
							$fixed_menu_items[ $key ] = $item;
						} else {
							$dynamic_menu_items[ $key ] = $item;
						}
					}
					echo self::format_toolbar_menu_items( $fixed_menu_items );
					if ( ! empty( $dynamic_menu_items ) ) {
						echo '<span class="gform-form-toolbar__divider"></span>';
						echo GFForms::format_toolbar_menu_items( $dynamic_menu_items );
					}
					?>
				</ul>
				<div id="gf_toolbar_buttons_container" class="gform-form-toolbar__buttons gf_toolbar_buttons_container">
					<?php
					$preview_args = array(
						'form_id' => $id,
					);
					echo GFCommon::get_preview_link( $preview_args );
					?>
				</div>
			</div>
		</div>

		<?php

	}

	/**
	 * Sorts menu items according to priority key.
	 *
	 * @since 2.5
	 *
	 * @param array $menu_items Contains the menu items to be displayed
	 */
	public static function sort_menu_items( &$menu_items ) {
		$priorities = array();
		foreach ( $menu_items as $k => $menu_item ) {
			$priorities[ $k ] = rgar( $menu_item, 'priority' );
		}

		array_multisort( $priorities, SORT_DESC, $menu_items );
	}

	/**
	 * Formats the menu items for display in the Gravity Forms toolbar.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFForms::top_toolbar()
	 * @uses    GFForms::toolbar_sub_menu_items()
	 *
	 * @param array $menu_items Contains the menu items to be displayed
	 * @param bool  $compact    If true, uses the compact labels.  Defaults to false.
	 *
	 * @return string $output The formatted toolbar menu items
	 */
	public static function format_toolbar_menu_items( $menu_items, $compact = false ) {
		if ( empty( $menu_items ) ) {
			return '';
		}

		$output = '';

		self::sort_menu_items( $menu_items );
		$keys     = array_keys( $menu_items );
		$last_key = array_pop( $keys ); // array_pop(array_keys($menu_items)) causes a Strict Standards warning in WP 3.6 on PHP 5.4

		foreach ( $menu_items as $key => $menu_item ) {
			if ( is_array( $menu_item ) ) {
				if ( GFCommon::current_user_can_any( rgar( $menu_item, 'capabilities' ) ) ) {
					$sub_menu_str         = '';
					$count_sub_menu_items = 0;
					$sub_menu_items       = rgar( $menu_item, 'sub_menu_items' );
					if ( is_array( $sub_menu_items ) ) {
						foreach ( $sub_menu_items as $k => $val ) {
							if ( false === GFCommon::current_user_can_any( rgar( $sub_menu_items[ $k ], 'capabilities' ) ) ) {
								unset( $sub_menu_items[ $k ] );
							}
						}
						$sub_menu_items       = array_values( $sub_menu_items ); //reset numeric keys
						$count_sub_menu_items = count( $sub_menu_items );
					}

					$menu_class    = rgar( $menu_item, 'menu_class' );
					$submenu_class = $count_sub_menu_items == 0 ? '' : 'has_submenu';

					if ( $count_sub_menu_items == 1 ) {
						$label     = $compact ? rgar( $menu_item, 'label' ) : rgar( $sub_menu_items[0], 'label' );
						$menu_item = $sub_menu_items[0];
					} else {
						$label        = rgar( $menu_item, 'label' );
						$sub_menu_str = self::toolbar_sub_menu_items( $sub_menu_items, $compact );
					}
					$link_class = esc_attr( rgar( $menu_item, 'link_class' ) ) . ' ' . $submenu_class;
					$icon       = rgar( $menu_item, 'icon' );
					$url        = esc_url( rgar( $menu_item, 'url' ) );
					$aria_label = rgar( $menu_item, 'aria-label' );
					$aria_label = ( ! empty( $aria_label ) ) ? "aria-label='" . esc_attr( $aria_label ) . "'" : '';
					$onclick    = esc_attr( rgar( $menu_item, 'onclick' ) );
					$label      = esc_html( $label );
					$target     = rgar( $menu_item, 'target' );

					$link = "<a class='{$link_class}' onclick='{$onclick}' onkeypress='{$onclick}' {$aria_label} href='{$url}' target='{$target}'>{$label}</a>" . $sub_menu_str;
					if ( $compact ) {
						if ( $key == 'delete' ) {

							/**
							 * A filter to allow the modification of the HTML link to delete a form
							 *
							 * @since Unknown
							 *
							 * @param string $link The HTML "Delete Form" Link
							 */
							$link = apply_filters( 'gform_form_delete_link', $link );
						}
						$divider = $key == $last_key ? '' : ' | ';
						if ( $count_sub_menu_items > 0 ) {
							$menu_class .= ' gf_form_action_has_submenu';
						}
						$output .= '<span class="' . $menu_class . '">' . $link . $divider . '</span>';
					} else {

						$output .= "<li class='{$menu_class}'>{$link}</li>";
					}
				}
			} elseif ( $compact ) {
				//for backwards compatibility <1.7: form actions only
				$divider = $key == $last_key ? '' : ' | ';
				$output .= '<span class="edit">' . $menu_item . $divider . '</span>';
			}
		}

		return $output;
	}

	/**
	 * Gets the menu items to be displayed within the toolbar.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFForms::top_toolbar()
	 * @uses    GFForms::toolbar_class()
	 *
	 * @param string $form_id The form ID.
	 * @param bool   $compact True if the compact label should be used.  Defaults to false.
	 *
	 * @return array $menu_items The menu items to be displayed.
	 */
	public static function get_toolbar_menu_items( $form_id, $compact = false ) {
		$menu_items = array();

		$is_mobile = wp_is_mobile();

		$form_id = absint( $form_id );

		$edit_capabilities = array( 'gravityforms_edit_forms' );

		$page = self::get_page();

		// Don't show the edit link if we're already in the editor.
		if ( $page != 'form_editor' ) {
			$menu_items['edit'] = array(
				'label'        => esc_html__( 'Edit', 'gravityforms' ),
				'short_label'  => esc_html__( 'Editor', 'gravityforms' ),
				'aria-label'   => esc_html__( 'Editor', 'gravityforms' ),
				'icon'         => '<i class="fa fa-pencil-square-o fa-lg"></i>',
				'url'          => '?page=gf_edit_forms&id=' . $form_id,
				'menu_class'   => 'gf_form_toolbar_editor',
				'link_class'   => self::toolbar_class( 'editor' ),
				'capabilities' => $edit_capabilities,
				'priority'     => 1000,
			);
		}

		$sub_menu_items = self::get_form_settings_sub_menu_items( $form_id );
		$menu_items['settings'] = array(
			'label'          => esc_html__( 'Settings', 'gravityforms' ),
			'aria-label'     => esc_html__( 'Edit settings for this form', 'gravityforms' ),
			'url'            => $is_mobile ? '#' : '?page=gf_edit_forms&view=settings&id=' . $form_id,
			'menu_class'     => 'gf_form_toolbar_settings',
			'link_class'     => self::toolbar_class( 'settings' ),
			'sub_menu_items' => $sub_menu_items,
			'capabilities'   => $edit_capabilities,
			'priority'       => 900,
		);

		$entries_capabilities = array(
			'gravityforms_view_entries',
			'gravityforms_edit_entries',
			'gravityforms_delete_entries'
		);
		$menu_items['entries'] = array(
			'label'        => esc_html__( 'Entries', 'gravityforms' ),
			'aria-label'   => esc_html__( 'View entries generated by this form', 'gravityforms' ),
			'url'          => '?page=gf_entries&id=' . $form_id,
			'menu_class'   => 'gf_form_toolbar_entries',
			'link_class'   => self::toolbar_class( 'entries' ),
			'capabilities' => $entries_capabilities,
			'priority'     => 800,
		);

		// Don't show in form editor toolbar as it is shown next to the update button.
		if ( $page == 'form_list' || $page == 'new_form' ) {
			$preview_args = array(
				'array'      => true,
				'form_id'    => $form_id,
				'link_class' => self::toolbar_class( 'preview' ),
				'menu_class' => 'gf_form_toolbar_preview',
			);

			$menu_items['preview'] = GFCommon::get_preview_link_data( $preview_args );
		}

		return $menu_items;
	}

	/**
	 * Builds the sub-menu items within the Gravity Forms toolbar.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFForms::format_toolbar_menu_items()
	 *
	 * @param array $menu_items The menu items to be built
	 * @param bool  $compact    True if the compact label should be used.  False otherwise.
	 *
	 * @return string $sub_menu_items_string The menu item HTML
	 */
	public static function toolbar_sub_menu_items( $menu_items, $compact = false ) {
		if ( empty( $menu_items ) ) {
			return '';
		}

		$sub_menu_items_string = '';
		foreach ( $menu_items as $menu_item ) {
			if ( GFCommon::current_user_can_any( rgar( $menu_item, 'capabilities' ) ) ) {
				$menu_class = esc_attr( rgar( $menu_item, 'menu_class' ) );
				$link_class = esc_attr( rgar( $menu_item, 'link_class' ) );
				$url        = esc_url( rgar( $menu_item, 'url' ) );
				$label      = esc_html( rgar( $menu_item, 'label' ) );
				$target     = esc_attr( rgar( $menu_item, 'target' ) );
				$icon       = rgar( $menu_item, 'icon' );
				$sub_menu_items_string .= "<li class='{$menu_class}'><a href='{$url}' class='{$link_class}' target='{$target}'><span class='gform-form-toolbar__icon'>{$icon}</span> {$label}</a></li>";
			}
		}

		$simplebar_rtl_attr = is_rtl() ? ' data-simplebar-direction="rtl"' : '';
		$sub_menu_items_string = sprintf(
			'<div class="gform-form-toolbar__submenu"><div data-simplebar%s><ul>' . $sub_menu_items_string . '</ul></div></div>',
			$simplebar_rtl_attr
		);

		return $sub_menu_items_string;
	}

	/**
	 * Gets the form settings sub-menu items.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFForms::get_toolbar_menu_items()
	 * @uses    GFFormSettings::get_tabs()
	 *
	 * @param string $form_id The form ID.
	 *
	 * @return array $sub_menu_items The sub-menu items.
	 */
	public static function get_form_settings_sub_menu_items( $form_id ) {
		require_once( GFCommon::get_base_path() . '/form_settings.php' );

		$sub_menu_items = array();
		$tabs           = GFFormSettings::get_tabs( $form_id );

		foreach ( $tabs as $tab ) {

			if ( $tab['name'] == 'settings' ) {
				$form_setting_menu_item['label'] = 'Settings';
			}

			$sub_menu_items[] = array(
				'url'          => admin_url( "admin.php?page=gf_edit_forms&view=settings&subview={$tab['name']}&id={$form_id}" ),
				'label'        => $tab['label'],
				'icon'         => GFCommon::get_icon_markup( $tab ),
				'capabilities' => ( isset( $tab['capabilities'] ) ) ? $tab['capabilities'] : array( 'gravityforms_edit_forms' ),
			);

		}

		return $sub_menu_items;
	}

	/**
	 * Gets the CSS class to be used for the toolbar.
	 *
	 * @since   Unknown
	 * @access  private
	 *
	 * @used-by GFForms::get_toolbar_menu_items()
	 *
	 * @param string $item The Gravity Forms view (current page).
	 *
	 * @return string The class name.  Empty string if the view isn't found.
	 */
	private static function toolbar_class( $item ) {

		switch ( $item ) {

			case 'editor':
				if ( in_array( rgget( 'page' ), array(
						'gf_edit_forms',
						'gf_new_form'
					) ) && rgempty( 'view', $_GET )
				) {
					return 'gf_toolbar_active';
				}
				break;

			case 'settings':
				if ( rgget( 'view' ) == 'settings' ) {
					return 'gf_toolbar_active';
				}
				break;

			case 'notifications' :
				if ( rgget( 'page' ) == 'gf_new_form' ) {
					return 'gf_toolbar_disabled';
				} else if ( rgget( 'page' ) == 'gf_edit_forms' && rgget( 'view' ) == 'notification' ) {
					return 'gf_toolbar_active';
				}
				break;

			case 'entries' :
				if ( rgget( 'page' ) == 'gf_new_form' ) {
					return 'gf_toolbar_disabled';
				} else if ( rgget( 'page' ) == 'gf_entries' && strpos( rgget( 'view' ), 'gf_results_' ) === false ) {
					return 'gf_toolbar_active';
				}

				break;

			case 'preview' :
				if ( rgget( 'page' ) == 'gf_new_form' ) {
					return 'gf_toolbar_disabled';
				}

				break;
		}

		return '';
	}

	/**
	 * Modifies the top WordPress toolbar to add Gravity Forms menu items.
	 *
	 * @since   Unknown
	 * @access  public
	 * @global $wp_admin_bar
	 *
	 * @used-by GFForms::init()
	 */
	public static function admin_bar() {
		/**
		 * @var  WP_Admin_Bar $wp_admin_bar
		 */
		global $wp_admin_bar;

		if ( GFCommon::current_user_can_any( 'gravityforms_create_form' ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'gravityforms-new-form',
					'parent' => 'new-content',
					'title'  => esc_attr__( 'Form', 'gravityforms' ),
					'href'   => admin_url( 'admin.php?page="gf_new_form' ),
				)
			);
		}

		if ( ! get_option( 'gform_enable_toolbar_menu' ) ) {
			return;
		}

		if ( ! GFCommon::current_user_can_any( array(
			'gravityforms_edit_forms',
			'gravityforms_create_form',
			'gravityforms_preview_forms',
			'gravityforms_view_entries'
		) )
		) {
			// The current user can't use anything on the menu so bail.
			return;
		}

		$args = array(
			'id'    => 'gform-forms',
			'title' => '<div class="ab-item gforms-menu-icon svg" style="background-image: url(\'' . self::get_admin_icon_b64( '#888888' ) . '\');"></div><span class="ab-label">' . esc_html__( 'Forms', 'gravityforms' ) . '</span>',
			'href'  => admin_url( 'admin.php?page=gf_edit_forms' ),
		);

		$wp_admin_bar->add_node( $args );

		$recent_form_ids = GFFormsModel::get_recent_forms();

		if ( $recent_form_ids ) {
			$forms = GFFormsModel::get_form_meta_by_id( $recent_form_ids );

			$wp_admin_bar->add_node(
				array(
					'id'     => 'gform-form-recent-forms',
					'parent' => 'gform-forms',
					'title'  => esc_html__( 'Recent', 'gravityforms' ),
					'group'  => true,
				)
			);

			foreach ( $recent_form_ids as $recent_form_id ) {

				foreach ( $forms as $form ) {
					if ( $form['id'] == $recent_form_id ) {
						$wp_admin_bar->add_node(
							array(
								'id'     => 'gform-form-' . $recent_form_id,
								'parent' => 'gform-form-recent-forms',
								'title'  => esc_html( $form['title'] ),
								'href'   => GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ? admin_url( 'admin.php?page=gf_edit_forms&id=' . $recent_form_id ) : '',
							)
						);

						if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
							$wp_admin_bar->add_node(
								array(
									'id'     => 'gform-form-' . $recent_form_id . '-edit',
									'parent' => 'gform-form-' . $recent_form_id,
									'title'  => esc_html__( 'Edit', 'gravityforms' ),
									'href'   => admin_url( 'admin.php?page=gf_edit_forms&id=' . $recent_form_id ),
								)
							);
						}

						if ( GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
							$wp_admin_bar->add_node(
								array(
									'id'     => 'gform-form-' . $recent_form_id . '-entries',
									'parent' => 'gform-form-' . $recent_form_id,
									'title'  => esc_html__( 'Entries', 'gravityforms' ),
									'href'   => admin_url( 'admin.php?page=gf_entries&id=' . $recent_form_id ),
								)
							);
						}

						if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
							$wp_admin_bar->add_node(
								array(
									'id'     => 'gform-form-' . $recent_form_id . '-settings',
									'parent' => 'gform-form-' . $recent_form_id,
									'title'  => esc_html__( 'Settings', 'gravityforms' ),
									'href'   => admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=settings&id=' . $recent_form_id ),
								)
							);
						}

						if ( GFCommon::current_user_can_any( array(
							'gravityforms_edit_forms',
							'gravityforms_create_form',
							'gravityforms_preview_forms'
						) )
						) {
							$wp_admin_bar->add_node(
								array(
									'id'     => 'gform-form-' . $recent_form_id . '-preview',
									'parent' => 'gform-form-' . $recent_form_id,
									'title'  => esc_html__( 'Preview', 'gravityforms' ),
									'href'   => trailingslashit( site_url() ) . '?gf_page=preview&id=' . $recent_form_id,
								)
							);
						}
					}
				}
			}
		}

		if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'gform-forms-view-all',
					'parent' => 'gform-forms',
					'title'  => esc_attr__( 'All Forms', 'gravityforms' ),
					'href'   => admin_url( 'admin.php?page=gf_edit_forms' ),
				)
			);
		}

		if ( GFCommon::current_user_can_any( 'gravityforms_create_form' ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'gform-forms-new-form',
					'parent' => 'gform-forms',
					'title'  => esc_attr__( 'New Form', 'gravityforms' ),
					'href'   => admin_url( 'admin.php?page=gf_new_form' ),
				)
			);
		}

	}

	/**
	 * Determines if automatic updating should be processed.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by WP_Automatic_Updater::should_update()
	 * @uses    GFForms::is_auto_update_disabled()
	 *
	 * @param bool   $update Whether or not to update.
	 * @param object $item   The update offer object.
	 *
	 * @return bool True if update should be processed.  False otherwise.
	 */
	public static function maybe_auto_update( $update, $item ) {

		if ( ! isset( $item->slug ) || $item->slug !== 'gravityforms' ) {
			return $update;
		}

		GFCommon::log_debug( 'GFForms::maybe_auto_update() - Starting auto-update for gravityforms.' );

		$auto_update_disabled = self::is_auto_update_disabled();
		GFCommon::log_debug( 'GFForms::maybe_auto_update() - $auto_update_disabled: ' . var_export( $auto_update_disabled, true ) );

		if ( $auto_update_disabled || version_compare( GFForms::$version, $item->new_version, '>=' ) ) {
			GFCommon::log_debug( 'GFForms::maybe_auto_update() - Aborting update.' );

			return false;
		}

		if ( self::should_update_to_version( $item->new_version ) ) {
			GFCommon::log_debug( __METHOD__ . '() - OK to update.' );

			return true;
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '() - Aborting. Automatically updating from %s to %s is not supported.', GFForms::$version, $item->new_version ) );

		return false;

	}

	/**
	 * Determines if the current version should update to the offered version.
	 *
	 * @since 2.4.22.4
	 *
	 * @param string $offered_ver The version number to be compared against the installed version number.
	 *
	 * @return bool
	 */
	public static function should_update_to_version( $offered_ver ) {
		if ( version_compare( GFForms::$version, $offered_ver, '>=' ) ) {
			return false;
		}

		$current_branch = implode( '.', array_slice( preg_split( '/[.-]/', GFForms::$version ), 0, 2 ) );
		$new_branch     = implode( '.', array_slice( preg_split( '/[.-]/', $offered_ver ), 0, 2 ) );

		return $current_branch == $new_branch;
	}

	/**
	 * Checks if automatic updates are disabled.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFForms::maybe_auto_update()
	 *
	 * @return bool True if auto update is disabled.  False otherwise.
	 */
	public static function is_auto_update_disabled() {
		// Check Gravity Forms Background Update Settings.
		$enabled = get_option( 'gform_enable_background_updates' );
		GFCommon::log_debug( 'GFForms::is_auto_update_disabled() - $enabled: ' . var_export( $enabled, true ) );

		/**
		 * Filter to disable Gravity Forms Automatic updates
		 *
		 * @param bool $enabled Check if automatic updates are enabled, and then disable it
		 */
		$disabled = apply_filters( 'gform_disable_auto_update', ! $enabled );
		GFCommon::log_debug( 'GFForms::is_auto_update_disabled() - $disabled: ' . var_export( $disabled, true ) );

		if ( ! $disabled ) {
			$disabled = defined( 'GFORM_DISABLE_AUTO_UPDATE' ) && GFORM_DISABLE_AUTO_UPDATE;
			GFCommon::log_debug( 'GFForms::is_auto_update_disabled() - GFORM_DISABLE_AUTO_UPDATE: ' . var_export( $disabled, true ) );
		}

		return $disabled;
	}

	/**
	 * Filter the auto-update message on the plugins page.
	 *
	 * @since Unknown
	 *
	 * @param string $html         HTML of the auto-update message.
	 * @param string $plugin_file  Plugin file.
	 * @param array  $plugin_data  Plugin details.
	 *
	 * @return string|void
	 */
	public static function auto_update_message( $html, $plugin_file, $plugin_data ) {
		// Check if the plugin is Gravity Forms or an add-on.
		if ( ! self::is_gf_or_addon( $plugin_data['PluginURI'] ) ) {
			return $html;
		}

		$update            = GFCommon::get_version_info();
		$no_update_message = esc_html__( 'Auto-updates unavailable.', 'gravityforms' );

		// If it's not GF core, only filter the message if the license is invalid.
		if ( 'gravityforms.php' !== substr( $plugin_file, strrpos( $plugin_file, '/' ) + 1 ) ) {
			if ( ! rgar( $update, 'is_valid_key' ) ) {
				return $no_update_message;
			} else {
				return $html;
			}
		}

		// We've gotten this far, so it must be GF core.
		if ( ! rgar( $update, 'is_valid_key' ) ) {
			return $no_update_message;
		}

		$background_updates = get_option( 'gform_enable_background_updates' );
		if ( $background_updates ) {
			// auto-updates are enabled, so clicking on this will disable them.
			$message = esc_html__( 'Disable auto-updates', 'gravityforms' );
			$action  = 'disable';
		} else {
			// auto-updates are disabled, so clicking on this will enable them.
			$message = esc_html__( 'Enable auto-updates', 'gravityforms' );
			$action  = 'enable';
		}

		$html = sprintf(
			'<a href="%s" class="gf-toggle-auto-update aria-button-if-js" data-gfaction="%s" data-nonce="%s"><span class="dashicons dashicons-update spin hidden gf-update-setting" aria-hidden="true"></span><span class="label gf-update-label">%s</span></a><div class="gf-auto-update-notice notice notice-error notice-alt inline hidden"><p></p></div>',
			esc_url( 'admin.php?page=gf_settings' ),
			$action . '-gf-updates',
			wp_create_nonce( 'gf-updates' ),
			$message
		);

		return $html;
	}

	/**
	 * Filter the auto-update message on the Site Health page.
	 *
	 * @since 2.4.20.2
	 *
	 * @param string $auto_updates_string Text of auto-update message.
	 * @param string $plugin_path         Plugin path.
	 * @param array  $plugin              Plugin details.
	 * @param bool   $enabled             Whether auto-updates are enabled.
	 *
	 * @return string|void
	 */
	public static function auto_update_debug_message( $auto_updates_string, $plugin_path, $plugin, $enabled ) {
		// Check if the plugin is Gravity Forms or an add-on.
		if ( ! self::is_gf_or_addon( $plugin['PluginURI'] ) ) {
			return $auto_updates_string;
		}

		$update            = GFCommon::get_version_info();
		$no_update_message = __( 'Please register your copy of Gravity Forms to enable automatic updates.', 'gravityforms' );

		// If it's not GF core, only filter the message if the license is invalid.
		if ( 'gravityforms.php' !== substr( $plugin_path, strrpos( $plugin_path, '/' ) + 1 ) ) {
			if ( ! rgar( $update, 'is_valid_key' ) ) {
				return $no_update_message;
			} else {
				return $auto_updates_string;
			}
		}

		// Filter the message for GF core.
		if ( rgar( $update, 'is_valid_key' ) ) {
			return $auto_updates_string;
		} else {
			return $no_update_message;
		}

	}

	/**
	 * Enable or disable auto-updates.
	 *
	 * AJAX function to enable or disable auto-updates from the WordPress plugins page.
	 *
	 * @since 2.4.20.2
	 */
	public static function update_auto_update_setting() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'gf-updates' ) ) {
			wp_send_json_error( __( 'Permissions error.', 'gravityforms' ) );
		}

		$acceptable_tasks = array( 'enable-gf-updates', 'disable-gf-updates' );
		if ( ! rgar( $_POST, 'task' ) || ! in_array( $_POST['task'], $acceptable_tasks ) ) {
			wp_send_json_error( __( 'Error processing request.  Please refresh and try again.', 'gravityforms' ) );
		}

		if ( 'enable-gf-updates' == $_POST['task'] ) {
			update_option( 'gform_enable_background_updates', true );
			wp_send_json_success( 'success' );
		} else {
			update_option( 'gform_enable_background_updates', false );
			wp_send_json_success( 'success' );
		}

	}

	/**
	 * Check if a plugin is Gravity Forms or an offical add-on.
	 *
	 * @since 2.4.20.2
	 *
	 * @param string $plugin_uri The URI of the plugin as found in the plugin header.
	 *
	 * @return bool
	 */
	public static function is_gf_or_addon( $plugin_uri ) {
		if ( strpos( $plugin_uri, 'gravityforms.com' ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function deprecate_add_on_methods() {
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) ) {
			return;
		}
		$deprecated = GFAddOn::get_all_deprecated_protected_methods();
		if ( ! empty( $deprecated ) ) {
			foreach ( $deprecated as $method ) {
				_deprecated_function( $method, '1.9', 'public access level' );
			}
		}
	}

	/**
	 * Shortcode UI
	 */

	/**
	 * Output a shortcode.
	 *
	 * Called via AJAX.
	 * Used for displaying the shortcode in the TinyMCE editor.
	 *
	 * @since  Unknown
	 * @access public
	 * @global $post
	 */
	public static function handle_ajax_do_shortcode() {

		$shortcode = ! empty( $_POST['shortcode'] ) ? sanitize_text_field( stripslashes( $_POST['shortcode'] ) ) : null;
		$post_id   = ! empty( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : null;

		if ( ! current_user_can( 'edit_post', $post_id ) || ! wp_verify_nonce( $_POST['nonce'], 'gf-shortcode-ui-preview' ) ) {
			echo esc_html__( 'Error', 'gravityforms' );
			exit;
		}

		$form_id = ! empty( $_POST['form_id'] ) ? intval( $_POST['form_id'] ) : null;

		global $post;
		$post = get_post( $post_id );
		setup_postdata( $post );

		self::enqueue_form_scripts( $form_id, true );
		wp_print_scripts();
		wp_print_styles();

		echo do_shortcode( $shortcode );

		// Disable the elements on the form
		?>
		<script type="text/javascript">
			jQuery('.gform_wrapper input, .gform_wrapper select, .gform_wrapper textarea').prop('disabled', true);
			jQuery('a img').each(function () {
				var image = this.src;
				var img = jQuery('<img>', {src: image});
				$(this).parent().replaceWith(img);
			});
			jQuery('a').each(function () {
				jQuery(this).replaceWith(jQuery(this).text());
			});
		</script>
		<?php
		exit;
	}

	/**
	 * Displays the shortcode editor.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFForms::init()
	 * @used    GFForms::get_view()
	 *
	 * @return void
	 */
	public static function action_print_media_templates() {

		echo GFForms::get_view( 'edit-shortcode-form' );
	}

	/**
	 * Gets the view and loads the appropriate template.
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by GFForms::action_print_media_templates()
	 *
	 * @param string $template The template to be loaded.
	 *
	 * @return mixed The contents of the template file.
	 */
	public static function get_view( $template ) {

		if ( ! file_exists( $template ) ) {

			$template_dir = GFCommon::get_base_path() . '/includes/templates/';
			$template     = $template_dir . $template . '.tpl.php';

			if ( ! file_exists( $template ) ) {
				return '';
			}
		}

		ob_start();
		include $template;

		return ob_get_clean();
	}

	/**
	 * Modifies the TinyMCE editor styling.
	 *
	 * Called from the tiny_mce_before_init filter
	 *
	 * @since   Unknown
	 * @access  public
	 *
	 * @used-by Filter: tiny_mce_before_init
	 *
	 * @param array $init Init data passed from the tiny_mce_before_init filter.
	 *
	 * @return array $init Data after filtering.
	 */
	public static function modify_tiny_mce_4( $init ) {

		// Hack to fix compatibility issue with ACF PRO
		if ( ! isset( $init['content_css'] ) ) {
			return $init;
		}

		$base_url = GFCommon::get_base_url();

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$editor_styles = $base_url . "/css/shortcode-ui-editor-styles{$min}.css,";
		$form_styles   = $base_url . "/legacy/css/formsmain{$min}.css";

		if ( isset( $init['content_css'] ) ) {
			if ( empty( $init['content_css'] ) ) {
				$init['content_css'] = '';
			} elseif ( is_array( $init['content_css'] ) ) {
				$init['content_css'][] = $editor_styles;
				$init['content_css'][] = $form_styles;

				return $init;
			} else {
				$init['content_css'] = $init['content_css'] . ',';
			}
		}

		// Note: Using .= here can trigger a fatal error
		$init['content_css'] = $init['content_css'] . $editor_styles . $form_styles;

		return $init;
	}

	/**
	 * Gets the available shortcode attributes.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array $shortcodes Shortcode attributes.
	 */
	public static function get_shortcodes() {

		$forms             = GFAPI::get_forms( true, false, 'title' );
		$forms_options[''] = __( 'Select a Form', 'gravityforms' );
		foreach ( $forms as $form ) {
			$forms_options[ absint( $form['id'] ) ] = $form['title'];
		}

		/**
		 * Modify the list of available forms displayed in the shortcode builder.
		 *
		 * @since 2.4.23
		 *
		 * @param array $forms_options A collection of active forms on site.
		 */
		$forms_options = apply_filters( 'gform_shortcode_builder_forms', $forms_options );

		$default_attrs = array(
			array(
				'label'       => __( 'Select a form below to add it to your post or page.', 'gravityforms' ),
				'tooltip'     => __( 'Select a form from the list to add it to your post or page.', 'gravityforms' ),
				'attr'        => 'id',
				'type'        => 'select',
				'section'     => 'required',
				'description' => __( "Can't find your form? Make sure it is active.", 'gravityforms' ),
				'options'     => $forms_options,
			),
			array(
				'label'   => __( 'Display form title', 'gravityforms' ),
				'attr'    => 'title',
				'default' => 'true',
				'section' => 'standard',
				'type'    => 'checkbox',
				'tooltip' => __( 'Whether or not to display the form title.', 'gravityforms' )
			),
			array(
				'label'   => __( 'Display form description', 'gravityforms' ),
				'attr'    => 'description',
				'default' => 'true',
				'section' => 'standard',
				'type'    => 'checkbox',
				'tooltip' => __( 'Whether or not to display the form description.', 'gravityforms' )
			),
			array(
				'label'   => __( 'Enable Ajax', 'gravityforms' ),
				'attr'    => 'ajax',
				'section' => 'standard',
				'type'    => 'checkbox',
				'tooltip' => __( 'Specify whether or not to use Ajax to submit the form.', 'gravityforms' )
			),
			array(
				'label'   => 'Tabindex',
				'attr'    => 'tabindex',
				'type'    => 'number',
				'tooltip' => __( 'Specify the starting tab index for the fields of this form.', 'gravityforms' )
			),

		);

		/**
		 * Filters through the shortcode builder actions (ajax, tabindex, form title) for adding a new form to a post, page, etc.
		 *
		 * @since Unknown
		 *
		 * @param array() Array of additional shortcode builder actions.  Empty by default.
		 */
		$add_on_actions = apply_filters( 'gform_shortcode_builder_actions', array() );

		if ( ! empty( $add_on_actions ) ) {
			$action_options = array( '' => __( 'Select an action', 'gravityforms' ) );
			foreach ( $add_on_actions as $add_on_action ) {
				foreach ( $add_on_action as $key => $array ) {
					$action_options[ $key ] = $array['label'];
				}
			}

			$default_attrs[] = array(
				'label'   => 'Action',
				'attr'    => 'action',
				'type'    => 'select',
				'options' => $action_options,
				'tooltip' => __( 'Select an action for this shortcode. Actions are added by some add-ons.', 'gravityforms' )
			);
		}

		$shortcode = array(
			'shortcode_tag' => 'gravityform',
			'action_tag'    => '',
			'label'         => 'Gravity Forms',
			'attrs'         => $default_attrs,
		);

		$shortcodes[] = $shortcode;

		if ( ! empty( $add_on_actions ) ) {
			foreach ( $add_on_actions as $add_on_action ) {
				foreach ( $add_on_action as $key => $array ) {
					$attrs     = array_merge( $default_attrs, $array['attrs'] );
					$shortcode = array(
						'shortcode_tag' => 'gravityform',
						'action_tag'    => $key,
						'label'         => rgar( $array, 'label' ),
						'attrs'         => $attrs,
					);
				}
			}
			$shortcodes[] = $shortcode;
		}

		return $shortcodes;
	}

	/**
	 * Enqueues scripts needed to display the form.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used   GFFormDisplay::enqueue_form_scripts()
	 * @used   GFAddOn::get_registered_addons()
	 *
	 * @param string $form_id The displayed form ID.
	 * @param bool   $is_ajax True if form uses AJAX.  False otherwise.
	 */
	public static function enqueue_form_scripts( $form_id, $is_ajax = false ) {
		require_once( GFCommon::get_base_path() . '/form_display.php' );
		$form = RGFormsModel::get_form_meta( $form_id );
		GFFormDisplay::enqueue_form_scripts( $form, $is_ajax );
		$addons = GFAddOn::get_registered_addons();
		foreach ( $addons as $addon ) {
			$a = call_user_func( array( $addon, 'get_instance' ) );
			$a->enqueue_scripts( $form, $is_ajax );
		}
	}

	/**
	 * Displays the installation wizard or upgrade wizard when appropriate.
	 *
	 * @since  2.2
	 * @access public
	 *
	 * @return bool Was a wizard displayed?
	 */
	public static function maybe_display_wizard() {

		return gf_upgrade()->maybe_display_wizard();
	}

	/**
	 * Display admin notice when logging is enabled.
	 *
	 * @since  2.4
	 * @access public
	 */
	public static function maybe_display_logging_notice() {

		$notice_disabled = defined( 'GF_LOGGING_DISABLE_NOTICE' ) && GF_LOGGING_DISABLE_NOTICE;
		$logging_enabled = get_option( 'gform_enable_logging', false ) || is_plugin_active( 'gravityformslogging/logging.php' );

		// If logging is disabled, return.
		if ( $notice_disabled || ! $logging_enabled || ! GFCommon::current_user_can_any( 'gravityforms_edit_settings' ) ) {
			return;
		}


		$message = sprintf(
			'<p>%1$s%3$s<strong>%2$s</strong></p><p><strong>%4$s</strong></p>',
			esc_html__( 'Gravity Forms logging is currently enabled. ', 'gravityforms' ),
			esc_html__( 'If you currently have a support ticket open, please do not disable logging until the Support Team has reviewed your logs. ', 'gravityforms' ),
			esc_html__( 'Since logs may contain sensitive information, please ensure that you only leave it enabled for as long as it is needed for troubleshooting. ', 'gravityforms' ),
			sprintf(
				esc_html__( 'Once troubleshooting is complete, %1$sclick here to disable logging and permanently delete your log files.%2$s ', 'gravityforms' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=gf_settings' ) ) . '">',
				'</a>'
			)
		);

		// Prepare script.
		$script = "<script type='text/javascript'>
			jQuery( document ).on( 'click', '#gform_disable_logging_notice a', function( e ) {
				e.preventDefault();
				var container = jQuery( '#gform_disable_logging_notice' );
				jQuery.ajax( {
					url: ajaxurl,
					dataType: 'json',
					data: {
						action: 'gf_disable_logging',
						nonce:  container.data( 'nonce' )
					},
					success: function( response ) {
						if ( response.success ) {
							container.removeClass( 'notice-error' ).addClass( 'notice-success' );
							jQuery( '#_gform_setting_enable_logging' ).prop( 'checked', false );
						}
						container.html( '<p>' + response.data + '</p>' );
					}
				} );             	
			} );
		</script>";

		printf( '<div class="notice notice-error gf-notice" id="gform_disable_logging_notice" data-nonce="%s">%s</div>%s', wp_create_nonce( 'gf_disable_logging_nonce' ), $message, $script );

	}

	/**
	 * Sets the screen options for the entry list.
	 *
	 * @since   2.0
	 * @access  public
	 *
	 * @used-by Filter: set-screen-option
	 *
	 * @param bool|int $status Screen option value. Not used. Defaults to false.
	 * @param string   $option The option to check.
	 * @param int      $value  The number of rows to display per page.
	 *
	 * @return array $return The filtered data
	 */
	public static function set_screen_options( $status, $option, $value ) {
		$return = false;
		if ( $option == 'gform_entries_screen_options' ) {
			$return                   = array();
			$return['default_filter'] = sanitize_key( rgpost( 'gform_default_filter' ) );
			$return['per_page']       = sanitize_key( rgpost( 'gform_per_page' ) );
			$return['display_mode']   = sanitize_key( rgpost( 'gform_entries_display_mode' ) );
		} elseif ( $option = 'gform_forms_per_page' ) {
			$return = $value;
		}

		return $return;
	}

	/**
	 * Returns the markup for the screen options for the entry list.
	 *
	 * @since   2.0
	 * @access  public
	 *
	 * @used-by Filter: screen_settings
	 * @used    GFEntryList::get_screen_options_markup()
	 *
	 * @param string    $status The current screen settings
	 * @param WP_Screen $args   WP_Screen object
	 *
	 * @return string $return The filtered screen settings
	 */
	public static function show_screen_options( $status, $args ) {

		$return = $status;

		if ( self::get_page() == 'entry_list' ) {
			require_once( GFCommon::get_base_path() . '/entry_list.php' );
			$return = GFEntryList::get_screen_options_markup( $status, $args );
		}

		return $return;
	}

	/**
	 * Loads the screen options for the entry detail page.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @used   GFEntryDetail::add_meta_boxes()
	 */
	public static function load_screen_options() {
		$screen = get_current_screen();

		if ( ! is_object( $screen ) ) {
			return;
		}

		$page = GFForms::get_page();

		if ( $page == 'form_list' ) {
			$args = array(
				'label'   => __( 'Forms per page', 'gravityforms' ),
				'default' => 20,
				'option'  => 'gform_forms_per_page',
			);
			add_screen_option( 'per_page', $args );
		} elseif ( in_array( $page, array( 'entry_detail', 'entry_detail_edit' ) ) ) {

			require_once( GFCommon::get_base_path() . '/entry_detail.php' );

			GFEntryDetail::add_meta_boxes();
		}
	}

	/**
	 * Daily cron task. Target for the gravityforms_cron action.
	 *
	 * - Performs self-healing
	 * - Adds empty index files
	 * - Deletes unclaimed export files.
	 * - Deleted old log files.
	 * - Deletes orphaned entry rows from the lead table.
	 *
	 * @since   2.0.0
	 * @access  public
	 *
	 * @used-by Action: gravityforms_cron
	 * @used    GFForms::add_security_files()
	 * @used    GFForms::delete_old_export_files()
	 * @used    GFForms::delete_old_log_files()
	 * @used    GFForms::do_self_healing()
	 * @used    GFForms::delete_orphaned_entries()
	 */
	public static function cron() {

		GFCommon::log_debug( __METHOD__ . '(): Starting cron.' );

		self::add_security_files();

		self::delete_old_export_files();

		self::delete_old_log_files();

		self::do_self_healing();

		if ( ! get_option( 'gform_enable_logging' ) ) {
			gf_logging()->delete_log_files();
		}

		require_once( 'includes/class-personal-data.php' );
		GF_Personal_Data::cron_task();

		GFCommon::log_debug( __METHOD__ . '(): Done.' );
	}

	/**
	 * Deletes all entry export files from the server that haven't been claimed within 24 hours.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public static function delete_old_export_files() {
		GFCommon::log_debug( __METHOD__ . '(): Starting.' );
		$uploads_folder = RGFormsModel::get_upload_root();
		if ( ! is_dir( $uploads_folder ) || is_link( $uploads_folder ) ) {
			GFCommon::log_debug( __METHOD__ . '(): No upload root - bailing.' );

			return;
		}
		$export_folder = $uploads_folder . 'export';
		if ( ! is_dir( $export_folder ) || is_link( $export_folder ) ) {
			GFCommon::log_debug( __METHOD__ . '():  No export root - bailing.' );

			return;
		}
		GFCommon::log_debug( __METHOD__ . '(): Start deleting old export files' );
		foreach ( GFCommon::glob( '*.csv', $export_folder . DIRECTORY_SEPARATOR ) as $filename ) {
			$timestamp = filemtime( $filename );
			if ( $timestamp < time() - DAY_IN_SECONDS ) {
				// Delete files over a day old
				GFCommon::log_debug( __METHOD__ . '(): Proceeding to delete ' . $filename );
				$success = unlink( $filename );
				GFCommon::log_debug( __METHOD__ . '(): Delete successful: ' . ( $success ? 'yes' : 'no' ) );
			}
		}
	}

	/**
	 * Deletes any log files that are older than one month.
	 *
	 * @since  2.0.0
	 * @access public
	 */
	public static function delete_old_log_files() {
		GFCommon::log_debug( __METHOD__ . '(): Starting.' );
		$uploads_folder = RGFormsModel::get_upload_root();
		if ( ! is_dir( $uploads_folder ) || is_link( $uploads_folder ) ) {
			GFCommon::log_debug( __METHOD__ . '(): No upload root - bailing.' );

			return;
		}
		$logs_folder = $uploads_folder . 'logs';
		if ( ! is_dir( $logs_folder ) || is_link( $logs_folder ) ) {
			GFCommon::log_debug( __METHOD__ . '():  No logs folder - bailing.' );

			return;
		}
		GFCommon::log_debug( __METHOD__ . '(): Start deleting old log files' );
		foreach ( GFCommon::glob( '*.txt', $logs_folder . DIRECTORY_SEPARATOR ) as $filename ) {
			$timestamp = filemtime( $filename );
			if ( $timestamp < time() - MONTH_IN_SECONDS ) {
				// Delete files over one month old
				GFCommon::log_debug( __METHOD__ . '(): Proceeding to delete ' . $filename );
				$success = unlink( $filename );
				GFCommon::log_debug( __METHOD__ . '(): Delete successful: ' . ( $success ? 'yes' : 'no' ) );
			}
		}
	}

	/**
	 * Deletes all rows in the lead table that don't have corresponding rows in the details table.
	 *
	 * @deprecated
	 * @since  2.0.0
	 * @access public
	 * @global $wpdb
	 */
	public static function delete_orphaned_entries() {
		_deprecated_function( __METHOD__, '2.4.17' );

		global $wpdb;

		if ( version_compare( GFFormsModel::get_database_version(), '2.3-beta-1', '<' ) || GFFormsModel::has_batch_field_operations() ) {
			return;
		}

		GFCommon::log_debug( __METHOD__ . '(): Starting to delete orphaned entries' );
		$entry_table      = GFFormsModel::get_entry_table_name();
		$entry_meta_table = GFFormsModel::get_entry_meta_table_name();
		$sql              = "DELETE FROM {$entry_table} WHERE id NOT IN( SELECT entry_id FROM {$entry_meta_table} )";
		$result           = $wpdb->query( $sql );
		GFCommon::log_debug( __METHOD__ . '(): Delete result: ' . print_r( $result, true ) );
	}

	/**
	 * Hooked into the 'admin_head' action.
	 *
	 * Outputs the styles for the Forms Toolbar menu.
	 * Outputs gf vars if required.
	 *
	 * @since  2.0.1.2
	 * @access public
	 */
	public static function load_admin_bar_styles() {

		if ( ! get_option( 'gform_enable_toolbar_menu' ) ) {
			return;
		}

		if ( ! GFCommon::current_user_can_any( array(
			'gravityforms_edit_forms',
			'gravityforms_create_form',
			'gravityforms_preview_forms',
			'gravityforms_view_entries'
		) )
		) {
			// The current user can't use anything on the menu so bail.
			return;
		}

		?>
		<style>
			.gforms-menu-icon {
				float: left;
				width: 26px !important;
				height: 30px !important;
				background-repeat: no-repeat;
				background-position: 0 6px;
				background-size: 20px;
			}

			@media screen and ( max-width: 782px ) {
				#wpadminbar #wp-admin-bar-gform-forms .ab-item {
					line-height: 53px;
					height: 46px !important;
					width: 52px !important;
					display: block;
					background-size: 36px 36px;
					background-position: 7px 6px;
				}

				#wpadminbar li#wp-admin-bar-gform-forms {
					display: block;
				}

			}
		</style>
		<?php

	}

	/**
	 * Preload the webfonts we use as font-face directives to avoid FOUT.
	 *
	 * @since 2.5
	 *
	 * @return void
	 */
	public static function preload_webfonts() {
		$preloaded = array(
				'inter-medium-webfont.woff',
				'inter-medium-webfont.woff2',
				'inter-regular-webfont.woff',
				'inter-regular-webfont.woff2',
				'inter-semibold-webfont.woff',
				'inter-semibold-webfont.woff2',
		);

		foreach( $preloaded as $font_file ) {
			$url = GFCommon::get_font_url( $font_file );
			printf( '<link rel="preload" as="font" href="%s" type="font/woff2" crossorigin="anonymous">%s', esc_url( $url ), "\r\n" );
		}
	}

	/**
	 * Drops a table index.
	 *
	 * @access     public
	 * @global       $wpdb
	 *
	 * @param string $table The table that the index will be dropped from.
	 * @param string $index The index to be dropped.
	 *
	 * @return void
	 * @deprecated Use gf_upgrade()->drop_index() instead
	 */
	public static function drop_index( $table, $index ) {
		_deprecated_function( 'This function has been deprecated. Use gf_upgrade()->drop_index() instead', '2.2', 'gf_upgrade()->drop_index()' );

		gf_upgrade()->drop_index( $table, $index );

	}

	/**
	 * Fixes case for database queries.
	 *
	 * @deprecated 2.2
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $cqueries Queries to be fixed.
	 *
	 * @return array $queries Queries after processing.
	 */
	public static function dbdelta_fix_case( $cqueries ) {
		_deprecated_function( 'dbdelta_fix_case', '2.2', 'gf_upgrade()->dbdelta_fix_case()' );

		return gf_upgrade()->dbdelta_fix_case( $cqueries );
	}

	public static function setup( $force_setup = false ) {

		_deprecated_function( 'This function has been deprecated. Use gf_upgrade()->maybe_upgrade() or gf_upgrade()->upgrade() instead', '2.2', 'gf_upgrade()->upgrade() or gf_upgrade()->maybe_upgrade()' );

		if ( $force_setup ) {
			$current_version = get_option( 'rg_form_version' );
			gf_upgrade()->upgrade( $current_version, true );
		} else {
			gf_upgrade()->maybe_upgrade();
		}
	}

	public static function setup_database() {
		_deprecated_function( 'This function has been deprecated. Use gf_upgrade()->upgrade_schema()', '2.2', 'gf_upgrade()->upgrade_schema()' );

		gf_upgrade()->upgrade_schema();
	}

	/**
	 * Creates an instance of GF_Background_Upgrader and stores it in GFForms::$background_upgrader
	 *
	 * @since 2.3
	 */
	public static function init_background_upgrader() {
		if ( empty( self::$background_upgrader ) ) {
			require_once GF_PLUGIN_DIR_PATH . 'includes/class-gf-background-upgrader.php';
			self::$background_upgrader = new GF_Background_Upgrader();
		}
	}

	/**
	 * Target for the WordPress 'query' filter. Triggers an PHP Notice if an outdated add-on or custom code attempts to
	 * access tables that are not valid for this version of Gravity Forms.
	 *
	 * @since 2.3
	 *
	 * @param $query
	 *
	 * @return string
	 */
	public static function filter_query( $query ) {
		global $wpdb;

		if ( preg_match( "/$wpdb->prefix(rg_lead_detail|rg_lead_meta|rg_lead_notes|rg_lead|rg_form_meta|rg_form_view|rg_form|rg_incomplete_submissions)/", $query, $matches ) ) {
			if ( version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '>' ) ) {
				$table_name = $matches[0];
				$url = 'https://docs.gravityforms.com/database-storage-structure-reference/#changes-from-gravity-forms-2-2';
				/* translators: 1: The table name 2: the URL with further details */
				$message = esc_html__( 'An outdated add-on or custom code is attempting to access the %1$s table which is not valid in this version of Gravity Forms. Update your add-ons and custom code to prevent loss of form data. Further details: %2$s', 'gravityforms' );
				$notice = sprintf( $message, $table_name, $url );
				trigger_error( $notice );
			}
		}
		return $query;
	}

	/**
	 * Target for the admin_notices action.
	 *
	 * @since 2.3
	 *
	 * Displays site-side dismissible notices.
	 */
	public static function action_admin_notices() {
		GFCommon::display_dismissible_message( false, 'site-wide' );
	}

	/**
	 * Registers the Gravity Forms data exporter.
	 *
	 * @since 2.4
	 *
	 * @param array $exporters
	 *
	 * @return array
	 */
	public static function register_data_exporter( $exporters ) {
		$exporters['gravityforms'] = array(
			'exporter_friendly_name' => __( 'Gravity Forms Exporter' ),
			'callback' => array( 'GFForms', 'data_exporter' ),
		);
		return $exporters;
	}

	/**
	 * Registers the Gravity Forms data eraser.
	 *
	 * @since 2.4
	 *
	 * @param array $erasers
	 *
	 * @return array
	 */
	public static function register_data_eraser( $erasers ) {
		$erasers['gravityforms'] = array(
			'eraser_friendly_name' => __( 'Gravity Forms Eraser' ),
			'callback' => array( 'GFForms', 'data_eraser' ),
		);
		return $erasers;
	}

	/**
	 * Callback for the WordPress data exporter.
	 *
	 * @since 2.4
	 *
	 * @param string $email_address
	 * @param int    $page
	 *
	 * @return array
	 */
	public static function data_exporter( $email_address, $page = 1 ) {

		require_once( 'includes/class-personal-data.php' );

		return GF_Personal_Data::data_exporter( $email_address, $page );
	}

	/**
	 * Callback for the WordPress data eraser.
	 *
	 * @since 2.4
	 *
	 * @param string $email_address
	 * @param int    $page
	 *
	 * @return array
	 */
	public static function data_eraser( $email_address, $page = 1 ) {

		require_once( 'includes/class-personal-data.php' );

		return GF_Personal_Data::data_eraser( $email_address, $page );
	}

	/**
	 * Initialize an ob_start() buffer with a callback to ensure our hooks JS has output on the page.
	 *
	 * @since 2.5.3
	 *
	 * @return void
	 */
	public static function init_buffer() {
		require_once GFCommon::get_base_path() . '/includes/libraries/class-dom-parser.php';
		$parser = new Dom_Parser( '' );

		if ( ! $parser->is_parseable_request( false ) ) {
			return;
		}

		if ( strpos( php_sapi_name(), 'cli' ) !== false ) {
			return;
		}

		ob_start( array( 'GFForms', 'ensure_hook_js_output' ) );
	}

	/**
	 * Callback to fire when ob_flush() is called. Allows us to ensure that our Hooks JS has been output on the page,
     * even in heavily-cached or concatenated environments.
	 *
	 * @since 2.5.3
	 *
	 * @param string $content The buffer content.
	 *
	 * @return string
	 */
	public static function ensure_hook_js_output( $content ) {
		require_once GFCommon::get_base_path() . '/includes/libraries/class-dom-parser.php';
		$parser = new Dom_Parser( $content );

		return $parser->get_injected_html();
	}
}

/**
 * Class RGForms
 *
 * @deprecated
 * Exists only for backwards compatibility. Used GFForms instead.
 */
class RGForms extends GFForms {
}

/**
 * Main Gravity Forms function call.
 *
 * Should be used to insert a Gravity Form from code.
 *
 * @param string $id The form ID
 * @param bool $display_title If the form title should be displayed in the form. Defaults to true.
 * @param bool $display_description If the form description should be displayed in the form. Defaults to true.
 * @param bool $display_inactive If the form should be displayed if marked as inactive. Defaults to false.
 * @param array|null $field_values Default field values. Defaults to null.
 * @param bool $ajax If submission should be processed via AJAX. Defaults to false.
 * @param int $tabindex Starting tabindex. Defaults to 0.
 * @param bool $echo If the field should be echoed.  Defaults to true.
 *
 * @return string|void
 */
function gravity_form( $id, $display_title = true, $display_description = true, $display_inactive = false, $field_values = null, $ajax = false, $tabindex = 0, $echo = true ) {
	if ( ! $echo ) {
		return GFForms::get_form( $id, $display_title, $display_description, $display_inactive, $field_values, $ajax, $tabindex );
	}

	echo GFForms::get_form( $id, $display_title, $display_description, $display_inactive, $field_values, $ajax, $tabindex );
}

/**
 * @return GF_Upgrade
 */
function gf_upgrade() {
	require_once( GFCommon::get_base_path() . '/includes/class-gf-upgrade.php' );

	return GF_Upgrade::get_instance();
}


/**
 * Enqueues form scripts for the specified form.
 *
 * @uses GFForms::enqueue_form_scripts()
 *
 * @param string $form_id The form ID.
 * @param bool $is_ajax If the form is submitted via AJAX.  Defaults to false.
 */
function gravity_form_enqueue_scripts( $form_id, $is_ajax = false ) {
	GFForms::enqueue_form_scripts( $form_id, $is_ajax );
}

if ( ! function_exists( 'rgget' ) ) {
	/**
	 * Helper function for getting values from query strings or arrays
	 *
	 * @param string $name The key
	 * @param array $array The array to search through.  If null, checks query strings.  Defaults to null.
	 *
	 * @return string The value.  If none found, empty string.
	 */
	function rgget( $name, $array = null ) {
		if ( ! isset( $array ) ) {
			$array = $_GET;
		}

		if ( ! is_array( $array ) ) {
			return '';
		}

		if ( isset( $array[ $name ] ) ) {
			return $array[ $name ];
		}

		return '';
	}
}

if ( ! function_exists( 'rgpost' ) ) {
	/**
	 * Helper function to obtain POST values.
	 *
	 * @param string $name The key
	 * @param bool $do_stripslashes Optional. Performs stripslashes_deep.  Defaults to true.
	 *
	 * @return string The value.  If none found, empty string.
	 */
	function rgpost( $name, $do_stripslashes = true ) {
		if ( isset( $_POST[ $name ] ) ) {
			return $do_stripslashes ? stripslashes_deep( $_POST[ $name ] ) : $_POST[ $name ];
		}

		return '';
	}
}

if ( ! function_exists( 'rgar' ) ) {
	/**
	 * Get a specific property of an array without needing to check if that property exists.
	 *
	 * Provide a default value if you want to return a specific value if the property is not set.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $array Array from which the property's value should be retrieved.
	 * @param string $prop Name of the property to be retrieved.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	function rgar( $array, $prop, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		if ( isset( $array[ $prop ] ) ) {
			$value = $array[ $prop ];
		} else {
			$value = '';
		}

		return empty( $value ) && $default !== null ? $default : $value;
	}
}

if ( ! function_exists( 'rgars' ) ) {
	/**
	 * Gets a specific property within a multidimensional array.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $array The array to search in.
	 * @param string $name The name of the property to find.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	function rgars( $array, $name, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		$names = explode( '/', $name );
		$val   = $array;
		foreach ( $names as $current_name ) {
			$val = rgar( $val, $current_name, $default );
		}

		return $val;
	}
}

if ( ! function_exists( 'rgempty' ) ) {
	/**
	 * Determines if a value is empty.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $name The property name to check.
	 * @param array $array Optional. An array to check through.  Otherwise, checks for POST variables.
	 *
	 * @return bool True if empty.  False otherwise.
	 */
	function rgempty( $name, $array = null ) {

		if ( is_array( $name ) ) {
			return empty( $name );
		}

		if ( ! $array ) {
			$array = $_POST;
		}

		$val = rgar( $array, $name );

		return empty( $val );
	}
}

if ( ! function_exists( 'rgblank' ) ) {
	/**
	 * Checks if the string is empty
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $text The string to check.
	 *
	 * @return bool True if empty.  False otherwise.
	 */
	function rgblank( $text ) {
		return empty( $text ) && ! is_array( $text ) && strval( $text ) != '0';
	}
}

if ( ! function_exists( 'rgobj' ) ) {
	/**
	 * Gets a property value from an object
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param object $obj The object to check
	 * @param string $name The property name to check for
	 *
	 * @return string The property value
	 */
	function rgobj( $obj, $name ) {
		if ( isset( $obj->$name ) ) {
			return $obj->$name;
		}

		return '';
	}
}
if ( ! function_exists( 'rgexplode' ) ) {
	/**
	 * Converts a delimiter separated string to an array.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $sep The delimiter between values
	 * @param string $string The string to convert
	 * @param int $count The expected number of items in the resulting array
	 *
	 * @return array $ary The exploded array
	 */
	function rgexplode( $sep, $string, $count ) {
		$ary = explode( $sep, $string );
		while ( count( $ary ) < $count ) {
			$ary[] = '';
		}

		return $ary;
	}
}

if ( ! function_exists( 'gf_apply_filters' ) ) {
	//function gf_apply_filters( $filter, $modifiers, $value ) {
	/**
	 * Gravity Forms pre-processing for apply_filters
	 *
	 * Allows additional filters based on form and field ID to be defined easily.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string|array $filter The name of the filter.
	 * @param mixed $value The value to filter.
	 *
	 * @return mixed The filtered value.
	 */
	function gf_apply_filters( $filter, $value ) {

		$args = func_get_args();

		if ( is_array( $filter ) ) {
			// func parameters are: $filter, $value
			$modifiers = array_splice( $filter, 1, count( $filter ) );
			$filter    = $filter[0];
			$args      = array_slice( $args, 2 );
		} else {
			//_deprecated_argument( 'gf_apply_filters', '1.9.14.20', "Modifiers should no longer be passed as a separate parameter. Combine the filter name and modifier(s) into an array and pass that array as the first parameter of the function. Example: gf_apply_filters( array( 'action_name', 'mod1', 'mod2' ), \$value, \$arg1, \$arg2 );" );
			// func parameters are: $filter, $modifier, $value
			$modifiers = ! is_array( $value ) ? array( $value ) : $value;
			$value     = $args[2];
			$args      = array_slice( $args, 3 );
		}

		// Add an empty modifier so the base filter will be applied as well
		array_unshift( $modifiers, '' );

		$args = array_pad( $args, 10, null );

		// Apply modified versions of filter
		foreach ( $modifiers as $modifier ) {
			$modifier = rgblank( $modifier ) ? '' : sprintf( '_%s', $modifier );
			$filter   .= $modifier;
			$value    = apply_filters( $filter, $value, $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9] );
		}

		return $value;
	}
}

if ( ! function_exists( 'gf_do_action' ) ) {
	/**
	 * Gravity Forms pre-processing for do_action.
	 *
	 * Allows additional actions based on form and field ID to be defined easily.
	 *
	 * @since  1.9.14.20 Modifiers should no longer be passed as a separate parameter.
	 * @since  1.9.12
	 * @access public
	 *
	 * @param string|array $action The action.
	 */
	function gf_do_action( $action ) {

		$args = func_get_args();

		if ( is_array( $action ) ) {
			// Func parameters are: $action, $value
			$modifiers = array_splice( $action, 1, count( $action ) );
			$action    = $action[0];
			$args      = array_slice( $args, 1 );
		} else {
			//_deprecated_argument( 'gf_do_action', '1.9.14.20', "Modifiers should no longer be passed as a separate parameter. Combine the action name and modifier(s) into an array and pass that array as the first parameter of the function. Example: gf_do_action( array( 'action_name', 'mod1', 'mod2' ), \$arg1, \$arg2 );" );
			// Func parameters are: $action, $modifier, $value
			$modifiers = ! is_array( $args[1] ) ? array( $args[1] ) : $args[1];
			$args      = array_slice( $args, 2 );
		}

		// Add an empty modifier so the base filter will be applied as well
		array_unshift( $modifiers, '' );

		$args = array_pad( $args, 10, null );

		// Apply modified versions of filter
		foreach ( $modifiers as $modifier ) {
			$modifier = rgblank( $modifier ) ? '' : sprintf( '_%s', $modifier );
			$action   .= $modifier;
			do_action( $action, $args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], $args[8], $args[9] );
		}
	}
}

if ( ! function_exists( 'gf_has_filters' ) ) {
	/**
	 * Determines if a callback has been registered for the specified filter.
	 *
	 * @since 2.4.18
	 *
	 * @param array $filter An array containing the filter tag and modifiers.
	 * @param bool|callable $function_to_check The optional callback to check for.
	 *
	 * @return bool
	 */
	function gf_has_filters( $filter, $function_to_check = false ) {
		$modifiers = array_splice( $filter, 1, count( $filter ) );
		$filter    = $filter[0];

		// Adding empty modifier for the base filter.
		array_unshift( $modifiers, '' );

		foreach ( $modifiers as $modifier ) {
			$modifier = rgblank( $modifier ) ? '' : sprintf( '_%s', $modifier );
			$filter   .= $modifier;
			if ( has_filter( $filter, $function_to_check ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'gf_has_filter' ) ) {
	/**
	 * Determines if a callback has been registered for the specified filter.
	 *
	 * @since 2.4.18
	 *
	 * @param array $filter An array containing the filter tag and modifiers.
	 * @param bool|callable $function_to_check The optional callback to check for.
	 *
	 * @return bool
	 */
	function gf_has_filter( $filter, $function_to_check = false ) {
		return gf_has_filters( $filter, $function_to_check );
	}
}

if ( ! function_exists( 'gf_has_action' ) ) {
	/**
	 * Determines if a callback has been registered for the specified action.
	 *
	 * @since 2.4.18
	 *
	 * @param array $action An array containing the action tag and modifiers.
	 * @param bool|callable $function_to_check The optional callback to check for.
	 *
	 * @return bool
	 */
	function gf_has_action( $action, $function_to_check = false ) {
		return gf_has_filters( $action, $function_to_check );
	}
}
