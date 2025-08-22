<?php

namespace Gravity_Forms\Gravity_Forms\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use GFCommon;

/**
 * Collection to hold GF_Config items and provide their structured data when needed.
 *
 * @since 2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Config
 */
class GF_Config_Collection {

	/**
	 * @var GF_Config[] $configs
	 */
	private $configs = array();

	/**
	 * Add a config to the collection.
	 *
	 * @param GF_Config $config
	 */
	public function add_config( GF_Config $config ) {
		$this->configs[] = $config;
	}

	/**
	 * Handle outputting the config data.
	 *
	 * If $localize is true, data is actually localized via `wp_localize_script`, otherwise
	 * data is simply returned as an array.
	 *
	 * @since 2.6
	 *
	 * @param bool $localize Whether to localize the data, or simply return it.
	 *
	 * @return array
	 */
	public function handle( $localize = true, $args = null ) {

		$scripts          = $this->get_configs_by_script();
		$data_to_localize = array();

		foreach ( $scripts as $script => $items ) {
			$item_data        = $this->localize_data_for_script( $script, $items, $localize, $args );
			$data_to_localize = array_merge( $data_to_localize, $item_data );
		}

		return $data_to_localize;
	}

	public function handle_ajax() {

		// Check nonce.
		$nonce_result = check_ajax_referer( 'gform_config_ajax', 'gform_ajax_nonce', false );

		if ( ! $nonce_result ) {
			GFCommon::send_json_error( esc_html__( 'Unable to verify nonce. Please refresh the page and try again.', 'gravityforms' ) );
		}

		$args         = json_decode( rgpost( 'args' ), true );
		$config_path  = rgpost( 'config_path' );
		$query_string = rgpost( 'query_string' );

		// Making the query string available for use with form filters.
		if ( ! empty( $query_string ) && is_string( $query_string ) ) {
			parse_str( $query_string, $query );
			unset( $query['gf_page'] ); // Removing so it doesn't conflict with gf_ajax_page=preview.
			$_GET = array_merge( $_GET, $query );
		}

		$configs = $this->get_configs_by_path( $config_path, $args );

		if ( ! $configs ) {
			GFCommon::send_json_error( sprintf( esc_html__( 'Unable to find config: %s', 'gravityforms' ), $config_path ) );
		}

		$data = $this->get_merged_data_for_object( $configs, $args );
		GFCommon::send_json_success( $data );
	}
	/**
	 * Localize the data for the given script.
	 *
	 * @since 2.6
	 *
	 * @param string $script   The script to localize.
	 * @param array  $items    The associative array of configs to process for this script.
	 * @param bool   $localize Whether to actually localize the data, or simply return it.
	 * @param array  $args     The arguments to pass to the config objects.
	 *
	 * @return array Returns the localized data.
	 */
	private function localize_data_for_script( $script, $items, $localize = true, $args = null ) {
		$data = array();

		foreach ( $items as $name => $configs ) {

			$localized_data = $this->get_merged_data_for_object( $configs, $args );

			/**
			 * Allows users to filter the data localized for a given script/resource.
			 *
			 * @since 2.6
			 *
			 * @param array  $localized_data The current localize data
			 * @param string $script         The script being localized
			 * @param array  $configs        An array of $configs being applied to this script
			 *
			 * @return array
			 */
			$localized_data = apply_filters( 'gform_localized_script_data_' . $name, $localized_data, $script, $configs );

			$data[ $name ] = $localized_data;

			if ( $localize ) {
				wp_localize_script( $script, $name, $localized_data );
			}
		}

		return $data;
	}

	/**
	 * Get the merged data object for the applicable configs. Will process each config by its
	 * $priority property, overriding or merging values as needed.
	 *
	 * @since 2.6
	 *
	 * @param GF_Config[] $configs
	 */
	private function get_merged_data_for_object( $configs, $args ) {
		// Squash warnings for PHP < 7.0 when running tests.
		@usort( $configs, array( $this, 'sort_by_priority' ) );

		$data = array();

		foreach ( $configs as $config ) {

			$config->set_args( $args );

			// Config is set to overwrite data - simply return its value without attempting to merge.
			if ( $config->should_overwrite() ) {
				$data = $config->get_data();
				continue;
			}

			// Config should be merged - loop through each key and attempt to recursively merge the values.
			foreach ( $config->get_data() as $key => $value ) {
				$existing = isset( $data[ $key ] ) ? $data[ $key ] : null;

				if ( is_null( $existing ) || ! is_array( $existing ) || ! is_array( $value ) ) {
					$data[ $key ] = $value;
					continue;
				}

				$data[ $key ] = array_merge_recursive( $existing, $value );
			}
		}

		return $data;
	}

	/**
	 * Get the appropriate configs, organized by the script they belong to.
	 *
	 * @since 2.6
	 *
	 * @return array
	 */
	private function get_configs_by_script() {
		$data_to_localize = array();

		foreach ( $this->configs as $config ) {
			if ( ( ! defined( 'GFORMS_DOING_MOCK' ) || ! GFORMS_DOING_MOCK ) && ! $config->should_enqueue() ) {
				continue;
			}

			$data_to_localize[ $config->script_to_localize() ][ $config->name() ][] = $config;
		}

		return $data_to_localize;
	}

	/**
	 * Gets a list of configs that match the specified config path.
	 *
	 * @since 2.9.0
	 *
	 * @param string $config_path The path of the config to be returned.
	 * @param array  $args        The arguments to pass to the config objects.
	 *
	 * @return array Returns an array of configs that are associated with the specified config path.
	 */
	private function get_configs_by_path( $config_path, $args )
	{
		$configs = array();
		foreach ( $this->configs as $config ) {
			if ( $config->enable_ajax( $config_path, $args ) ) {
				$configs[] = $config;
			}
		}
		return $configs;
	}

	/**
	 * usort() callback to sort the configs by their $priority.
	 *
	 * @param GF_Config $a
	 * @param GF_Config $b
	 *
	 * @return int
	 */
	public function sort_by_priority( GF_Config $a, GF_Config $b ) {
		if ( $a->priority() === $b->priority() ) {
			return 0;
		}

		return $a->priority() < $b->priority() ? - 1 : 1;
	}
}
