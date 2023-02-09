<?php
/**
 * Service Provider for Honeypot Service
 *
 * @package Gravity_Forms\Gravity_Forms\Honeypot
 */

namespace Gravity_Forms\Gravity_Forms\Honeypot;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Service_Provider;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Honeypot\Config\GF_Honeypot_Config;
use Gravity_Forms\Gravity_Forms\Util\GF_Util_Service_Provider;

/**
 * Class GF_Honeypot_Service_Provider
 *
 * Service provider for the Honeypot Service.
 */
class GF_Honeypot_Service_Provider extends GF_Service_Provider {

	const GF_HONEYPOT_HANDLER = 'gf_honeypot_handler';

	// configs
	const GF_HONEYPOT_CONFIG = 'gf_honeypot_config';

	/**
	 * Array mapping config class names to their container ID.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $configs = array(
		self::GF_HONEYPOT_CONFIG => GF_Honeypot_Config::class,
	);

	/**
	 * Includes all related files and adds all containers.
	 *
	 * @param GF_Service_Container $container Container singleton object.
	 */
	public function register( GF_Service_Container $container ) {

		require_once plugin_dir_path( __FILE__ ) . 'class-gf-honeypot-handler.php';
		require_once plugin_dir_path( __FILE__ ) . 'config/class-gf-honeypot-config.php';

		$container->add(
			self::GF_HONEYPOT_HANDLER,
			function () {
				return new GF_Honeypot_Handler( \GFCommon::get_base_url() );
			}
		);

		$this->add_configs( $container );
	}

	/**
	 * Initializes service.
	 *
	 * @param GF_Service_Container $container Service Container.
	 */
	public function init( GF_Service_Container $container ) {
		parent::init( $container );

		$honeypot_handler = $container->get( self::GF_HONEYPOT_HANDLER );

		// Maybe abort early. If configured not to create entry.
		add_filter( 'gform_abort_submission_with_confirmation', array( $honeypot_handler, 'handle_abort_submission' ), 10, 2 );

		// Marks entry as spam.
		add_filter( 'gform_entry_is_spam', array( $honeypot_handler, 'handle_entry_is_spam' ), 1, 2 );

		// Clear validation cache.
		add_action( 'gform_after_submission', array( $honeypot_handler, 'handle_after_submission' ), 10, 2 );

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
