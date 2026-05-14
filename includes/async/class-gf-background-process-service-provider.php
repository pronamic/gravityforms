<?php
/**
 * Service provider for async (background) processors.
 *
 * @package Gravity_Forms\Gravity_Forms
 */

namespace Gravity_Forms\Gravity_Forms\Async;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Telemetry\GF_Telemetry_Processor;
use Gravity_Forms\Gravity_Forms\Bulk_Actions\GF_Entry_Bulk_Action_Processor;
use Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints\GF_Bulk_Action_Endpoint_Start;
use Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints\GF_Bulk_Action_Endpoint_Status;
use Gravity_Forms\Gravity_Forms\Bulk_Actions\Endpoints\GF_Bulk_Action_Endpoint_Cancel;
use GFForms;
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

	const UPGRADER                    = 'upgrade_processor';
	const FEEDS                       = 'feeds_processor';
	const NOTIFICATIONS               = 'notifications_processor';
	const TELEMETRY                   = 'telemetry_processor';
	const BULK_ACTION                 = 'bulk_action_processor';
	const BULK_ACTION_ENDPOINT_START  = 'bulk_action_endpoint_start';
	const BULK_ACTION_ENDPOINT_STATUS = 'bulk_action_endpoint_status';
	const BULK_ACTION_ENDPOINT_CANCEL = 'bulk_action_endpoint_cancel';

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
		self::TELEMETRY     => GF_Telemetry_Processor::class,
		self::BULK_ACTION   => GF_Entry_Bulk_Action_Processor::class,
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
		require_once GF_PLUGIN_DIR_PATH . 'includes/telemetry/class-gf-telemetry-processor.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/bulk-actions/class-gf-entry-bulk-action-processor.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/bulk-actions/endpoints/class-gf-bulk-action-endpoint-start.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/bulk-actions/endpoints/class-gf-bulk-action-endpoint-status.php';
		require_once GF_PLUGIN_DIR_PATH . 'includes/bulk-actions/endpoints/class-gf-bulk-action-endpoint-cancel.php';

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

		$container->add( self::BULK_ACTION_ENDPOINT_START, function() {
			return new GF_Bulk_Action_Endpoint_Start();
		} );

		$container->add( self::BULK_ACTION_ENDPOINT_STATUS, function() {
			return new GF_Bulk_Action_Endpoint_Status();
		} );

		$container->add( self::BULK_ACTION_ENDPOINT_CANCEL, function() {
			return new GF_Bulk_Action_Endpoint_Cancel();
		} );
	}

	public function init( GF_Service_Container $container ) {
		add_action( 'wp_ajax_' . GF_Bulk_Action_Endpoint_Start::ACTION_NAME, function() use ( $container ) {
			$container->get( self::BULK_ACTION_ENDPOINT_START )->handle();
		} );

		add_action( 'wp_ajax_' . GF_Bulk_Action_Endpoint_Status::ACTION_NAME, function() use ( $container ) {
			$container->get( self::BULK_ACTION_ENDPOINT_STATUS )->handle();
		} );

		add_action( 'wp_ajax_' . GF_Bulk_Action_Endpoint_Cancel::ACTION_NAME, function() use ( $container ) {
			$container->get( self::BULK_ACTION_ENDPOINT_CANCEL )->handle();
		} );
	}

}
