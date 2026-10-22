<?php

use Gravity_Forms\Gravity_Forms\Settings\Settings;
use \Gravity_Forms\Gravity_Forms\License;
use \Gravity_Forms\Gravity_Forms\Setup_Wizard\Endpoints\GF_Setup_Wizard_Endpoint_Save_Prefs;
use Gravity_Forms\Gravity_Forms\TranslationsPress_Updater;

class_exists( 'GFForms' ) || die();

/**
 * Class GFSettings
 *
 * Generates the Gravity Forms settings page
 */
class GFSettings {

	/**
	 * Stores the current instance of the Settings renderer.
	 *
	 * @since 2.5
	 *
	 * @var false|Settings
	 */
	private static $_settings_renderer = false;

	/**
	 * Settings pages associated with add-ons
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @var array $addon_pages
	 */
	public static $addon_pages = array();

	/**
	 * Used to hold the addon that has been uninstalled.
	 *
	 * @since  2.5
	 *
	 * @var string $uninstalled_addon
	 */
	private static $uninstalled_addon;

	/**
	 * Adds a settings page to the Gravity Forms settings.
	 *
	 * @since  Unknown
	 * @access public
	 * @uses GFSettings::$addon_pages
	 *
	 * @param string|array $name      The settings page slug.
	 * @param string|array $handler   The callback function to run for this settings page.
	 * @param string       $icon_path The path to the icon for the settings tab. @deprecated.
	 */
	public static function add_settings_page( $name, $handler, $icon_path = '' ) {

		if ( ! empty( $icon_path ) ) {
			_deprecated_argument( __METHOD__, '2.5', '$icon_path has been deprecated in favor of passing a dashicons string via $name[\'icon\']' );
		}

		$title = '';
		$icon  = 'gform-icon--cog';

		// if name is an array, assume that an array of args is passed.
		if ( is_array( $name ) ) {

			/**
			 * Extracting args.
			 *
			 * @var string       $name
			 * @var string       $title
			 * @var string       $tab_label
			 * @var string|array $handler
			 * @var string       $icon
			 */
			extract(
				wp_parse_args(
					$name, array(
						'name'      => '',
						'title'     => '',
						'tab_label' => '',
						'handler'   => false,
						'icon'      => 'gform-icon--cog',
					)
				)
			);

		}

		if ( ! isset( $tab_label ) || ! $tab_label ) {
			$tab_label = $name;
		}

		/**
		 * Adds additional actions after settings pages are registered.
		 *
		 * @since Unknown
		 *
		 * @param string|array $handler The callback function being run.
		 */
		add_action( 'gform_settings_' . str_replace( ' ', '_', $name ), $handler );
		self::$addon_pages[ $name ] = array( 'name' => $name, 'title' => $title, 'tab_label' => $tab_label, 'icon' => $icon );
	}

	/**
	 * Determines the content displayed on the Gravity Forms settings page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFSettings::get_subview()
	 * @uses GFSettings::gravityforms_settings_page()
	 * @uses GFSettings::settings_uninstall_page()
	 * @uses GFSettings::page_header()
	 * @uses GFSettings::page_footer()
	 *
	 * @return void
	 */
	public static function settings_page() {

		$subview = self::get_subview();

		switch ( $subview ) {
			case 'settings':
				self::gravityforms_settings_page();
				break;
			case 'recaptcha':
				self::recaptcha_page();
				break;
			case 'uninstall':
				self::settings_uninstall_page();
				break;
			default:
				self::page_header();

				/**
				 * Fires in the settings page depending on which page of the settings page you are in (the Subview).
				 *
				 * @since Unknown
				 *
				 * @param mixed $subview The sub-section of the main Form's settings
				 */
				do_action( 'gform_settings_' . str_replace( ' ', '_', $subview ) );
				self::page_footer();
		}
	}

	/**
	 * Displays the Gravity Forms uninstall page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFSettings::settings_page()
	 * @uses    GFSettings::page_header()
	 * @uses    GFCommon::current_user_can_any()
	 * @uses    GFFormsModel::drop_tables()
	 * @uses    GFCommon::delete_directory()
	 * @uses    GFFormsModel::get_upload_root()
	 * @uses    GFCommon::current_user_can_any()
	 * @uses    GFSettings::page_footer()
	 */
	public static function settings_uninstall_page() {

		self::page_header( __( 'Uninstall Gravity Forms', 'gravityforms' ), '' );
		if ( isset( $_POST['uninstall'] ) ) {

			check_admin_referer( 'gform_uninstall', 'gform_uninstall_nonce' );

			if ( ! GFCommon::current_user_can_uninstall() ) {
				die( esc_html__( "You don't have adequate permission to uninstall Gravity Forms.", 'gravityforms' ) );
			}

			/**
			 * Used to perform any cleanup tasks when the uninstall button has been clicked on the Forms > Settings > Uninstall page.
			 *
			 * @since 2.6.9
			 */
			do_action( 'gform_uninstalling' );

			// Background tasks cleanup moved to \Gravity_Forms\Gravity_Forms\Async\GF_Background_Process_Service_Provider().

			// Removing cron task
			wp_clear_scheduled_hook( 'gravityforms_cron' );

			// Dropping all tables
			RGFormsModel::drop_tables();

			// Removing options
			delete_option( 'rg_form_version' );
			delete_option( 'rg_gforms_disable_css' );
			delete_option( 'rg_gforms_enable_html5' );
			delete_option( 'rg_gforms_captcha_public_key' );
			delete_option( 'rg_gforms_captcha_private_key' );
			delete_option( 'rg_gforms_captcha_type' );
			delete_option( 'rg_gforms_message' );
			delete_option( 'rg_gforms_currency' );
			delete_option( 'rg_gforms_enable_akismet' );

			delete_option( 'gf_dismissed_upgrades' );
			delete_option( 'gf_db_version' );
			delete_option( 'gf_previous_db_version' );
			delete_option( 'gf_upgrade_lock' );
			delete_option( 'gf_submissions_block' );
			delete_option( 'gf_imported_file' );
			delete_option( 'gf_imported_theme_file' );
			delete_option( 'gf_rest_api_db_version' );

			delete_option( 'gform_api_count' );
			delete_option( 'gform_email_count' );
			delete_option( 'gform_enable_toolbar_menu' );
			delete_option( 'gform_enable_dashboard_widget' );
			delete_option( 'gform_enable_logging' );
			delete_option( 'gform_pending_installation' );
			delete_option( 'gform_enable_noconflict' );
			delete_option( 'gform_enable_background_updates' );
			delete_option( 'gform_sticky_admin_messages' );
			delete_option( 'gform_upgrade_status' );
			delete_option( 'gform_custom_choices' );
			delete_option( 'gform_recaptcha_keys_status' );
			delete_option( 'gform_upload_page_slug' );
			delete_option( 'gform_enable_async_notifications' );

			delete_option( 'gravityformsaddon_gravityformswebapi_version' );
			delete_option( 'gravityformsaddon_gravityformswebapi_settings' );

			// Remove setup wizard data.
			GFForms::get_service_container()->get( \Gravity_Forms\Gravity_Forms\Setup_Wizard\GF_Setup_Wizard_Service_Provider::SAVE_PREFS_ENDPOINT )->remove_setup_data();

			// Removes license key
			GFFormsModel::save_key( '' );

			// Removing gravity forms upload folder
			GFCommon::delete_directory( RGFormsModel::get_upload_root() );

			// Delete Logging settings and logging files
			gf_logging()->delete_settings();
			gf_logging()->delete_log_files();

			delete_option( 'widget_gform_widget' );
			delete_option( 'rg_gforms_default_theme' );
			delete_option( 'rg_form_original_version' );
			delete_option( 'gform_version_info' );

			delete_option( 'gf_telemetry_data' );
			delete_option( 'gf_last_telemetry_run' );

			delete_transient( 'rg_gforms_license' );

			if ( ! class_exists( 'TranslationsPress_Updater' ) ) {
				require_once GF_PLUGIN_DIR_PATH . '/includes/class-translationspress-updater.php';
			}

			delete_site_transient( TranslationsPress_Updater::T15S_TRANSIENT_KEY );

			// Deactivating plugin
			$plugin = 'gravityforms/gravityforms.php';
			deactivate_plugins( $plugin );
			update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );

			?>
			<div class="updated fade gf-notice notice-success" role="alert"><?php echo sprintf( esc_html__( 'Gravity Forms has been successfully uninstalled. It can be re-activated from the %splugins page%s.', 'gravityforms' ), "<a href='plugins.php'>", '</a>' ) ?></div>
			<?php
			return;
		}

		self::uninstall_addon_message();

		?>

		<div class="gform-settings-panel">
			<header class="gform-settings-panel__header">
				<h4 class="gform-settings-panel__title"><?php esc_html_e( 'Uninstall Gravity Forms', 'gravityforms' ); ?></h4>
			</header>
			<div class="gform-settings-panel__content">
				<p class="alert error">
					<?php esc_html_e('This operation deletes ALL Gravity Forms settings. If you continue, you will NOT be able to retrieve these settings.', 'gravityforms'); ?>
				</p>
				<form action="" method="post">
					<?php
					if ( GFCommon::current_user_can_uninstall() ) {

						wp_nonce_field( 'gform_uninstall', 'gform_uninstall_nonce' );

						$alert_title   = esc_html__( 'Uninstall Gravity Forms', 'gravityforms' );
						$alert_message = esc_html__( 'Warning! ALL Gravity Forms data, including form entries, will be deleted. This action cannot be undone. \'OK\' to delete, \'Cancel\' to stop.', 'gravityforms' );

						$uninstall_button = sprintf(
							'<input type="submit" name="uninstall" id="uninstall-button" data-dialog-title="%1$s" data-dialog-confirm="%2$s" class="button red" value="%3$s" />',
							esc_attr( $alert_title ),
							esc_attr( $alert_message ),
							esc_attr__( 'Uninstall Gravity Forms', 'gravityforms' )
						);

						echo $uninstall_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</form>
			</div>
		</div>
		<?php

		self::uninstall_addons();

		self::page_footer();
	}

	/**
	 * Handles the uninstallation process for addons from the settings page.
	 *
	 * @since  2.5
	 */
	private static function uninstall_addons() {
		$uninstallable_addons = GFAddOn::get_registered_addons( true );

		// Display the complete list of addons to install.
		if ( ! rgpost( 'uninstall_addon' ) ) {
			GFAddOn::addons_for_uninstall( $uninstallable_addons );
			return;
		}

		// Uninstall the addon and remove it from the list of installed addons on page reload.
		check_admin_referer( 'uninstall', 'gf_addon_uninstall' );

		foreach ( $uninstallable_addons as $key => $addon ) {
			if ( rgpost( 'addon' ) !== $addon->get_short_title() ) {
				continue;
			}

			unset( $uninstallable_addons[ $key ] );
			$addon->uninstall_addon();
			break;
		}

		GFAddOn::addons_for_uninstall( array_values( $uninstallable_addons ) );
	}

	/**
	 * Renders the uninstall message when an addon is uninstalled.
	 *
	 * @since  2.5
	 *
	 */
	private static function uninstall_addon_message() {
		if ( isset( self::$uninstalled_addon ) ) {
			?>
			<div class="alert success"><?php echo sprintf( esc_html__( '%s uninstalled. It can be re-activated from the %splugins page%s.', 'gravityforms' ), esc_html__( self::$uninstalled_addon ), "<a href='plugins.php'>", '</a>' ) ?></div>
			<?php
		}
	}



	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Displays the main Gravity Forms settings page.
	 *
	 * @since  Unknown
	 * @access public
	 * @global $wpdb
	 */
	public static function gravityforms_settings_page() {

		if ( ! GFCommon::ensure_wp_version() ) {
			return;
		}

		self::page_header();

		wp_enqueue_style( 'gform_admin' );

		// Initialize Settings renderer.
		if ( ! self::get_settings_renderer() ) {
			self::initialize_plugin_settings();
		}

		self::get_settings_renderer()->render();

		self::page_footer();

	}

	/**
	* Determine whether Orbital should be the default theme.
	*
 	* @since 2.7.15
	*
	* @return bool
	*/
    public static function is_orbital_default() {
		$theme_option = get_option( 'rg_gforms_default_theme' );

		// Fallback if the option is not set
		if ( ! $theme_option ) {
			$versions = gf_upgrade()->get_versions();

			// New install or upgrade from version that supports this feature
			if ( version_compare( get_option( 'rg_form_original_version', $versions['version'] ), '2.7.14.2', '>=' ) ) {
				return true;
			}

			// Upgrade from version prior to this feature
			if ( version_compare( $versions['previous_db_version'], '2.7.14.2', '<' ) ) {
				return false;
			}
		}

		if ( 'orbital' == $theme_option ) {
			return true;
		}

		return false;
    }



	/**
	 * Prepare Plugin Settings fields.
	 *
	 * @since 2.5
	 * @since 2.10.0 Added the background notifications setting.
	 *
	 * @return array
	 */
	private static function plugin_settings_fields() {
		$license_section_description = esc_html__( 'A valid license key is required for access to automatic plugin upgrades and product support.', 'gravityforms' );
		$is_hidden                   = false;
		if ( ! is_main_site() && GFCommon::is_network_active() ) {
			$is_hidden                   = true;
			$license_section_description = esc_html__( 'License key is managed by the administrator of this network', 'gravityforms' );
		}

		$fields = array(
			'license_key'         => array(
				'title'       => esc_html__( 'Support License Key', 'gravityforms' ),
				'class'       => 'gform-settings-panel--full',
				'description' => $license_section_description,
				'fields'      => array(
					array(
						'name'                => 'license_key',
						'label'               => esc_html__( 'Paste Your License Key Here', 'gravityforms' ),
						'type'                => 'text',
						'input_type'          => 'password',
						'callback'            => array( 'GFSettings', 'license_key_render_callback' ),
						'class'               => 'gform-admin-input',
						'validation_callback' => array( 'GFSettings', 'license_key_validation_callback' ),
						'hidden'              => $is_hidden,
						'after_input'         => function () {
							/**
							 * @var License\GF_License_API_Connector $license_connector
							 */
							$license_connector = GFForms::get_service_container()->get( License\GF_License_Service_Provider::LICENSE_API_CONNECTOR );
							$is_save_postback  = self::get_settings_renderer()->is_save_postback();
							$license_key       = $is_save_postback ? rgpost( '_gform_setting_license_key' ) : GFCommon::get_key();

							if ( empty( $license_key ) ) {
								delete_transient( 'rg_gforms_registration_error' );
								return '';
							}

							$license_info      = $license_connector->check_license( trim( $license_key ), ! $is_save_postback );
							$usability         = $license_info->get_usability();

							$license_key_alert = sprintf(
								'<div class="alert gforms_note_%s">%s %s</div>',
								$usability,
								$is_save_postback && ! $license_info->can_be_used() ? __( 'Your license key was not updated. ', 'gravityforms' ) : null,
								License\GF_License_Statuses::get_message_for_code( $license_info->get_status(), $license_info->get_error_message() )
							);

							delete_transient( 'rg_gforms_registration_error' );

							return $license_key_alert;
						},
						'feedback_callback'   => function () {
							$license_key = GFCommon::get_key();

							if ( empty( $license_key ) ) {
								return License\GF_License_Statuses::USABILITY_ALLOWED;
							}

							/**
							 * @var License\GF_License_API_Connector $license_connector
							 */
							$license_connector = GFForms::get_service_container()->get( License\GF_License_Service_Provider::LICENSE_API_CONNECTOR );
							$license_info      = $license_connector->check_license();

							return $license_info->get_usability();
						},
						'save_callback'       => function( $field, $value ) {
							// Remove non-alphanumeric characters.
							$value = preg_replace( '/[^a-zA-Z0-9]/', '', $value );
							if ( isset( $_POST['_gform_setting_license_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
								GFFormsModel::save_key( $value );
							}

							return $value;
						},
					),
				),
			),
			'license_key_details' => array(
				'id'     => 'section_license_key_details',
				'title'  => __( 'Your License Details', 'gravityforms' ),
				'class'  => 'gform-settings-panel--no-padding gform-settings-panel--license-details',
				'fields' => array(
					array(
						'name' => 'license_key_details',
						'type' => 'html',
						'html' => array( 'GFSettings', 'license_key_details_callback' ),
					),
				),
			),
			'currency'            => array(
				'id'     => 'section_currency',
				'title'  => esc_html__( 'Default Currency', 'gravityforms' ),
				'class'  => 'gform-settings-panel--half',
				'fields' => array(
					array(
						'name'          => 'currency',
						'description'   => esc_html__( 'Select the default currency for your forms. This is used for product fields, credit card fields and others.', 'gravityforms' ),
						'type'          => 'select',
						'choices'       => RGCurrency::get_grouped_currency_options(),
						'enhanced_ui'   => false,
						'after_select'  => self::currency_message_callback(),
						'save_callback' => function( $field, $value ) {
							update_option( 'rg_gforms_currency', $value );

							return $value;
						},
					),
				),
			),
			'async_notifications' => array(
				'id'          => 'section_enable_async_notifications',
				'title'       => esc_html__( 'Background Notifications', 'gravityforms' ),
				'description' => esc_html__( 'Enable background (asynchronous) notifications to improve form submission performance by using a separate request to send the notifications, so the user can see the confirmation before notification sending has completed.', 'gravityforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_async_notifications',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Background Notifications', 'gravityforms' ),
						'save_callback' => function ( $field, $value ) {
							update_option( 'gform_enable_async_notifications', $value ? 1 : 0, false );

							return $value;
						},
					),
				),
			),
			'logging'             => array(
				'id'          => 'section_enable_logging',
				'title'       => esc_html__( 'Logging', 'gravityforms' ),
				'description' => esc_html__( 'Enable if you would like logging within Gravity Forms. Logging allows you to easily debug the inner workings of Gravity Forms to solve any possible issues. ', 'gravityforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_logging',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Logging', 'gravityforms' ),
						'save_callback' => function( $field, $value ) {
							if ( (bool) $value ) {
								GFSettings::enable_logging();
							} else {
								GFSettings::disable_logging();
							}

							return $value;
						},
					),
				),
			),
		);

		$fields['default_theme'] = array(
			'id'     => 'section_default_theme',
			'title'  => esc_html__( 'Default Form Theme', 'gravityforms' ),
			'class'  => 'gform-settings-panel--half',
			'fields' => array(
					array(
					'name'          => 'default_theme',
					'type'          => 'select',
					'choices'       => array(
						array(
							'label'   => esc_html__( 'Gravity Forms 2.5 Theme', 'gravityforms' ),
							'value'   => 'gravity-theme',
							'default' => ! self::is_orbital_default(),
						),
						array(
							'label'   => esc_html__( 'Orbital Theme (Recommended)', 'gravityforms' ),
							'value'   => 'orbital',
							'default' => self::is_orbital_default(),
						),
					),
					'description'   => sprintf(
						'%s&nbsp;<a href="%s" target="_blank">%s<span class="screen-reader-text">%s</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>',
						esc_html__( 'This theme will be used by default everywhere forms are embedded on your site', 'gravityforms' ),
						'https://docs.gravityforms.com/block-themes-and-style-settings/',
						esc_html__( 'Learn more about form theme and style settings.', 'gravityforms' ),
						esc_html__( '(opens in a new tab)', 'gravityforms' )
					),
					'save_callback' => function( $field, $value ) {
						update_option( 'rg_gforms_default_theme', $value );

						return $value;
					},
				),
			),
		);


        $fields['toolbar'] = array(
				'id'          => 'section_enable_toolbar',
				'title'       => esc_html__( 'Toolbar Menu', 'gravityforms' ),
				'description' => esc_html__( 'Enable to display the forms menu in the WordPress top toolbar. The forms menu will display the ten forms recently opened in the form editor.', 'gravityforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_toolbar',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Toolbar Menu', 'gravityforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'gform_enable_toolbar_menu', (bool) $value );

							return $value;
						},
					),
				),
        );

		$fields['dashboard_widget'] = array(
				'id'          => 'section_enable_dashboard_widget',
				'title'       => esc_html__( 'Dashboard Widget', 'gravityforms' ),
				'description' => esc_html__( 'Turn on to enable the Gravity Forms dashboard widget. The dashboard widget displays a list of forms and the number of entries each form has.', 'gravityforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_dashboard_widget',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Dashboard Widget', 'gravityforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'gform_enable_dashboard_widget', $value );

							return $value;
						},
						'default_value' => get_option( 'gform_enable_dashboard_widget' ),
					),
				),
		);

        $fields['background_updates'] = array(
				'id'          => 'section_enable_background_updates',
				'title'       => esc_html__( 'Automatic Background Updates', 'gravityforms' ),
				'description' => esc_html__( 'Enable to allow Gravity Forms to download and install bug fixes and security updates automatically in the background. Requires a valid license key.', 'gravityforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_background_updates',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Automatic Background Updates', 'gravityforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'gform_enable_background_updates', (bool) $value );

							return $value;
						},
					),
				),
			);

        $fields['no_conflict_mode'] = array(
				'id'          => 'section_conflict_mode',
				'title'       => esc_html__( 'No Conflict Mode', 'gravityforms' ),
				'description' => esc_html__( 'Enable to prevent extraneous scripts and styles from being printed on a Gravity Forms admin pages, reducing conflicts with other plugins and themes.', 'gravityforms' ),
				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'enable_noconflict',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'No Conflict Mode', 'gravityforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'gform_enable_noconflict', (bool) $value );

							return $value;
						},
					),
				),
			);

        $fields['akismet'] = array(
				'id'          => 'section_enable_akismet',
				'title'       => esc_html__( 'Akismet Integration', 'gravityforms' ),
				'description' => esc_html__( 'Protect your form entries from spam using Akismet.', 'gravityforms' ),
				'class'       => 'gform-settings-panel--half',
				'dependency'  => array( 'GFCommon', 'has_akismet' ),
				'fields'      => array(
					array(
						'name'          => 'enable_akismet',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Enable Akismet Integration', 'gravityforms' ),
						'default_value' => true,
						'save_callback' => function( $field, $value ) {
							update_option( 'rg_gforms_enable_akismet', (bool) $value );

							return $value;
						},
					),
				),
			);

		$fields['telemetry'] = array(
			'id'          => 'section_enable_telemetry_collection',
			'title'       => esc_html__( 'Data Collection', 'gravityforms' ),
			'description' => sprintf(
				esc_html__( 'We love improving the form building experience for everyone in our community. By enabling data collection, you can help us learn more about how our customers use Gravity Forms. %1$sLearn more...%2$s', 'gravityforms' ),
				'<a target="_blank" href="https://docs.gravityforms.com/about-additional-data-collection/">',
				'<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'gravityforms' ) . '</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>'
			),
			'class'       => 'gform-settings-panel--half',
			'fields'      => array(
				array(
					'name'          => 'rg_gforms_dataCollection',
					'type'          => 'toggle',
					'default_value' => get_option( 'rg_gforms_dataCollection', 0 ),
					'toggle_label'  => esc_html__( 'Enable Data Collection', 'gravityforms' ),
					'save_callback' => function( $field, $value ) {
						update_option( 'rg_gforms_dataCollection', (bool) $value ? 1 : 0 );

						return $value;
					},
				),
			),
		);

		/**
		 * Allows forcing the display of the disable CSS setting.
		 *
		 * @since 2.8
		 *
		 * @param bool $gform_display_disable_css_setting Indicates if the disable CSS setting should be displayed or not.
		 */
		$gform_display_disable_css_setting = apply_filters( 'gform_display_disable_css_setting', (bool) get_option( 'rg_gforms_disable_css' ) );

		if ( $gform_display_disable_css_setting ) {
			$fields['css'] = array(
				'id'          => 'section_default_css',
				'title'       => esc_html__( 'Output Default CSS', 'gravityforms' ),
				'description' => sprintf(
						esc_html__( 'Enable this option to output the default form CSS. Disable it if you plan to create your own CSS in a child theme. Note: after Gravity Forms 2.8, this setting will no longer appear on the settings page. If you previously had it enabled, you will need to use the %sgform_disable_css%s filter to disable it.', 'gravityforms' ),
						'<a href="https://docs.gravityforms.com/gform_disable_css/" target="_blank">',
						'<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'gravityforms' ) . '</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>'
						),

				'class'       => 'gform-settings-panel--half',
				'fields'      => array(
					array(
						'name'          => 'disable_css',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Disable CSS', 'gravityforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'rg_gforms_disable_css', ! (bool) $value );

							return $value;
						},
					),
				),
			);
		}

		// Check if user has hidden license details in the installation wizard.
		$hide_license_option = get_option( 'rg_gforms_' . GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_HIDE_LICENSE, false );

		// Cast license option to bool.
		if ( $hide_license_option === 'true' ) {
			$hide_license_option = true;
		}

		if ( $hide_license_option === 'false' ) {
			$hide_license_option = false;
		}

		$display_license_details = ! $hide_license_option;

		/**
		 * Allows display of the license details panel to be disabled.
		 *
		 * @since 2.5.17
		 *
		 * @param bool $display_license_details Indicates if the license details panel should be displayed.
		 */
		if ( ! apply_filters( 'gform_settings_display_license_details', $display_license_details ) ) {
			unset( $fields['license_key_details'] );
		}

		/**
		 * Allows the plugin settings fields to be overridden before they are displayed.
		 *
		 * @since 2.5.17
		 *
		 * @param array $fields The plugin settings fields.
		 */
		return array_values( apply_filters( 'gform_plugin_settings_fields', $fields ) );
	}

	public static function license_key_details_callback() {
		$key             = GFCommon::get_key();
		$empty_template  = '<div class="gform-p-16">%s</div>';
		$invalid_message = sprintf( $empty_template, esc_html__( 'Please enter a valid license key to see details.', 'gravityforms' ) );

		if ( empty( $key ) ) {
			return $invalid_message;
		}

		/**
		 * @var License\GF_License_API_Connector $license_connector
		 */
		$license_connector = GFForms::get_service_container()->get( License\GF_License_Service_Provider::LICENSE_API_CONNECTOR );
		$license_info      = $license_connector->check_license( $key );

		if ( ! $license_info->can_be_used() ) {
			return $invalid_message;
		} else if ( empty( $license_info->get_data_value( 'product_name' ) ) ) {
			return sprintf( $empty_template, esc_html__( 'License details are not available at this time.', 'gravityforms' ) );
		}

		$cta              = $license_info->get_cta();
		$days_left_header = $cta['type'] === 'text' ? __( 'Days Left', 'gravityforms' ) : '';

		ob_start();
		?>
		<table class="gform-table gform-table--responsive gform-table--no-outer-border gform-table--license-ui">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'License Type', 'gravityforms' ); ?></th>
					<th scope="col"><?php esc_html_e( 'License Status', 'gravityforms' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Purchase Date', 'gravityforms' ); ?></th>
					<th scope="col"><?php esc_html_e( 'License Activations', 'gravityforms' ); ?></th>
					<th scope="col"><?php echo esc_html( $license_info->renewal_text() ); ?></th>
					<th scope="col"><?php echo esc_html( $days_left_header ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td data-header="<?php esc_html_e( 'License Type', 'gravityforms' ); ?>">
						<p><?php echo esc_html( trim( str_replace( 'Gravity Forms', '', $license_info->get_data_value( 'product_name' ) ) ) ); ?></p>
					</td>
					<td data-header="<?php esc_html_e( 'License Status', 'gravityforms' ); ?>">
						<p>
							<?php
								$status_class = $license_info->display_as_valid() ? 'active' : 'error';
								$status_label = $license_info->get_display_status();
							?>
							<span class="gform-status-indicator gform-status-indicator--size-sm gform-status-indicator--theme-cosmos gform-status--no-hover gform-status--static gform-status--no-icon gform-status--<?php echo esc_html( $status_class ); ?>">
								<span class="gform-status-indicator-status gform-typography--weight-medium gform-typography--size-text-xs">
									<?php echo esc_html( $status_label ); ?>
								</span>
							</span>
						</p>
					</td>
					<td data-header="<?php esc_attr_e( 'Purchase Date', 'gravityforms' ); ?>">
						<p><?php echo esc_html( gmdate( 'M d, Y', strtotime( $license_info->get_data_value( 'date_created' ) ) ) ); ?></p>
					</td>
					<td data-header="<?php esc_attr_e( 'License Activations', 'gravityforms' ); ?>">
						<p>
							<?php $activation_class = $license_info->max_seats_exceeded() ? 'gform-c-error-text' : ''; ?>
							<span class="<?php echo esc_attr( $activation_class ); ?>">
								<?php echo esc_html( sprintf( '%s of %s', $license_info->get_data_value( 'active_sites' ), $license_info->get_data_value( 'max_sites' ) ) ); ?>
							</span>
						</p>
					</td>
					<td data-header="<?php echo esc_attr( $license_info->renewal_text() ); ?>">
						<p><?php echo esc_html( $license_info->renewal_date() ); ?></p>
					</td>
					<td data-header="<?php echo esc_attr( $days_left_header ); ?>">
						<p>
							<?php if ( $cta['type'] === 'button' ) : ?>
								<a
									class="gform-button gform-button--white gform-button--icon-leading gform-button--size-xs"
									href="<?php echo esc_url( $cta['link'] ); ?>"
									target="_blank"
									rel="noopener"
								>
									<i class="gform-button__icon gform-icon gform-icon--<?php echo esc_attr( $cta['class'] ); ?>" aria-hidden="true"></i>
									<?php echo esc_html( $cta['label'] ); ?>
									<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'gravityforms' ); ?></span>&nbsp;
									<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span>
								</a>
							<?php elseif ( $cta['type'] === 'text' ) : ?>
								<?php echo esc_html( $cta['content'] ); ?>
							<?php endif; ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php

		return ob_get_clean();
	}

	/**
	 * Callback to output any additional markup after the currency select markup.
	 *
	 * @since 2.5
	 *
	 * @return false|string
	 */
	public static function currency_message_callback() {
		// Start output buffer to capture any echoed output.
		ob_start();

		/**
		* Allows third-party code to add a message after the Currency setting markup.
		*
		* @since Unknown
		* @since 2.5 - Moved to currency message callback.
		*
		* @param string The default message.
		*/
		do_action( 'gform_currency_setting_message', '' );

		$output = ob_get_clean();

		// Message was echoed, return it.
		if ( ! empty( $output ) ) {
			return $output;
		}

		return '';
	}

	/**
	 * Render the License Key Field as a callback.
	 *
	 * Callback is used so that the gform_settings_key_field filter can be retained.
	 *
	 * @since 2.5
	 *
	 * @param object $field The Field Object for the rendered input.
	 *
	 * @return string
	 */
	public static function license_key_render_callback( $field ) {
		$html = apply_filters( 'gform_settings_key_field', $field->markup() );

		return $html;
	}

	/**
	 * Custom validation callback for the License Key Field.
	 *
	 * Callback is used so that we can skip validation if the License Key field is null.
	 *
	 * @since 2.5
	 *
	 * @param object $field The Field Object for the rendered input.
	 * @param mixed  $value The current posted field value.
	 *
	 * @return void
	 */
	public static function license_key_validation_callback( $field, $value ) {
		if ( is_null( $value ) ) {
			return;
		}

		$field->do_validation( $value );
	}

	/**
	 * Initialize Plugin Settings fields renderer.
	 *
	 * @since 2.5
	 * @since 2.10.0 Added the background notifications setting.
	 */
	public static function initialize_plugin_settings() {

		require_once( GFCommon::get_base_path() . '/tooltips.php' );

		$initial_values = array(
			'license_key'                => GFCommon::get_key(),
			'default_theme'              => get_option( 'rg_gforms_default_theme', 'gravity-theme' ),
			'currency'                   => GFCommon::get_currency(),
			'disable_css'                => ! (bool) get_option( 'rg_gforms_disable_css' ),
			'enable_noconflict'          => (bool) get_option( 'gform_enable_noconflict' ),
			'enable_akismet'             => (bool) get_option( 'rg_gforms_enable_akismet', true ),
			'enable_background_updates'  => (bool) get_option( 'gform_enable_background_updates' ),
			'enable_toolbar'             => (bool) get_option( 'gform_enable_toolbar_menu' ),
			'enable_logging'             => (bool) get_option( 'gform_enable_logging' ),
			'enable_async_notifications' => (bool) get_option( 'gform_enable_async_notifications' ),
		);

		$renderer = new Settings(
			array(
				'fields'            => self::plugin_settings_fields(),
				'header'            => array(
					'icon'  => 'fa fa-gear',
					'title' => esc_html__( 'Settings: General', 'gravityforms' ),
				),
				'input_name_prefix' => '_gform_setting',
				'capability'        => 'gravityforms_edit_settings',
				'initial_values'    => $initial_values,
				'save_callback'     => function( $values ) {
					GFCommon::cache_remote_message();
				},
			)
		);

		self::set_settings_renderer( $renderer );

		// Process save callback.
		if ( self::get_settings_renderer()->is_save_postback() ) {
			self::get_settings_renderer()->process_postback();
		}

	}





	// # reCAPTCHA SETTINGS --------------------------------------------------------------------------------------------

	/**
	 * Display reCAPTCHA Settings page.
	 *
	 * @since 2.5
	 */
	private static function recaptcha_page() {

		if ( ! GFCommon::ensure_wp_version() ) {
			return;
		}

		self::maybe_install_recaptcha();

		self::page_header();

		wp_enqueue_style( 'gform_admin' );

		// Initialize Settings renderer.
		if ( ! self::get_settings_renderer() ) {
			self::initialize_recaptcha_settings();
		}

		// Disable the save button on the core reCAPTCHA settings page with the gform_settings_save_button filter.
		add_filter( 'gform_settings_save_button', array( 'GFSettings', 'hide_recaptcha_save_button' ), 10, 0 );
		self::get_settings_renderer()->render();

		self::page_footer();
	}

	/**
	 * Check is the reCAPTCHA Add-On is installed and activated, and handle installation and activation if the installation button was clicked.
	 *
	 * @since 3.0.0
	 */
	public static function maybe_install_recaptcha() {
		if ( ! rgget( 'gform_recaptcha_addon_action' ) ) {
			return;
		}

		check_admin_referer( 'gform_recaptcha_addon_action', 'gform_recaptcha_addon_nonce' );

		// Checking permissions to install and activate the recaptcha addon
		if ( ! self::can_activate_recaptcha() ) {
			return;
		}

		// Install plugin if it is not already installed.
		$plugin_file = self::find_recaptcha_plugin_file();
		if ( empty( $plugin_file ) ) {
			self::install_recaptcha_addon();
			$plugin_file = self::find_recaptcha_plugin_file();
		}

		if ( empty( $plugin_file ) ) {
			// could not install plugin. abort.
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! is_plugin_active( $plugin_file ) ) {
			// Activating addon
			activate_plugin( $plugin_file );

			// Moving recaptcha settings to addon
			self::move_recaptcha_settings();
		}

		// Redirects to the settings page if user has access to that page, otherwise redirect to the plugins page.
		$redirect_url = GFCommon::current_user_can_any( 'gravityforms_recaptcha' ) ? admin_url( 'admin.php?page=gf_settings&subview=gravityformsrecaptcha' ) : admin_url( 'plugins.php' );
		wp_safe_redirect( $redirect_url );
	}

	/**
	 * Move core reCAPTCHA settings to the Add-On to prevent removal of the reCAPTCHA field on form display.
	 *
	 * @since 3.0.0
	 */
	private static function move_recaptcha_settings() {
		$v2_settings = [
			'site_key_v2'     => get_option( 'rg_gforms_captcha_public_key' ),
			'secret_key_v2'   => get_option( 'rg_gforms_captcha_private_key' ),
			'type_v2'         => get_option( 'rg_gforms_captcha_type' ),
			'connection_type' => 'v2',
		];

		// Get existing settings (if any) and merge
		$existing_addon_settings = get_option( 'gravityformsaddon_gravityformsrecaptcha_settings', [] );

		// Write to the option
		update_option( 'gravityformsaddon_gravityformsrecaptcha_settings', array_merge( $existing_addon_settings, $v2_settings ) );
	}

	/**
	 * Installs the reCAPTCHA Add-On.
	 *
	 * @since 3.0.0
	 */
	private static function install_recaptcha_addon() {
		if ( ! self::can_activate_recaptcha() ) {
			return false;
		}

		$key = GFCommon::get_key();

		if ( empty( $key ) ) {
			GFCommon::add_dismissible_message(
				__( 'The Gravity Forms reCAPTCHA Add-On could not be installed because a valid license key is required.', 'gravityforms' ),
				'gform_recaptcha_install_error',
				'warning'
			);
			return false;
		}

		$raw_response = GFCommon::post_to_manager( 'api.php', "op=get_plugin&slug=gravityformsrecaptcha&key={$key}", array() );

		if ( is_wp_error( $raw_response ) || rgar( $raw_response['response'], 'code' ) !== 200 ) {
			return false;
		}

		$plugin_data  = maybe_unserialize( rgar( $raw_response, 'body' ) );
		$download_url = is_array( $plugin_data ) ? rgar( $plugin_data, 'download_url' ) : '';

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
		$result   = $upgrader->install( $download_url );

		if ( ! $result || is_wp_error( $upgrader->result ) ) {
			GFCommon::add_dismissible_message(
				__( 'The Gravity Forms reCAPTCHA Add-On could not be installed. reCAPTCHA support in Gravity Forms is being deprecated, and the reCAPTCHA Add-On will be required in the future.', 'gravityforms' ),
				'gform_recaptcha_install_error',
				'warning'
			);
			return false;
		}

		return true;
	}

	/**
	 * Find the reCAPTCHA plugin file in the list of installed plugins.
	 *
	 * @since 3.0.0
	 *
	 * @return string|false The plugin file path if found, false otherwise.
	 */
	private static function find_recaptcha_plugin_file() {
		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			if ( dirname( $plugin_file ) === 'gravityformsrecaptcha' ) {
				return $plugin_file;
			}
		}
		return false;
	}

	/**
	 * Check if the reCAPTCHA Add-On is active.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if the reCAPTCHA Add-On is active, false otherwise.
	 */
	private static function recaptcha_addon_active() {
		$plugin_file = self::find_recaptcha_plugin_file();
		if ( empty( $plugin_file ) ) {
			return false;
		}
		return is_plugin_active( $plugin_file );
	}

	/**
	 * Check if the reCAPTCHA tab should be displayed.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if the reCAPTCHA tab should be displayed, false otherwise.
	 */
	private static function has_recaptcha_tab() {
		if ( self::recaptcha_addon_active() ) {
			// If the addon is active, display the tab if the user has access to the settings page
			return GFCommon::current_user_can_any( 'gravityforms_recaptcha' );
		}

		// If the addon is not active, display the tab if legacy keys are configured.
		$recaptcha_public_key     = get_option( 'rg_gforms_captcha_public_key' );
		$recaptcha_private_key    = get_option( 'rg_gforms_captcha_private_key' );
		$has_legacy_recaptcha_key = ! rgblank( $recaptcha_public_key ) || ! rgblank( $recaptcha_private_key );
		return $has_legacy_recaptcha_key;
	}

	/**
	 * Get the HTML for the reCAPTCHA Add-On installation/activation button, or empty string if the user does not have the required capabilities.
	 *
	 * @since 3.0.0
	 *
	 * @return string The button HTML, or empty string.
	 */
	private static function get_recaptcha_activate_button() {
		if ( ! self::can_activate_recaptcha() ) {
			return '';
		}

		$recaptcha_addon_url = wp_nonce_url(
			add_query_arg( 'gform_recaptcha_addon_action', '1', admin_url( 'admin.php?page=gf_settings&subview=recaptcha' ) ),
			'gform_recaptcha_addon_action',
			'gform_recaptcha_addon_nonce'
		);

		$label = empty( self::find_recaptcha_plugin_file() ) ? esc_html__( 'Install and activate the Gravity Forms reCAPTCHA Add-On', 'gravityforms' ) : esc_html__( 'Activate the Gravity Forms reCAPTCHA Add-On', 'gravityforms' );

		return sprintf(
			'<div style="margin-top:4px;"><a href="%s" class="button primary">%s</a></div>',
			esc_url( $recaptcha_addon_url ),
			$label,
		);
	}

	/**
	 * Check if the current user has the required capabilities to install or activate the reCAPTCHA Add-On.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if the user has the required capabilities, false otherwise.
	 */
	private static function can_activate_recaptcha() {
		$is_addon_installed = ! empty( self::find_recaptcha_plugin_file() );

		if ( $is_addon_installed ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}
		} else {
			if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get the deprecation notice for the core reCAPTCHA settings page.
	 *
	 * @since 3.0.0
	 *
	 * @return string The deprecation notice HTML.
	 */
	private static function get_recaptcha_deprecation_notice() {
		$capabilities_message = self::can_activate_recaptcha() ? esc_html__( 'please install the Gravity Forms reCAPTCHA Add-On', 'gravityforms' ) : esc_html__( 'please contact your site administrator to install and activate the Gravity Forms reCAPTCHA Add-On', 'gravityforms' );

		return sprintf(
			'<div class="alert warning"><p>%s</p></div>',
			sprintf(
				/* translators: %1$s is replaced with a message about installing the reCAPTCHA Add-On or contacting the site administrator. */
				esc_html__( 'The built-in reCAPTCHA integration in Gravity Forms Core is being deprecated. To continue using and managing reCAPTCHA, %1$s. Your existing keys are shown below for reference but can no longer be edited from this page.', 'gravityforms' ),
				$capabilities_message
			)
		);
	}

	/**
	 * Hides the save button for the core reCAPTCHA settings page.
	 *
	 * @since 3.0.0
	 *
	 * @return string Returns a blank string to remove the save button
	 */
	public static function hide_recaptcha_save_button() {
		return '';
	}

	/**
	 * Initialize reCAPTCHA Settings renderer.
	 *
	 * @since 2.5
	 */
	public static function initialize_recaptcha_settings() {

		require_once( GFCommon::get_base_path() . '/tooltips.php' );

		$renderer = new Settings(
			array(
				'fields'            => array(
					array(
						'id'          => 'recpatcha',
						'title'       => esc_html__( 'reCAPTCHA Settings', 'gravityforms' ),
						'description' => self::get_recaptcha_deprecation_notice(),
						'class'       => 'gform-settings-panel--full',
						'fields'      => array(
							array(
								'name'        => 'public_key',
								'label'       => esc_html__( 'Site Key', 'gravityforms' ),
								'tooltip'     => gform_tooltip( 'settings_recaptcha_public', null, true ),
								'type'        => 'text',
								'readonly'    => 'readonly',
								'description' => '<span class="screen-reader-text">' . esc_html__( 'This field is read-only.', 'gravityforms' ) . '</span>',
							),
							array(
								'name'        => 'private_key',
								'label'       => esc_html__( 'Secret Key', 'gravityforms' ),
								'tooltip'     => gform_tooltip( 'settings_recaptcha_private', null, true ),
								'type'        => 'text',
								'readonly'    => 'readonly',
								'description' => '<span class="screen-reader-text">' . esc_html__( 'This field is read-only.', 'gravityforms' ) . '</span>',
							),
							array(
								'name'          => 'type',
								'label'         => esc_html__( 'Type', 'gravityforms' ),
								'tooltip'       => GFSettings::get_subview() === 'recaptcha' ? gform_tooltip( 'deprecated_settings_recaptcha_type', null, true ) : gform_tooltip( 'settings_recaptcha_type', null, true ),
								'type'          => 'radio',
								'horizontal'    => true,
								'default_value' => 'checkbox',
								'disabled'      => 'disabled',
								'choices'       => array(
									array(
										'label' => esc_html__( 'Checkbox', 'gravityforms' ),
										'value' => 'checkbox',
									),
									array(
										'label' => esc_html__( 'Invisible', 'gravityforms' ),
										'value' => 'invisible',
									),
								),
							),
							array(
								'type' => 'html',
								'name' => 'recaptcha_addon_install',
								'html' => self::get_recaptcha_activate_button(),
							),
						),
					),
				),
				'input_name_prefix' => '_gform_setting',
				'capability'        => 'gravityforms_edit_settings',
				'initial_values'    => array(
					'public_key'  => get_option( 'rg_gforms_captcha_public_key' ),
					'private_key' => get_option( 'rg_gforms_captcha_private_key' ),
					'type'        => get_option( 'rg_gforms_captcha_type' ),
				),
			)
		);

		self::set_settings_renderer( $renderer );
	}

	/**
	 * Renders a reCAPTCHA verification field.
	 *
	 * @since 2.5
	 *
	 * @param array $props Field properties.
	 * @param bool  $echo  Output the field markup directly.
	 *
	 * @return string
	 */
	public static function settings_field_recaptcha_reset( $props = array(), $echo = true ) {

		// Add setup message.
		$html = sprintf(
			'<p id="gforms_checkbox_recaptcha_message" style="margin-bottom:10px;">%s</p>',
			esc_html__( 'Please complete the reCAPTCHA widget to validate your reCAPTCHA keys:', 'gravityforms' )
		);

		// Add reCAPTCHA container, reset input.
		$html .= '<div id="recaptcha"></div>';
		$html .= sprintf( '<input type="hidden" name="%s_%s" />', esc_attr( self::get_settings_renderer()->get_input_name_prefix() ), esc_attr( $props['name'] ) );

		return $html;

	}





	// # SETTINGS RENDERER ---------------------------------------------------------------------------------------------

	/**
	 * Gets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @return false|Settings
	 */
	private static function get_settings_renderer() {

		return self::$_settings_renderer;

	}

	/**
	 * Sets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @param Settings $renderer Settings renderer.
	 *
	 * @return bool|WP_Error
	 */
	private static function set_settings_renderer( $renderer ) {

		// Ensure renderer is an instance of Settings
		if ( ! is_a( $renderer, 'Gravity_Forms\Gravity_Forms\Settings\Settings' ) ) {
			return new WP_Error( 'Renderer must be an instance of Gravity_Forms\Gravity_Forms\Settings\Settings.' );
		}

		self::$_settings_renderer = $renderer;

		return true;

	}

	/**
	 * Handles license upgrades from the Settings page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCommon::get_key()
	 * @uses GFCommon::post_to_manager()
	 *
	 * @return void
	 */
	public static function upgrade_license() {
		$key                = GFCommon::get_key();
		$body               = "key=$key";
		$options            = array( 'method' => 'POST', 'timeout' => 3, 'body' => $body );
		$options['headers'] = array(
			'Content-Type'   => 'application/x-www-form-urlencoded; charset=' . get_option( 'blog_charset' ),
			'Content-Length' => strlen( $body ),
			'User-Agent'     => 'WordPress/' . get_bloginfo( 'version' ),
		);

		$raw_response = GFCommon::post_to_manager( 'api.php', 'op=upgrade_message&key=' . GFCommon::get_key(), $options );

		if ( is_wp_error( $raw_response ) || 200 != $raw_response['response']['code'] ) {
			$message = '';
		} else {
			$message = $raw_response['body'];
		}

		// Validating that message is a valid Gravity Form message. If message is invalid, don't display anything.
		if ( substr( $message, 0, 10 ) != '<!--GFM-->' ) {
			$message = '';
		}

		echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Outputs the settings page header.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses SCRIPT_DEBUG
	 * @uses GFSettings::get_subview()
	 * @uses GFSettings::$addon_pages
	 * @uses GFCommon::get_browser_class()
	 * @uses GFCommon::display_dismissible_message()
	 *
	 * @param string $title   Optional. The page title to be used. Defaults to an empty string.
	 * @param string $message Optional. The message to display in the header. Defaults to empty string.
	 *
	 * @return void
	 */
	public static function page_header( $title = '', $message = '' ) {

		// Print admin styles.
		wp_print_styles( array( 'jquery-ui-styles', 'gform_admin', 'gform_settings' ) );

		$current_tab = self::get_subview();

		// Build left side options, always have GF Settings first and Uninstall last, put add-ons in the middle.
		$setting_tabs = array(
			'10' => array( 'name' => 'settings', 'label' => __( 'Settings', 'gravityforms' ), 'icon' => 'gform-icon--cog' ),
		);

		if ( self::has_recaptcha_tab() ) {
			$setting_tabs['11'] = array( 'name' => 'recaptcha', 'label' => __( 'reCAPTCHA', 'gravityforms' ), 'icon' => 'gform-icon--recaptcha' );
		}

		// Remove an addon from the sidebar if it is uninstalled from the main uninstall page.
		if ( rgpost( 'uninstall_addon' ) ) {
			check_admin_referer( 'uninstall', 'gf_addon_uninstall' );
			foreach ( self::$addon_pages as $key => $addon ) {
				if ( rgpost( 'addon' ) == $addon['tab_label'] ) {
					unset( self::$addon_pages[ $key ] );
					break;
				}
			}

			// Set the uninstalled addon variable to display a success message.
			self::$uninstalled_addon = rgpost( 'addon' );
		}

		if ( ! empty( self::$addon_pages ) ) {

			$sorted_addons = self::$addon_pages;
			usort(
				$sorted_addons,
				function ( $a, $b ) {
					return strnatcasecmp( $a['tab_label'], $b['tab_label'] );
				}
			);

			// Add add-ons to menu
			foreach ( $sorted_addons as $sorted_addon ) {
				$setting_tabs[] = array(
					'name'  => urlencode( $sorted_addon['name'] ),
					'label' => esc_html( $sorted_addon['tab_label'] ),
					'title' => esc_html( rgar( $sorted_addon, 'title' ) ),
					'icon'  => rgar( $sorted_addon, 'icon', 'gform-icon--cog' ),
				);
			}
		}

		// Prevent Uninstall tab from being added for users that don't have gravityforms_uninstall capability.
		if ( GFCommon::current_user_can_uninstall() ) {
			$setting_tabs[] = array(
				'name'  => 'uninstall',
				'label' => __( 'Uninstall', 'gravityforms' ),
				'icon'  => 'gform-icon--trash',
			);
		}

		/**
		 * Filters the Settings menu tabs.
		 *
		 * @since Unknown
		 *
		 * @param array $setting_tabs The settings tab names and labels.
		 */
		$setting_tabs = apply_filters( 'gform_settings_menu', $setting_tabs );
		ksort( $setting_tabs, SORT_NUMERIC );

		// Kind of boring having to pass the title, optionally get it from the settings tab.
		if ( ! $title ) {
			foreach ( $setting_tabs as $tab ) {
				if ( $tab['name'] == urlencode( $current_tab ) ) {
					$title = ! empty( $tab['title'] ) ? $tab['title'] : $tab['label'];
				}
			}
		}

		?>

		<div class="<?php echo esc_attr( GFCommon::get_browser_class() ); ?>">

			<?php
			self::page_header_bar();
			echo GFCommon::get_remote_message(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			GFCommon::notices_section();
			?>

			<?php if ( $message ) { ?>
				<div id="message" class="updated"><p><?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p></div>
			<?php } ?>

			<div class="gform-settings__wrapper">

				<?php GFCommon::display_dismissible_message(); ?>
				<?php
				$page_title = get_admin_page_title();
				$page_title = str_replace( '- Gravity Forms', 'page', $page_title );
				?>

				<nav class="gform-settings__navigation" aria-label="<?php echo esc_attr( esc_html__( $page_title, 'gravityforms' ) ); ?>">
					<?php
					foreach ( $setting_tabs as $tab ) {

						// Prepare tab URL.
						$url  = add_query_arg( array( 'subview' => $tab['name'] ), admin_url( 'admin.php?page=gf_settings' ) );

						// Get tab icon.
						$icon_markup = GFCommon::get_icon_markup( $tab, 'gform-icon--cog' );

						printf(
							'<a href="%s" %s><span class="icon">%s</span> <span class="label">%s</span></a>',
							esc_url( $url ),
							$current_tab === $tab['name'] ? ' class="active"' : '',
							$icon_markup, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							esc_html( $tab['label'] )
						);
					}
					?>
				</nav>

				<div class="gform-settings__content" id="tab_<?php echo esc_attr( $current_tab ); ?>">

		<?php
	}

	/**
	 * Outputs the Settings header bar.
	 *
	 * @since 2.5
	 */
	public static function page_header_bar() {
		?>

		<div class="wrap <?php echo esc_attr( GFCommon::get_browser_class() ); ?>">

		<?php
		GFCommon::gf_header();

	}

	/**
	 * Outputs the Settings page footer.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return void
	 */
	public static function page_footer() {
		?>
				</div>
				<!-- / gform-settings__content -->
			</div>
			<!-- / gform-settings__wrapper -->

		</div> <!-- / wrap -->

		<?php
	}

    /**
     * Check if the reCAPTCHA Add-On is active.
     *
     * @since 3.0.0
     *
     * @return bool
     */
    private static function is_recaptcha_addon_active() {
        $gf_addons = GFAddOn::get_registered_addons();
        foreach ( $gf_addons as $addon ) {
            if ( $addon === 'Gravity_Forms\Gravity_Forms_RECAPTCHA\GF_RECAPTCHA' ) {
                return true;
            }
        }
        return false;
    }

	/**
	 * Gets the Settings page subview based on the query string.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return string The subview.
	 */
	public static function get_subview() {

		// Default to subview, if no subview provided support.
		$subview = rgget( 'subview' ) ? rgget( 'subview' ) : rgget( 'addon' );

		if ( ! $subview ) {
			$subview = 'settings';
		}

		return $subview;
	}

	/**
	 * Handles the enabling/disabling of the Akismet Integration setting
	 *
	 * Called from GFSettings::gravityforms_settings_page
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFSettings::gravityforms_settings_page()
	 *
	 * @return string $akismet_setting '1' if turning on, '2' if turning off.
	 */
	public static function get_posted_akismet_setting() {

		$akismet_setting = rgpost( 'gforms_enable_akismet' );

		if ( $akismet_setting ) {
			$akismet_setting = '1';
		} elseif ( $akismet_setting === false ) {
			$akismet_setting = false;
		} else {
			$akismet_setting = '0';
		}

		return $akismet_setting;
	}

	/**
	 * Enable the GFLogging class.
	 *
	 * @since 2.4.4.2
	 *
	 * @return bool
	 */
	public static function enable_logging() {

		// Update option.
		$enabled = update_option( 'gform_enable_logging', true );

		// Prepare settings page, enable logging.
		if ( function_exists( 'gf_logging' ) ) {

			// Add settings page.
			self::add_settings_page(
				array(
					'name'      => gf_logging()->get_slug(),
					'tab_label' => gf_logging()->get_short_title(),
					'title'     => gf_logging()->plugin_settings_title(),
					'handler'   => array( gf_logging(), 'plugin_settings_page' ),
					'icon'      => gf_logging()->get_menu_icon(),
				),
				null,
				null
			);

			// Enabling all loggers by default.
			gf_logging()->enable_all_loggers();

		}

		return $enabled;

	}

	/**
	 * Disable the GFLogging class.
	 *
	 * @since 2.4.4.2
	 *
	 * @return bool
	 */
	public static function disable_logging() {

		// Update option.
		$disabled = update_option( 'gform_enable_logging', false );

		// Remove settings page, log files.
		if ( function_exists( 'gf_logging' ) ) {
			unset( self::$addon_pages[ gf_logging()->get_slug() ] );
			gf_logging()->delete_log_files();
		}

		return $disabled;

	}

}
