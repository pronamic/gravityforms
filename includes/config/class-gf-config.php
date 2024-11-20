<?php

namespace Gravity_Forms\Gravity_Forms\Config;

/**
 * Base class for providing advanced functionality when localizing Config Data
 * for usage in Javascript.
 *
 * @since   2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Config
 */
abstract class GF_Config {

	/**
	 * The Data Parser
	 *
	 * @since 2.6
	 *
	 * @var GF_Config_Data_Parser
	 */
	protected $parser;

	/**
	 * The data for this config object.
	 *
	 * @since 2.6
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * The object name for this config.
	 *
	 * @since 2.6
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The ID of the script to localize the data to.
	 *
	 * @since 2.6
	 *
	 * @var string
	 */
	protected $script_to_localize;

	/**
	 * The priority of this config - can be used to control the order in
	 * which configs are processed in the Collection.
	 *
	 * @since 2.6
	 *
	 * @var int
	 */
	protected $priority = 0;

	/**
	 * Whether the config should enqueue it's data. Can also be handled by overriding the
	 * ::should_enqueue() method.
	 *
	 * @since 2.6
	 *
	 * @var bool
	 */
	protected $should_enqueue = true;

	/**
	 * Whether this config should overwrite previous values in the object.
	 *
	 * If set to "true", the object will be overwritten by the values provided here.
	 * If set to "false", the object will have its values merged with those defined here, recursively.
	 *
	 * @since 2.6
	 *
	 * @var bool
	 */
	protected $overwrite = false;

	/**
	 * An args array. Use in the data() method to retrieve data specific to the specified args. For example, form specific configs will have an array of form ids specified in args.
	 *
	 * @var array
	 */
	protected $args = array();

	/**
	 * Constructor
	 *
	 * @param GF_Config_Data_Parser $parser
	 */
	public function __construct( GF_Config_Data_Parser $parser ) {
		$this->parser = $parser;
	}

	/**
	 * Method to handle defining the data array for this config.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	abstract protected function data();

	/**
	 * Override this method to add enable ajax loading for a specific config path.
	 * To enable loading data() via ajax, check if $config_path is one of the paths that are provided by the config. If so, return true.
	 *
	 * Example:
	 * public function enable_ajax( $config_path, $args ) {
	 *    return str_starts_with( $config_path, 'gform_theme_config/common/form/product_meta' );
	 * }
	 *
	 * @since 2.9.0
	 *
	 * @param string $config_path The full path to the config item when stored in the browser's window object, for example: "gform_theme_config/common/form/product_meta"
	 * @param array  $args        The args used to load the config data. This will be empty for generic config items. For form specific items will be in the format: array( 'form_ids' => array(123,222) ).
	 *
	 * @return bool Return true to load the config data associated with the provided $config_path. Return false otherwise.
	 */
	public function enable_ajax( $config_path, $args ) {
		return false;
	}

	/**
	 * Determine if the config should enqueue its data. If should_enqueue() is a method,
	 * call it and return the result. If not, simply return the (boolean) value of the property.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		if ( is_callable( $this->should_enqueue ) ) {
			return call_user_func( $this->should_enqueue );
		}

		return $this->should_enqueue;
	}

	/**
	 * Get the data for the config, passing it through a filter.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	public function get_data() {
		if ( ( ! defined( 'GFORMS_DOING_MOCK' ) || ! GFORMS_DOING_MOCK ) && ! $this->should_enqueue() ) {
			return false;
		}

		/**
		 * Allows developers to modify the raw config data being sent to the Config Parser. Useful for
		 * adding in custom default/mock values for a given entry in the data, as well as modifying
		 * things like callbacks for dynamic data before it's parsed and localized.
		 *
		 * @since 2.6
		 *
		 * @param array  $data
		 * @param string $script_to_localize
		 *
		 * @return array
		 */
		$data = apply_filters( 'gform_config_data_' . $this->name(), $this->data(), $this->script_to_localize() );

		return $this->parser->parse( $data );
	}

	/**
	 * Get the name of the config's object.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function name() {
		return $this->name;
	}

	/**
	 * Get the $priority for the config.
	 *
	 * @since 2.6
	 *
	 * @return int
	 */
	public function priority() {
		return $this->priority;
	}

	/**
	 * Get the script to localize.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function script_to_localize() {
		return $this->script_to_localize;
	}

	/**
	 * Get whether the config should override previous values.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	public function should_overwrite() {
		return $this->overwrite;
	}

	/**
	 * Sets the $form_ids arrays.
	 *
	 * @since 2.9.0
	 *
	 * @param array $args Args array to be set
	 *
	 * @return void
	 */
	public function set_args( $args ) {
		$this->args = $args;
	}

	/**
	 * Validates the config data against a hash to ensure it has not been tampered with.
	 * This method is called via AJAX, initiated by the gform.config.isValid() JS method.
	 *
	 * @since 2.9.0
	 *
	 * @return void
	 */
	public static function validate_ajax() {

		// Check nonce
		$nonce_result = check_ajax_referer( 'gform_config_ajax', 'gform_ajax_nonce', false );

		if ( ! $nonce_result ) {
			wp_send_json_error( esc_html__( 'Unable to verify nonce. Please refresh the page and try again.', 'gravityforms' ) );
		}

		$config = json_decode( rgpost( 'config' ), true );
		$hash   = rgar( $config, 'hash' );
		if ( ! $hash ) {
			wp_send_json_error( esc_html__( 'Invalid config.', 'gravityforms' ) );
		}

		// Remove hash from config before validating.
		unset( $config['hash'] );

		// Compare hash to config.
		if ( $hash !== self::hash( $config ) ) {
			wp_send_json_error( esc_html__( 'Config validation failed. Hash does not match', 'gravityforms' ) );
		}

		// Send success response.
		wp_send_json_success();
	}

	/**
	 * Hashes the config data.
	 *
	 * @since 2.9.0
	 *
	 * @param array $config
	 *
	 * @return string Returns the hash of the config data.
	 */
	public static function hash( $config ) {
		return wp_hash( json_encode( $config ) );
	}
}

// AJAX hash validation for config data.
add_action('wp_ajax_gform_validate_config', array( 'Gravity_Forms\Gravity_Forms\Config\GF_Config', 'validate_ajax' ) );
add_action('wp_ajax_nopriv_gform_validate_config', array( 'Gravity_Forms\Gravity_Forms\Config\GF_Config', 'validate_ajax' ) );
