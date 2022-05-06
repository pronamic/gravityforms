<?php

namespace Gravity_Forms\Gravity_Forms\Settings;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\Settings\Config\GF_Settings_Config_Admin;
use Gravity_Forms\Gravity_Forms\Settings\Config\GF_Settings_Config_I18N;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class GF_Settings_Service_Provider
 *
 * Service provider for the Settings Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Settings;
 */
class GF_Settings_Service_Provider extends GF_Service_Provider {

	// Configs
	const SETTINGS_CONFIG_I18N  = 'settings_config_i18n';
	const SETTINGS_CONFIG_ADMIN = 'settings_config_admin';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::SETTINGS_CONFIG_I18N  => GF_Settings_Config_I18N::class,
		self::SETTINGS_CONFIG_ADMIN => GF_Settings_Config_Admin::class,
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
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-settings-config-i18n.php' );
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-settings-config-admin.php' );

		$this->add_configs( $container );
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

}