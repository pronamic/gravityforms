<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class HTML extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'html';





	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {
		$html = rgobj( $this, 'html' );

		if ( is_callable( $html ) ) {
			return call_user_func( $html );
		}

		// Prepare markup.
		return $html;
	}

}

Fields::register( 'html', '\Gravity_Forms\Gravity_Forms\Settings\Fields\HTML' );
