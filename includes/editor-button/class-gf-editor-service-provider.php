<?php

namespace Gravity_Forms\Gravity_Forms\Editor_Button;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\Editor_Button\Config\GF_Editor_Config;
use Gravity_Forms\Gravity_Forms\Editor_Button\Dom\GF_Editor_Button;
use Gravity_Forms\Gravity_Forms\Editor_Button\Endpoints\GF_Editor_Save_Editor_Settings;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class GF_Editor_Service_Provider
 *
 * Service provider for the Embed Form Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Editor_Button;
 */
class GF_Editor_Service_Provider extends GF_Service_Provider {

	// Configs
	const EDITOR_CONFIG = 'editor_config';
	const ENDPOINTS_CONFIG = 'editor_endpoints_config';

	// DOM
	const DOM_EDITOR_BUTTON = 'dom_editor_button';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.8
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::EDITOR_CONFIG => GF_Editor_Config::class,
	);

	/**
	 * Register services to the container.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		// Configs
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-editor-config.php' );

		// Dom
		require_once( plugin_dir_path( __FILE__ ) . '/dom/class-gf-editor-button.php' );

		// Endpoints
		require_once( plugin_dir_path( __FILE__ ) . '/endpoints/class-gf-editor-save-editor-settings.php' );

		$container->add( self::ENDPOINTS_CONFIG, function () {
			return new GF_Editor_Save_Editor_Settings();
		} );

		$this->add_configs( $container );
		$this->dom( $container );
	}

	/**
	 * Initialize any actions or hooks.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	public function init( GF_Service_Container $container ) {
		add_action( 'gform_after_toolbar_buttons', function () use ( $container ) {
			$container->get( self::DOM_EDITOR_BUTTON )->output_button();
		} );

		add_action( 'wp_ajax_' . GF_Editor_Save_Editor_Settings::ACTION_NAME, function () use ( $container ) {
			$container->get( self::ENDPOINTS_CONFIG )->handle();
		} );
	}

	/**
	 * For each config defined in $configs, instantiate and add to container.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function add_configs( GF_Service_Container $container ) {
		foreach ( $this->configs as $name => $class ) {
			$container->add( $name, function () use ( $container, $class ) {
				return new $class( $container->get( GF_Config_Service_Provider::DATA_PARSER ) );
			} );

			$container->get( GF_Config_Service_Provider::CONFIG_COLLECTION )->add_config( $container->get( $name ) );
		}
	}

	/**
	 * Register DOM-related services.
	 *
	 * @since 2.6
	 *
	 * @param GF_Service_Container $container
	 *
	 * @return void
	 */
	private function dom( GF_Service_Container $container ) {
		$container->add( self::DOM_EDITOR_BUTTON, function() {
			return new GF_Editor_Button();
		});
	}

	/**
	 * Determine if the compact view is enabled for the given form and user.
	 *
	 * @since 2.8
	 *
	 * @param int $user_id The user ID.
	 * @param int $form_id The form ID.
	 *
	 * @return bool
	 */
	public static function is_compact_view_enabled( $user_id, $form_id ) {
		$compact_view = get_user_meta( $user_id, 'gform_compact_view_' . $form_id, true );
		return $compact_view === 'enable';
	}

	/**
	 * Determine if the field ID view in compact view is enabled for the given form and user.
	 *
	 * @since 2.8
	 *
	 * @param int $user_id The user ID.
	 * @param int $form_id The form ID.
	 *
	 * @return bool
	 */
	public static function is_field_id_enabled( $user_id, $form_id ) {
		$field_id = get_user_meta( $user_id, 'gform_compact_view_show_id_' . $form_id, true );
		return $field_id === 'enable';
	}

}
