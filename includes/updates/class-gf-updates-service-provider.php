<?php
/**
 * Service provider for managing auto updates.
 *
 * @package Gravity_Forms\Gravity_Forms
 */

namespace Gravity_Forms\Gravity_Forms\Updates;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GF_Auto_Updates_Service_Provider
 *
 * @since 2.7.2
 */
class GF_Auto_Updates_Service_Provider extends GF_Service_Provider {

	const GF_AUTO_UPDATES_HANDLER = 'gf_auto_updates_handler';

	/**
	 * Registers the handler.
	 *
	 * @since 2.7.2
	 *
	 * @param GF_Service_Container $container
	 */
	public function register( GF_Service_Container $container ) {
		require_once GF_PLUGIN_DIR_PATH . 'includes/updates/class-gf-auto-updates-handler.php';

		$container->add(
			self::GF_AUTO_UPDATES_HANDLER,
			function () use ( $container ) {
				return new GF_Auto_Updates_Handler();
			}
		);
	}

	/**
	 * Initializing hooks.
	 *
	 * @since 2.7.2
	 *
	 * @param GF_Service_Container $container
	 */
	public function init( GF_Service_Container $container ) {
		$handler = $container->get( self::GF_AUTO_UPDATES_HANDLER );
		$handler->add_gf_hooks();
		$handler->add_wp_hooks();

		if ( doing_action( 'activate_' . GF_PLUGIN_BASENAME ) ) {
			$handler->activation_sync();
		}

	}

}
