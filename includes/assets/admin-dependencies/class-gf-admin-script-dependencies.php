<?php

namespace Gravity_Forms\Gravity_Forms\Assets\Admin_Dependencies;

use Gravity_Forms\Gravity_Forms\Assets\GF_Dependencies;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class GF_Admin_Script_Dependencies
 *
 * @since 2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Assets\Admin_Dependencies;
 */
class GF_Admin_Script_Dependencies extends GF_Dependencies {

	/**
	 * Items to enqueue globally in admin.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $items = array(
		'gform_gravityforms_admin',
	);

	/**
	 * Enqueue the item by handle.
	 *
	 * @since 2.6
	 *
	 * @param $handle
	 *
	 * @return void
	 */
	protected function do_enqueue( $handle ) {
		wp_enqueue_script( $handle );
	}

	/**
	 * Whether the global scripts should enqueue.
	 *
	 * @since 2.6
	 *
	 * @return bool
	 */
	protected function should_enqueue() {
		/***
		 * The newer JavaScript added in 2.5 is now enqueued globally in the admin.
		 * We implemented this as we are now using code splitting to only inject JavaScript
		 * dynamically as it is needed, and to also allow our addons easy access to the core libraries
		 * we use.
		 *
		 * This filter allows users to make our admin scripts only load on Gravity Forms admin screens.
		 * Setting it to false may cause unexpected behavior/feature loss in some addons or core.
		 *
		 * @since 2.6.0
		 *
		 * @param bool true Load admin scripts globally?
		 *
		 * @return bool
		 */
		return apply_filters( 'gform_load_admin_scripts_globally', true );
	}
}