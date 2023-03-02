<?php

namespace Gravity_Forms\Gravity_Forms\Assets\Admin_Dependencies;

use Gravity_Forms\Gravity_Forms\Assets\GF_Dependencies;
use Gravity_Forms\Gravity_Forms\GF_Service_Container;
use Gravity_Forms\Gravity_Forms\GF_Service_Provider;

/**
 * Class GF_Admin_Style_Dependencies
 *
 * @since 2.6
 *
 * @package Gravity_Forms\Gravity_Forms\Assets\Admin_Dependencies;
 */
class GF_Admin_Style_Dependencies extends GF_Dependencies {

	/**
	 * Items to enqueue globally in admin.
	 *
	 * @since 2.6
	 *
	 * @var string[]
	 */
	protected $items = array(
		'gform_common_css_utilities',
		'gform_common_icons',
		'gform_admin_icons',
		'gform_admin_components',
		'gform_admin_css_utilities',
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
		wp_enqueue_style( $handle );
	}

}

