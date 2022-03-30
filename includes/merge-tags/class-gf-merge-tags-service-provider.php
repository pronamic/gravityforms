<?php

namespace Gravity_Forms\Gravity_Forms\Merge_Tags;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\Merge_Tags\Config\GF_Merge_Tags_Config_I18N;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class GF_Merge_Tags_Service_Provider
 *
 * Service provider for the Merge_Tags Service.
 *
 * @package Gravity_Forms\Gravity_Forms\Merge_Tags;
 */
class GF_Merge_Tags_Service_Provider extends GF_Service_Provider {

	// Configs
	const MERGE_TAGS_CONFIG_I18N = 'merge_tags_config_i18n';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::MERGE_TAGS_CONFIG_I18N => GF_Merge_Tags_Config_I18N::class,
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
		require_once( plugin_dir_path( __FILE__ ) . '/config/class-gf-merge-tags-config-i18n.php' );

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