<?php

namespace Gravity_Forms\Gravity_Forms\Ajax\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use GFForms;

/**
 * Config items for Ajax operations
 *
 * @since 2.9.0
 */
class GF_Ajax_Config extends GF_Config {

	protected $name               = 'gform_theme_config';
	protected $script_to_localize = 'gform_gravityforms_theme';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$preview_query_string = \GFCommon::is_preview() ? '?gf_ajax_page=preview' : '';
		return array(
			'common' => array(
				'form' => array(
					'ajax' => array(
						'ajaxurl'               => admin_url( 'admin-ajax.php' ) . $preview_query_string,
						'ajax_submission_nonce' => wp_create_nonce( 'gform_ajax_submission' ),
						'i18n' => array(
							/* Translators: This is used to announce the current step of a multipage form, 1. first step number, 2. total steps number, example: Step 1 of 5 */
							'step_announcement' => esc_html__( 'Step %1$s of %2$s, %3$s', 'gravityforms' ),
							'unknown_error'     => esc_html__( 'There was an unknown error processing your request. Please try again.', 'gravityforms' ),
						),
					),
				),
			),
		);
	}
}
