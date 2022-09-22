<?php
/**
 * Handles logic for adjusting Gravity Forms to different environment configurations.
 *
 * @package Gravity_Forms\Gravity_Forms\Environment_Config
 */

namespace Gravity_Forms\Gravity_Forms\Environment_Config;

/**
 * Class GF_Environment_Config_Handler
 *
 * @since 2.6.7
 *
 * Provides functionality for handling environment configuration options.
 */
class GF_Environment_Config_Handler {

	/**
	 * Provides caching service.
	 *
	 * @var \GFCache $cache
	 */
	protected $cache;

	/**
	 * GF_Environment_Config_Handler constructor.
	 *
	 * @since 2.6.7
	 *
	 * @param \GFCache $cache Provides caching service.
	 */
	public function __construct( $cache ) {

		$this->cache = $cache;
	}

	/**
	 * Gets an environment setting from wp_options.
	 *
	 * @since 2.6.7
	 *
	 * @param string $name    The environment setting name. Don't include the "gf_env_" prefix.
	 * @param mixed  $default Default value to be returned if option is not set.
	 *
	 * @return mixed
	 */
	protected function get_environment_setting( $name, $default = false ) {
		$option_name = "gf_env_{$name}";
		$setting = $this->cache->get( $option_name, $found );
		if ( ! $found ) {
			$setting = get_option( $option_name, $default );
			$this->cache->set( $option_name, $setting );
		}
		return $setting;
	}

	/**
	 * Gets the license_key config value.
	 *
	 * @since 2.6.7
	 *
	 * @return string The license key config.
	 */
	public function get_license_key() {
		return $this->get_environment_setting( 'license_key', false );
	}

	/**
	 * Gets the hide_license config value.
	 *
	 * @since 2.6.7
	 *
	 * @return bool Returns true if license is supposed to be hidden from the UI, false otherwise.
	 */
	public function get_hide_license() {
		return (bool) $this->get_environment_setting( 'hide_license', false );
	}

	/**
	 * Gets the hide_install_wizard config value.
	 *
	 * @since 2.6.7
	 *
	 * @return bool Returns true if install wizard is supposed to be hidden. Returns false otherwise.
	 */
	public function get_hide_install_wizard() {
		return (bool) $this->get_environment_setting( 'hide_setup_wizard', false );
	}

	/**
	 * Gets the support_url config value.
	 *
	 * @since 2.6.7
	 *
	 * @return string The support link config value.
	 */
	public function get_support_url() {
		return $this->get_environment_setting( 'support_url', 'https://gravityforms.com/support/' );
	}

	/**
	 * Target of the pre_option_gform_pending_installation filter. Bypasses the installation wizard by returning 0 for the gform_pending_installation option.
	 *
	 * @hook pre_option_gform_pending_installation 10, 1
	 *
	 * @return int Returns 0 if the install wizard is set to be hidden by environment settings. Otherwise return false so that option is not overridden.
	 */
	public function maybe_override_gform_pending_installation() {

		// If environment config is set to hide install wizard, override gform_pending_intallation option with 0. Otherwise, use existing option.
		$hide_install_wizard = $this->get_hide_install_wizard();
		return $hide_install_wizard ? 0 : false;
	}


	/**
	 * Target of the pre_option_rg_gforms_key filter. Uses the license key configured by the environment settings if one is set.
	 *
	 * @hook pre_option_rg_gforms_key 10, 1
	 *
	 * @since 2.6.7
	 *
	 * @return string Returns the environment license key if one is set. If not set, return false so that value is not overridden.
	 */
	public function maybe_override_rg_gforms_key() {

		// Use environment license key if one is set. Otherwise, use rg_gforms_key option.
		$env_license_key = $this->get_license_key();
		return $env_license_key !== false ? $env_license_key : false;
	}


	/**
	 * Target of the gform_plugin_settings_fields filter. Removes the license key and license key detail sections from the array.
	 *
	 * @since 2.6.7
	 *
	 * @param array $fields The settings fields to be filtered.
	 *
	 * @return array The $fields array without the license_key and license_key_details sections.
	 */
	public function remove_license_from_settings( $fields ) {

		if ( $this->get_hide_license() ) {
			unset( $fields['license_key'] );
			unset( $fields['license_key_details'] );
		}
		return $fields;
	}
}
