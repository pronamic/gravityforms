<?php
/**
 * Service provider for async (background) processors.
 *
 * @package Gravity_Forms\Gravity_Forms
 */

namespace Gravity_Forms\Gravity_Forms\Async;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use GFForms;
use GF_Background_Process;
use GF_Background_Upgrader;
use GF_Feed_Processor;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Background_Processing_Service_Provider
 *
 * @since 2.6.9
 */
class GF_Background_Process_Service_Provider extends GF_Service_Provider {

	const UPGRADER = 'upgrade_processor';
	const FEEDS = 'feeds_processor';
	const NOTIFICATIONS = 'notifications_processor';

	/**
	 * The names and classes of the async (background) processors.
	 *
	 * @since 2.6.9
	 *
	 * @var string[]
	 */
	protected $processors = array(
		self::UPGRADER      => GF_Background_Upgrader::class,
		self::FEEDS         => GF_Feed_Processor::class,
		self::NOTIFICATIONS => GF_Notifications_Processor::class,
	);

	/**
	 * Initializing the processors and adding them to the container as services.
	 *
	 * @since 2.6.9
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		GFForms::init_background_upgrader();
		require_once GF_PLUGIN_DIR_PATH . 'includes/addon/class-gf-feed-processor.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/async/class-gf-notifications-processor.php';

		foreach ( $this->processors as $name => $class ) {
			$container->add( $name, function () use ( $name, $class ) {
				if ( $name === self::UPGRADER ) {
					return GFForms::$background_upgrader;
				}

				$callback = array( $class, 'get_instance' );
				if ( is_callable( $callback ) ) {
					return call_user_func( $callback );
				}

				return new $class();
			} );
		}
	}

	/**
	 * Initializing hooks.
	 *
	 * @since 2.6.9
	 *
	 * @param GF_Service_Container $container
	 */
	public function init( GF_Service_Container $container ) {
		$processors = array_keys( $this->processors );

		add_action( 'gform_uninstalling', function () use ( $processors, $container ) {
			foreach ( $processors as $name ) {
				/**
				 * @var GF_Background_Process $processor
				 */
				$processor = $container->get( $name );
				$processor->clear_scheduled_events();
				$processor->clear_queue( true );
				$processor->unlock_process();
			}
		} );
	}

}
