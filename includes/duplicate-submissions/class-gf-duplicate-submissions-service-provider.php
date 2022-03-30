<?php
/**
 * Service Provider for Duplicate Submission Service
 *
 * @package Gravity_Forms\Gravity_Forms\Duplicate_Submissions
 */

namespace Gravity_Forms\Gravity_Forms\Duplicate_Submissions;

use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;
use Gravity_Forms\Gravity_Forms\Util\GF_Util_Service_Provider;

/**
 * Class GF_License_Service_Provider
 *
 * Service provider for the Duplicate Submission Service.
 */
class GF_Duplicate_Submissions_Service_Provider extends GF_Service_Provider {

	const GF_DUPLICATE_SUBMISSION_HANDLER = 'gf_duplicate_submission_handler';

	/**
	 * Includes all related files and adds all containers.
	 *
	 * @param GF_Service_Container $container Container singleton object.
	 */
	public function register( GF_Service_Container $container ) {
		\GFForms::include_gravity_api();

		require_once plugin_dir_path( __FILE__ ) . 'class-gf-duplicate-submissions-handler.php';

		$container->add(
			self::GF_DUPLICATE_SUBMISSION_HANDLER,
			function () {
				return new GF_Duplicate_Submissions_Handler( \GFCommon::get_base_url() );
			}
		);
	}

	/**
	 * Initializes service.
	 *
	 * @param GF_Service_Container $container Service Container.
	 */
	public function init( GF_Service_Container $container ) {
		parent::init( $container );

		$duplicate_submission_handler = $container->get( self::GF_DUPLICATE_SUBMISSION_HANDLER );

		add_action( 'gform_enqueue_scripts', array( $duplicate_submission_handler, 'maybe_enqueue_scripts' ) );
		add_action( 'wp_loaded', array( $duplicate_submission_handler, 'maybe_handle_safari_redirect' ), 8, 0 );
	}
}
