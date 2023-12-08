<?php

namespace Gravity_Forms\Gravity_Forms\Setup_Wizard\Endpoints;

/**
 * AJAX Endpoint for saving preferences.
 *
 * @since   2.7
 *
 * @package Gravity_Forms\Gravity_Forms\Setup_Wizard\Endpoints
 */
class GF_Setup_Wizard_Endpoint_Save_Prefs {

	// Strings.
	const ACTION_NAME = 'gf_setup_wizard_save_prefs';

	// Parameters.
	const PARAM_ACTIVE_STEP        = 'activeStep';
	const PARAM_AUTO_UPDATE        = 'autoUpdate';
	const PARAM_CURRENCY           = 'currency';
	const PARAM_DATA_COLLECTION    = 'dataCollection';
	const PARAM_HIDE_LICENSE       = 'hideLicense';
	const PARAM_IS_OPEN            = 'isOpen';
	const PARAM_ORGANIZATION       = 'organization';
	const PARAM_EMAIL              = 'email';
	const PARAM_EMAIL_CONSENT      = 'emailConsent';
	const PARAM_FORM_TYPES         = 'formTypes';
	const PARAM_FORM_TYPES_OTHER   = 'formTypesOther';
	const PARAM_ORGANIZATION_OTHER = 'organizationOther';
	const PARAM_SERVICES           = 'services';
	const PARAM_SERVICES_OTHER     = 'servicesOther';


	/**
	 * @var \Gravity_Api $api
	 */
	private $api;

	public function __construct( \Gravity_Api $api ) {
		$this->api = $api;
	}

	/**
	 * List of telemetry values to save to DB.
	 *
	 * @return string[]
	 */
	private function telemetry_options_map() {
		// Add any additional telemetry items we want to actually save here.
		return array(
			self::PARAM_AUTO_UPDATE,
			self::PARAM_CURRENCY,
			self::PARAM_DATA_COLLECTION,
			self::PARAM_EMAIL,
			self::PARAM_EMAIL_CONSENT,
			self::PARAM_FORM_TYPES,
			self::PARAM_FORM_TYPES_OTHER,
			self::PARAM_HIDE_LICENSE,
			self::PARAM_ORGANIZATION,
			self::PARAM_ORGANIZATION_OTHER,
			self::PARAM_SERVICES,
			self::PARAM_SERVICES_OTHER,
		);
	}

	/**
	 * Remove any setup data, including license and installation values. Currently called when
	 * plugin is uninstalled.
	 *
	 * @since 2.7
	 */
	public function remove_setup_data() {
		foreach( $this->telemetry_options_map() as $key ) {
			$option_name = $this->get_option_name( $key );
			delete_option( $option_name );
		}
	}

	/**
	 * Map new settings names to legacy settings names.
	 *
	 * @since 2.7
	 *
	 * @return string[]
	 */
	private function legacy_options_map() {
		return array(
			self::PARAM_AUTO_UPDATE => 'gform_enable_background_updates',
		);
	}

	/**
	 * Handle the AJAX request.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function handle() {
		check_ajax_referer( self::ACTION_NAME );

		// Loop through each actual telemetry value we want to save and store it if present.
		foreach ( $this->telemetry_options_map() as $name ) {
			if ( ! isset( $_POST[ $name ] ) ) {
				continue;
			}

			$value = $this->sanitize_posted_value( rgpost( $name ) );
			$option_name = $this->get_option_name( $name );

			update_option( $option_name, $value );
		}

		// Update legacy defaults that we no longer control via the wizard.
		$this->handle_legacy();

		// Cleanup.
		$this->cleanup();

		// Save the license key (if set).
		$license = rgpost( 'licenseKey' );

		if ( $license ) {
			\GFFormsModel::update_license_key( md5( $license ) );
		}

		if ( ! empty( rgpost( self::PARAM_EMAIL ) && ( ! empty( rgpost( self::PARAM_EMAIL_CONSENT ) ) && rgpost( self::PARAM_EMAIL_CONSENT ) != 'false' ) ) ) {
			$sent = $this->api->send_email_to_hubspot( rgpost( self::PARAM_EMAIL ) );

			if ( is_wp_error( $sent ) ) {
				\GFCommon::log_debug( __METHOD__ . '(): error sending setup wizard to hubspot. ' . print_r( $sent, true ) );
			}
		}

		wp_send_json_success( __( 'Preferences updated.', 'gravityforms' ) );
	}

	/**
	 * Gets a saved preference by name.
	 *
	 * @since 2.7
	 *
	 * @param string $param_name The parameter name.
	 *
	 * @return string|bool Returns the value of the specified preference.
	 */
	public function get_value( $param_name ) {

		$option_name = $this->get_option_name( $param_name );
		$option      = get_option( $option_name );

		switch ( $param_name ) {
			case self::PARAM_AUTO_UPDATE:
			case self::PARAM_DATA_COLLECTION:
			case self::PARAM_HIDE_LICENSE:
				$option = (bool) $option ? 1 : 0;
				break;

			case self::PARAM_SERVICES:
			case self::PARAM_FORM_TYPES:
				$option = \GFCommon::is_json( $option ) ? implode( ',', \GFCommon::json_decode( $option ) ) : '';
				break;
		}

		return $option;
	}

	/**
	 * Gets the option name that stores the specified parameter.
	 *
	 * @since 2.7
	 *
	 * @param string $param_name The parameter name.
	 *
	 * @return string Returns the name of the option associated with the specified parameter name.
	 */
	private function get_option_name( $param_name ) {

		// Use legacy names if available.
		$legacy_options = $this->legacy_options_map();
		return isset( $legacy_options[ $param_name ] ) ? $legacy_options[ $param_name ] : sprintf( 'rg_gforms_%s', $param_name );
	}

	/**
	 * Values can come from the browser in a variety of types, sanitize them for consistency.
	 *
	 * @since 2.7
	 *
	 * @param $value
	 *
	 * @return bool|mixed|string
	 */
	private function sanitize_posted_value( $value ) {
		if ( $value === 'true' ) {
			$value = true;
		} elseif ( $value === 'false' ) {
			$value = false;
		}

		if ( is_array( $value ) ) {
			$value = json_encode( $value );
		}

		return $value;
	}

	/**
	 * We don't present some legacy options in the Wizard any longer, so we need to manually set the values.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	private function handle_legacy() {
		update_option( 'gform_enable_toolbar_menu', true );
		update_option( 'rg_gforms_enable_akismet', true );
		update_option( 'gform_enable_noconflict', false );
	}

	/**
	 * After the wizard is complete, clean up values.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	private function cleanup() {
		// Tell the system we no longer need to display the wizard.
		delete_option( 'gform_pending_installation' );
		delete_option( 'rg_gforms_message' );

		// Save the version in the DB
		update_option( 'rg_form_version', \GFForms::$version, false );
	}

}
