<?php

namespace Gravity_Forms\Gravity_Forms\Settings;

use Gravity_Forms\Gravity_Forms\Settings\Fields\Base;
use WP_Error;

defined( 'ABSPATH' ) || die();

class Fields {

	/**
	 * A collection of registered Settings field types.
	 *
	 * @since 2.5
	 *
	 * @var Fields\Base[]
	 */
	private static $types = array();

	/**
	 * Initialize a new Settings field.
	 *
	 * @since 2.5
	 *
	 * @param array|Base                           $props
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 *
	 * @return Fields\Base|WP_Error
	 */
	public static function create( $props, $settings ) {

		// If field is already created, return.
		if ( is_a( $props, 'Gravity_Forms\Gravity_Forms\Settings\Fields\Base' ) && ! self::exists( $props['type'] ) ) {
			return $props;
		}

		// Ensure Settings instance was provided.
		if ( ! is_a( $settings, 'Gravity_Forms\Gravity_Forms\Settings\Settings' ) && ! is_subclass_of( $settings, 'Gravity_Forms\Gravity_Forms\Settings\Settings' ) ) {
			return new WP_Error( 'settings_not_found', 'Instance of Settings Framework must be provided.' );
		}

		// If no field type is defined, use Base field.
		if ( ( ! rgar( $props, 'type' ) || ! self::exists( $props['type'] ) ) && rgar( $props, 'callback' ) ) {
			return new Fields\Base( $props, $settings );
		}

		// If field type does not exist, exit.
		if ( ! self::exists( $props['type'] ) ) {
			return new WP_Error( 'type_not_found', 'Field type does not exist.' );
		}

		// Create field.
		$field = new self::$types[ $props['type'] ]( $props, $settings );

		return $field;

	}

	/**
	 * Determine if field type exists.
	 *
	 * @since 2.5
	 *
	 * @param string $type Field type.
	 *
	 * @return bool
	 */
	public static function exists( $type ) {

		return isset( self::$types[ $type ] );

	}

	/**
	 * Register a new Settings field type.
	 *
	 * @since 2.5
	 *
	 * @param string $type       Field type.
	 * @param string $class_name Class name.
	 *
	 * @return bool|WP_Error
	 */
	public static function register( $type, $class_name ) {

		if ( self::exists( $type ) ) {
			return new WP_Error( 'type_registered', 'Field type already registered.' );
		}

		self::$types[ $type ] = $class_name;

		return true;

	}

}

// Load all field files.
foreach ( glob( plugin_dir_path( __FILE__ ) . '/fields/class-*.php' ) as $filename ) {
	require_once( $filename );
}
