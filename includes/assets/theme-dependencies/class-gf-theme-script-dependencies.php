<?php

namespace Gravity_Forms\Gravity_Forms\Assets\Theme_Dependencies;

use Gravity_Forms\Gravity_Forms\Assets\GF_Dependencies;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class GF_Theme_Script_Dependencies
 *
 * @since 2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Assets\Theme_Dependencies;
 */
class GF_Theme_Script_Dependencies extends GF_Dependencies {

	/**
	 * Items to enqueue globally in admin.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $items = array(
		'gform_gravityforms_theme',
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

}
