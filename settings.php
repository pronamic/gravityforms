<?php

use Gravity_Forms\Gravity_Forms\Settings\Settings;
use \Gravity_Forms\Gravity_Forms\License;

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
	 *
	 * @uses GFSettings::$addon_pages
	 *
	 * @param string|array $name      The settings page slug.
	 * @param string|array $handler   The callback function to run for this settings page.
	 * @param string       $icon_path The path to the icon for the settings tab. @deprecated
	 */
	public static function add_settings_page( $name, $handler, $icon_path = '' ) {

		if ( ! empty( $icon_path ) ) {
			_deprecated_argument( __METHOD__, '2.5', '$icon_path has been deprecated in favor of passing a dashicons string via $name[\'icon\']' );
		}

		$title = '';
		$icon  = 'gform-icon--cog';

		// if name is an array, assume that an array of args is passed
		if ( is_array( $name ) ) {

			/**
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

			// Removing background tasks.
			$processors = array(
				GFForms::$background_upgrader,
				gf_feed_processor()
			);

			/** @var GF_Background_Process $processor The background task processor. */
			foreach ( $processors as $processor ) {
				$processor->clear_scheduled_events();
				$processor->clear_queue( true );
				$processor->unlock_process();
			}

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
			delete_option( 'gform_enable_logging' );
			delete_option( 'gform_pending_installation' );
			delete_option( 'gform_version_info' );
			delete_option( 'gform_enable_noconflict' );
			delete_option( 'gform_enable_background_updates' );
			delete_option( 'gform_sticky_admin_messages' );
			delete_option( 'gform_upgrade_status' );
			delete_option( 'gform_custom_choices' );
			delete_option( 'gform_recaptcha_keys_status' );
			delete_option( 'gform_upload_page_slug' );

			delete_option( 'gravityformsaddon_gravityformswebapi_version' );
			delete_option( 'gravityformsaddon_gravityformswebapi_settings' );

			// Removes license key
			GFFormsModel::save_key( '' );

			// Removing gravity forms upload folder
			GFCommon::delete_directory( RGFormsModel::get_upload_root() );

			// Delete Logging settings and logging files
			gf_logging()->delete_settings();
			gf_logging()->delete_log_files();

			// Deactivating plugin
			$plugin = 'gravityforms/gravityforms.php';
			deactivate_plugins( $plugin );
			update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );

			?>
			<div class="updated fade gf-notice notice-success"><?php echo sprintf( esc_html__( 'Gravity Forms has been successfully uninstalled. It can be re-activated from the %splugins page%s.', 'gravityforms' ), "<a href='plugins.php'>", '</a>' ) ?></div>
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

							$uninstall_button = sprintf(
								'<input type="submit" name="uninstall" class="button red" value="%1$s" onclick="return confirm( \'%2$s\' );" onkeypress="return confirm( \'%2$s\' );" />',
								esc_attr__( 'Uninstall Gravity Forms', 'gravityforms' ),
								esc_js( __( "Warning! ALL Gravity Forms data, including form entries will be deleted. This cannot be undone. 'OK' to delete, 'Cancel' to stop", 'gravityforms' ) )
							);

							/**
							 * Allows for the modification of the Gravity Forms uninstall button.
							 *
							 * @since Unknown
							 *
							 * @param string $uninstall_button The HTML of the uninstall button.
							 */
							echo apply_filters( 'gform_uninstall_button', $uninstall_button );

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
			<div class="alert success"><?php echo sprintf( esc_html__( '%s uninstalled. It can be re-activated from the %splugins page%s.', 'gravityforms' ), self::$uninstalled_addon ,"<a href='plugins.php'>", '</a>' ) ?></div>
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
	 * Prepare Plugin Settings fields.
	 *
	 * @since 2.5
	 *
	 * @return array
	 */
	private static function plugin_settings_fields() {

		// Prepare currency options.
		$currency_options = array(
			array(
				'label' => esc_html__( 'Select a Currency', 'gravityforms' ),
				'value' => '',
			),
		);
		foreach ( RGCurrency::get_currencies() as $code => $currency ) {
			$currency_options[] = array( 'label' => esc_html( $currency['name'] ), 'value' => $code );
		}

		$fields = array(
			'license_key'         => array(
				'title'       => esc_html__( 'Support License Key', 'gravityforms' ),
				'class'       => 'gform-settings-panel--full',
				'description' => esc_html__( 'A valid license key is required for access to automatic plugin upgrades and product support.', 'gravityforms' ),
				'fields'      => array(
					array(
						'name'                => 'license_key',
						'label'               => esc_html__( 'Paste Your License Key Here', 'gravityforms' ),
						'type'                => 'text',
						'input_type'          => 'password',
						'callback'            => array( 'GFSettings', 'license_key_render_callback' ),
						'class'               => 'gform-admin-input',
						'validation_callback' => array( 'GFSettings', 'license_key_validation_callback' ),
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
								License\GF_License_Statuses::get_message_for_code( $license_info->get_status() )
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
							if ( isset( $_POST['_gform_setting_license_key'] ) ) {
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
				'class'       => 'gform-settings-panel--no-padding gform-settings-panel--license-details',
				'fields' => array(
					array(
						'name' => 'license_key_details',
						'type' => 'html',
						'html' => array( 'GFSettings', 'license_key_details_callback' ),
					),
				),
			),
			'css'                 => array(
				'id'          => 'section_default_css',
				'title'       => esc_html__( 'Output Default CSS', 'gravityforms' ),
				'description' => esc_html__( 'Enable this option to output the default form CSS. Disable it if you plan to create your own CSS in a child theme.', 'gravityforms' ),
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
						'choices'       => $currency_options,
						'enhanced_ui'   => true,
						'after_select'  => self::currency_message_callback(),
						'save_callback' => function( $field, $value ) {
							update_option( 'rg_gforms_currency', $value );

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
			'toolbar'             => array(
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
			),
			'background_updates'  => array(
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
			),
			'no_conflict_mode'    => array(
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
			),
			'akismet'             => array(
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
			),
			'html5'               => array(
				'id'            => 'section_enable_html5',
				'title'         => esc_html__( 'Output HTML5', 'gravityforms' ),
				'description'   => esc_html__( 'Gravity Forms outputs HTML5 form fields by default. Disable this option if you would like to prevent the plugin from outputting HTML5 form fields.', 'gravityforms' ),
				'class'         => 'gform-settings-panel--half',
				'default_value' => true,
				'fields'        => array(
					array(
						'name'          => 'enable_html5',
						'type'          => 'toggle',
						'toggle_label'  => esc_html__( 'Output HTML5', 'gravityforms' ),
						'save_callback' => function( $field, $value ) {
							update_option( 'rg_gforms_enable_html5', (bool) $value ? 1 : 0 );

							return $value;
						},
					),
				),
			),
		);

		$display_license_details = true;

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
		$key          = GFCommon::get_key();
		$empty_string = '<div class="gform-p-16">' . __( 'Please enter a valid license key to see details.', 'gravityforms' ) . '</div>';

		if ( empty( $key ) ) {
			return $empty_string;
		}

		/**
		 * @var License\GF_License_API_Connector $license_connector
		 */
		$license_connector = GFForms::get_service_container()->get( License\GF_License_Service_Provider::LICENSE_API_CONNECTOR );
		$license_info      = $license_connector->check_license( $key );

		if ( ! $license_info->can_be_used() ) {
			return $empty_string;
		}

		ob_start();
		?>
		<table class="gform-table gform-table--responsive gform-table--no-outer-border gform-table--license-ui">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'License Type', 'gravityforms' ); ?></th>
					<th scope="col"><?php esc_html_e( 'License Status', 'gravityforms' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Purchase Date', 'gravityforms' ); ?></th>
					<th scope="col"><?php esc_html_e( 'License Activations', 'gravityforms' ); ?></th>
					<th scope="col"><?php esc_html_e( $license_info->renewal_text() ); ?></th>
					<th scope="col"><?php esc_html_e( 'Days Left', 'gravityforms' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td data-header="<?php esc_html_e( 'License Type', 'gravityforms' ); ?>">
						<p><?php echo trim( str_replace( 'Gravity Forms', '', $license_info->get_data_value( 'product_name' ) ) ); ?></p>
					</td>
					<td data-header="<?php esc_html_e( 'License Status', 'gravityforms' ); ?>">
						<p>
							<?php
								$status_class = $license_info->display_as_valid() ? 'active' : 'error';
								$status_label = $license_info->get_display_status();
							?>
							<span class="gform-status-indicator gform-status--<?php echo $status_class; ?> gform-status--static gform-status--no-icon">
								<span class="gform-status-indicator-status"><?php echo esc_html( $status_label ); ?></span>
							</span>
						</p>
					</td>
					<td data-header="<?php _e( 'Purchase Date', 'gravityforms' ); ?>">
						<p><?php echo gmdate( 'M d, Y', strtotime( $license_info->get_data_value('date_created' ) ) ); ?></p>
					</td>
					<td data-header="<?php _e( 'License Activations', 'gravityforms' ); ?>">
						<p>
							<?php $activation_class = $license_info->max_seats_exceeded() ? 'gform-c-error-text' : ''; ?>
							<span class="<?php echo esc_attr( $activation_class ); ?>">
								<?php printf( '%s of %s', $license_info->get_data_value('active_sites' ), $license_info->get_data_value( 'max_sites' ) ); ?>
							</span>
						</p>
					</td>
					<td data-header="<?php esc_html_e( $license_info->renewal_text() ); ?>">
						<p><?php echo esc_html( $license_info->renewal_date() ); ?></p>
					</td>
					<td data-header="<?php _e( 'Days Left', 'gravityforms' ); ?>">
						<p>
							<?php $cta = $license_info->get_cta(); ?>
							<?php if ( $license_info->cta_type() === 'button' ): ?>
								<a
									class="gform-button gform-button--white gform-button--icon-leading gform-button--size-xs"
									href="<?php echo esc_url( $cta['link'] ); ?>"
									target="_blank"
									rel="noopener"
								>
									<i class="gform-button__icon gform-icon gform-icon--<?php echo esc_attr( $cta['class'] ); ?>"></i>
									<?php echo esc_html( $cta['label'] ); ?>
								</a>
							<?php else: ?>
								<?php echo esc_html( $cta ); ?>
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
	 */
	public static function initialize_plugin_settings() {

		require_once( GFCommon::get_base_path() . '/tooltips.php' );

		$initial_values = array(
			'license_key'               => GFCommon::get_key(),
			'currency'                  => GFCommon::get_currency(),
			'disable_css'               => ! (bool) get_option( 'rg_gforms_disable_css' ),
			'enable_html5'              => (bool) get_option( 'rg_gforms_enable_html5', false ),
			'enable_noconflict'         => (bool) get_option( 'gform_enable_noconflict' ),
			'enable_akismet'            => (bool) get_option( 'rg_gforms_enable_akismet', true ),
			'enable_background_updates' => (bool) get_option( 'gform_enable_background_updates' ),
			'enable_toolbar'            => (bool) get_option( 'gform_enable_toolbar_menu' ),
			'enable_logging'            => (bool) get_option( 'gform_enable_logging' ),
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

		self::page_header();

		wp_enqueue_style( 'gform_admin' );

		// Initialize Settings renderer.
		if ( ! self::get_settings_renderer() ) {
			self::initialize_recaptcha_settings();
		}

		self::get_settings_renderer()->render();

		self::page_footer();


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
						'description' => sprintf(
							'%s <strong>%s</strong> %s <a href="http://www.google.com/recaptcha/" target="_blank">%s</a>',
							esc_html__( 'Gravity Forms integrates with reCAPTCHA, a free CAPTCHA service that uses an advanced risk analysis engine and adaptive challenges to keep automated software from engaging in abusive activities on your site. ', 'gravityforms' ),
							esc_html__( 'Please note, only v2 keys are supported and checkbox keys are not compatible with invisible reCAPTCHA.', 'gravityforms' ),
							esc_html__( 'These settings are required only if you decide to use the reCAPTCHA field.', 'gravityforms' ),
							esc_html__( 'Read more about reCAPTCHA.', 'gravityforms' )
						),
						'class'       => 'gform-settings-panel--full',
						'fields'      => array(
							array(
								'name'              => 'public_key',
								'label'             => esc_html__( 'Site Key', 'gravityforms' ),
								'tooltip'           => gform_tooltip( 'settings_recaptcha_public', null, true ),
								'type'              => 'text',
								'feedback_callback' => function( $value ) {
									$key_status = get_option( 'gform_recaptcha_keys_status', null );
									return is_null( $key_status ) ? ( rgblank( $value ) ? null : false ) : (bool) $key_status;
								},
							),
							array(
								'name'              => 'private_key',
								'label'             => esc_html__( 'Secret Key', 'gravityforms' ),
								'tooltip'           => gform_tooltip( 'settings_recaptcha_private', null, true ),
								'type'              => 'text',
								'feedback_callback' => function( $value ) {
									$key_status = get_option( 'gform_recaptcha_keys_status', null );
									return is_null( $key_status ) ? ( rgblank( $value ) ? null : false ) : (bool) $key_status;
								},
							),
							array(
								'name'          => 'type',
								'label'         => esc_html__( 'Type', 'gravityforms' ),
								'tooltip'       => gform_tooltip( 'settings_recaptcha_type', null, true ),
								'type'          => 'radio',
								'horizontal'    => true,
								'default_value' => 'checkbox',
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
								'name'     => 'reset',
								'label'    => esc_html__( 'Validate Keys', 'gravityforms' ),
								'type'     => 'recaptcha_reset',
								'callback' => array( 'GFSettings', 'settings_field_recaptcha_reset' ),
								'hidden'   => true,
								'validation_callback' => function( $field, $value ) {

									// If reCAPTCHA key is empty, exit.
									if ( rgblank( $value ) ) {
										return;
									}

									$values = GFSettings::get_settings_renderer()->get_posted_values();

									// Get public, private keys, API response.
									$public_key  = rgar( $values, 'public_key' );
									$private_key = rgar( $values, 'private_key' );
									$response    = rgpost( 'g-recaptcha-response' );

									// If keys and response are provided, verify and save.
									if ( $public_key && $private_key && $response ) {

										// Log public, private keys, API response.
										GFCommon::log_debug( __METHOD__ . '(): reCAPTCHA Site Key:' . print_r( $public_key, true ) );
										GFCommon::log_debug( __METHOD__ . '(): reCAPTCHA Secret Key:' . print_r( $private_key, true ) );
										GFCommon::log_debug( __METHOD__ . '(): reCAPTCHA Response:' . print_r( $response, true ) );

										// Verify response.
										$recaptcha          = new GF_Field_CAPTCHA();
										$recaptcha_response = $recaptcha->verify_recaptcha_response( $response, $private_key );

										// Log verification response.
										GFCommon::log_debug( __METHOD__ . '(): reCAPTCHA verification response:' . print_r( $recaptcha_response, true ) );

										// If response is false, return validation error.
										if ( $recaptcha_response === false ) {
											$field->set_error( __( 'reCAPTCHA keys are invalid.', 'gravityforms' ) );
										}

										// Save status.
										update_option( 'gform_recaptcha_keys_status', $recaptcha_response );

									} else {

										// Delete existing status.
										delete_option( 'gform_recaptcha_keys_status' );

									}

								}
							),
						),
					),
				),
				'save_button'       => array(
					'messages' => array(
						'save'  => esc_html__( 'Settings updated.', 'gravityforms' ),
						'error' => __( 'reCAPTCHA keys are invalid.', 'gravityforms' ),
					),
				),
				'input_name_prefix' => '_gform_setting',
				'capability'        => 'gravityforms_edit_settings',
				'initial_values'    => array(
					'public_key'  => get_option( 'rg_gforms_captcha_public_key' ),
					'private_key' => get_option( 'rg_gforms_captcha_private_key' ),
					'type'        => get_option( 'rg_gforms_captcha_type' ),
				),
				'save_callback'     => function( $values ) {

					// reCAPTCHA.
					update_option( 'rg_gforms_captcha_public_key', rgar( $values, 'public_key' ) );
					update_option( 'rg_gforms_captcha_private_key', rgar( $values, 'private_key' ) );
					update_option( 'rg_gforms_captcha_type', rgar( $values, 'type' ) );

				},
				'after_fields'      => function() {
					echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
					printf( '<script type="text/javascript" src="%s"></script>', GFCommon::get_base_url() . '/js/plugin_settings.js' );
				},
			)
		);

		self::set_settings_renderer( $renderer );

		// Process save callback.
		if ( self::get_settings_renderer()->is_save_postback() ) {
			self::get_settings_renderer()->process_postback();
		}


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

		echo $message;

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
			'11' => array( 'name' => 'recaptcha', 'label' => __( 'reCAPTCHA', 'gravityforms' ), 'icon' => 'gform-icon--recaptcha' ),
		);

		// Remove an addon from the sidebar if it is uninstalled from the main uninstall page.
		if ( rgpost( 'uninstall_addon' ) ) {
			check_admin_referer( 'uninstall', 'gf_addon_uninstall' );
			foreach ( self::$addon_pages as $key => $addon ) {
				if ( $_POST['addon'] == $addon['tab_label'] ) {
					unset( self::$addon_pages[ $key ] );
					break;
				}
			}

			// Set the uninstalled addon variable to display a success message.
			self::$uninstalled_addon = $_POST['addon'];
		}

		if ( ! empty( self::$addon_pages ) ) {

			$sorted_addons = self::$addon_pages;
			asort( $sorted_addons );

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
			$setting_tabs[] = array( 'name' => 'uninstall', 'label' => __( 'Uninstall', 'gravityforms' ), 'icon' => 'gform-icon--trash' );
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

		// Kind of boring having to pass the title, optionally get it from the settings tab
		if ( ! $title ) {
			foreach ( $setting_tabs as $tab ) {
				if ( $tab['name'] == urlencode( $current_tab ) ) {
					$title = ! empty( $tab['title'] ) ? $tab['title'] : $tab['label'];
				}
			}
		}

		?>

		<div class="<?php echo GFCommon::get_browser_class() ?>">

			<?php
			self::page_header_bar();
			echo GFCommon::get_remote_message();
			GFCommon::notices_section();
			?>

			<?php if ( $message ) { ?>
				<div id="message" class="updated"><p><?php echo $message; ?></p></div>
			<?php } ?>

			<div class="gform-settings__wrapper">

				<?php GFCommon::display_dismissible_message(); ?>

				<nav class="gform-settings__navigation">
					<?php
					foreach ( $setting_tabs as $tab ) {

						// Prepare tab URL.
						$url  = add_query_arg( array( 'subview' => $tab['name'] ), admin_url( 'admin.php?page=gf_settings' ) );

						// Get tab icon.
						$icon_markup = GFCommon::get_icon_markup( $tab, 'gform-icon--cog' );

						printf(
							'<a href="%s"%s><span class="icon">%s</span> <span class="label">%s</span></a>',
							esc_url( $url ),
							$current_tab === $tab['name'] ? ' class="active"' : '',
							$icon_markup,
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

		<div class="wrap <?php echo GFCommon::get_browser_class(); ?>">

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
	 * Gets the Settings page subview based on the query string.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return string The subview.
	 */
	public static function get_subview() {

		// Default to subview, if no subview provided support
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

		if( $akismet_setting ) {
			$akismet_setting = '1';
		} elseif( $akismet_setting === false ) {
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

			// Enabling all loggers by default
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
