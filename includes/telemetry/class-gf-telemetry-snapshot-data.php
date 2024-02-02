<?php

namespace Gravity_Forms\Gravity_Forms\Telemetry;

use GFCommon;
use GFForms;
use GFFormsModel;
use \Gravity_Forms\Gravity_Forms\Setup_Wizard\GF_Setup_Wizard_Service_Provider;
use \Gravity_Forms\Gravity_Forms\Setup_Wizard\Endpoints\GF_Setup_Wizard_Endpoint_Save_Prefs;

class GF_Telemetry_Snapshot_Data extends GF_Telemetry_Data {

	/**
	 * @var string $key Identifier for this data object.
	 */
	public $key = 'snapshot';

	public function __construct() {
		parent::__construct();
		/**
		 * Array of callback functions returning an array of data to be included in the telemetry snapshot.
		 *
		 * @since 2.8
		 */
		$callbacks = array(
			array( $this, 'get_site_basic_info' ),
			array( $this, 'get_gf_settings' ),
			array( $this, 'get_legacy_forms' ),
		);

		// Merges the default callbacks with any additional callbacks added via the gform_telemetry_snapshot_data filter. Default callbacks are added last so they can't be overridden.
		$callbacks = array_merge( $this->get_callbacks(), $callbacks );

		foreach ( $callbacks as $callback ) {
			if ( is_callable( $callback ) ) {
				$this->data = array_merge( $this->data, call_user_func( $callback ) );
			}
		}
	}

	/**
	 * Get additional callbacks that return data to be included in the telemetry snapshot.
	 *
	 * @since 2.8
	 *
	 * @return array
	 */
	public function get_callbacks() {
		/**
		 * Filters the non-default data to be included in the telemetry snapshot.
		 *
		 * @since 2.8
		 *
		 * @param array $new_callbacks An array of callbacks returning an array of data to be included in the telemetry snapshot. Default empty array.
		 */
		return apply_filters( 'gform_telemetry_snapshot_data', array() );
	}

	/**
	 * Get basic site info for telemetry.
	 *
	 * @since 2.8
	 *
	 * @return array
	 */
	public function get_site_basic_info() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_list = get_plugins();
		$plugins     = array();

		$active_plugins = get_option( 'active_plugins' );

		foreach ( $plugin_list as $key => $plugin ) {
			$is_active = in_array( $key, $active_plugins );

			$slug = substr( $key, 0, strpos( $key, '/' ) );
			if ( empty( $slug ) ) {
				$slug = str_replace( '.php', '', $key );
			}

			$plugins[] = array(
				'name'      => str_replace( 'phpinfo()', 'PHP Info', $plugin['Name'] ),
				'slug'      => $slug,
				'version'   => $plugin['Version'],
				'is_active' => $is_active,
			);
		}

		$theme            = wp_get_theme();
		$theme_name       = $theme->get( 'Name' );
		$theme_uri        = $theme->get( 'ThemeURI' );
		$theme_version    = $theme->get( 'Version' );
		$theme_author     = $theme->get( 'Author' );
		$theme_author_uri = $theme->get( 'AuthorURI' );

		$form_counts    = GFFormsModel::get_form_count();
		$active_count   = $form_counts['active'];
		$inactive_count = $form_counts['inactive'];
		$fc             = abs( $active_count ) + abs( $inactive_count );
		$entry_count    = GFFormsModel::get_entry_count_all_forms( 'active' );
		$meta_counts    = GFFormsModel::get_entry_meta_counts();
		$im             = is_multisite();
		$lang           = get_locale();
		$db             = GFCommon::get_dbms_type();

		$post = array(
			'key'                 => GFCommon::get_key(),
			'wp_version'          => get_bloginfo( 'version' ),
			'php_version'         => phpversion(),
			'mysql_version'       => GFCommon::get_db_version(),
			'plugins'             => $plugins,
			'theme_name'          => $theme_name,
			'theme_uri'           => $theme_uri,
			'theme_version'       => $theme_version,
			'theme_author'        => $theme_author,
			'theme_author_uri'    => $theme_author_uri,
			'is_multisite'        => $im,
			'total_forms'         => $fc,
			'total_entries'       => $entry_count,
			'emails_sent'         => GFCommon::get_emails_sent(),
			'api_calls'           => GFCommon::get_api_calls(),
			'entry_meta_count'    => $meta_counts['meta'],
			'entry_details_count' => $meta_counts['details'],
			'entry_notes_count'   => $meta_counts['notes'],
			'lang'                => $lang,
			'db'                  => $db,
		);

		$installation_telemetry = array(
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_AUTO_UPDATE,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_CURRENCY,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_DATA_COLLECTION,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_EMAIL,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_FORM_TYPES,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_FORM_TYPES_OTHER,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_HIDE_LICENSE,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_ORGANIZATION,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_ORGANIZATION_OTHER,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_SERVICES,
			GF_Setup_Wizard_Endpoint_Save_Prefs::PARAM_SERVICES_OTHER,
		);

		$wizard_endpoint = GFForms::get_service_container()->get( GF_Setup_Wizard_Service_Provider::SAVE_PREFS_ENDPOINT );
		foreach ( $installation_telemetry as $telem ) {
			$post[ $telem ] = $wizard_endpoint->get_value( $telem );
		}

		return $post;
	}

	/**
	 * Collect the data from the Gravity Forms settings page
	 *
	 * @since 2.8.3
	 *
	 * @return array
	 */
	public function get_gf_settings() {
		if ( ! $this->data_collection ) {
			return array();
		}

		$gform_settings = array();

		$settings_keys = array(
			'gform_enable_logging',
			'rg_gforms_default_theme',
			'gform_enable_toolbar_menu',
			'gform_enable_noconflict',
		);

		foreach ( $settings_keys as $key ) {
			$gform_settings[ $key ] = get_option( $key );
		}

		return $gform_settings;
	}

	/**
	 * Count the number of forms with legacy mode enabled.
	 *
	 * @since 2.8.3
	 *
	 * @return array
	 */
	public function get_legacy_forms() {
		if ( ! $this->data_collection ) {
			return array();
		}

		$legacy_forms = 0;

		$forms = GFFormsModel::get_forms();
		foreach ( $forms as $form ) {
			// if form has legacy mode enabled, add it to the total
			if ( GFCommon::is_legacy_markup_enabled( $form->id ) ) {
				$legacy_forms++;
			}
		}

		return array( 'legacy_forms' => $legacy_forms );
	}

	/**
	 * Stores the response from the version.php endpoint, to be used by the license service.
	 *
	 * @since 2.8
	 *
	 * @param array $response Raw response from the API endpoint.
	 */
	public static function data_sent( $response ) {
		$version_info = array(
			'is_valid_key' => '1',
			'version'      => '',
			'url'          => '',
			'is_error'     => '1',
		);

		if ( is_wp_error( $response ) || rgars( $response, 'response/code' ) != 200 ) {
			$version_info['timestamp'] = time();

			return $version_info;
		}

		$decoded = json_decode( $response['body'], true );

		if ( empty( $decoded ) ) {
			$version_info['timestamp'] = time();

			return $version_info;
		}

		$decoded['timestamp'] = time();

		update_option( 'gform_version_info', $decoded, false );

		\GFCommon::log_debug( __METHOD__ . sprintf( '(): Version info cached.' ) );
	}
}
