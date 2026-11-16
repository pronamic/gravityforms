<?php

namespace Gravity_Forms\Gravity_Forms\Form_Display\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * Config for the require-login session check.
 *
 * Localized only when a displayed form requires the visitor to be logged in.
 *
 * @since 3.2.2
 */
class GF_Require_Login_Auth_Config extends GF_Config {

	protected $name               = 'gform_theme_config';
	protected $script_to_localize = 'gform_gravityforms_theme';

	/**
	 * Form IDs passed to gform_enqueue_scripts.
	 *
	 * Conversational Forms render with gravity_form() instead of a shortcode, so the
	 * post-content scan never supplies their IDs. The enqueue action still runs.
	 *
	 * @since 3.2.2
	 *
	 * @var int[]
	 */
	private static $enqueued_form_ids = array();

	/**
	 * Remembers a form whose scripts were enqueued directly, such as a Conversational Form.
	 *
	 * @since 3.2.2
	 *
	 * @param array $form The form being enqueued.
	 *
	 * @return void
	 */
	public static function register_enqueued_form( $form ) {
		$form_id = absint( rgar( $form, 'id' ) );
		if ( $form_id ) {
			self::$enqueued_form_ids[] = $form_id;
		}
	}

	/**
	 * Whether a displayed form needs the require-login session check.
	 *
	 * @since 3.2.2
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		foreach ( $this->get_relevant_form_ids() as $form_id ) {
			$form = \GFAPI::get_form( $form_id );
			if ( is_array( $form ) && \GFCommon::form_requires_login( $form ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Form IDs from the post-content scan and from direct script enqueues.
	 * 
	 * @since 3.2.2
	 *
	 * @return int[]
	 */
	private function get_relevant_form_ids() {
		return array_values( array_unique( array_merge( $this->get_form_ids_arg(), self::$enqueued_form_ids ) ) );
	}

	/**
	 * Config data.
	 *
	 * @since 3.2.2
	 *
	 * @return array
	 */
	public function data() {
		$login_url   = wp_login_url();
		$same_origin = $this->is_same_origin( $login_url );
		$interim_url = '';

		if ( $same_origin ) {
			$interim_url = add_query_arg(
				array(
					'interim-login' => '1',
					'wp_lang'       => get_user_locale(),
				),
				$login_url
			);
		}

		return array(
			'common' => array(
				'form' => array(
					'requireLoginAuth' => array(
						'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
						'interimLoginUrl' => $interim_url,
						'sameOrigin'      => $same_origin,
						'strings'         => array(
							'close'       => esc_html__( 'Close', 'gravityforms' ),
							'iframeTitle' => esc_html__( 'Log in', 'gravityforms' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Determines whether a URL has the same origin as the site home URL.
	 *
	 * Interim login is loaded in an iframe, which WordPress only allows for the same origin.
	 *
	 * @since 3.2.2
	 *
	 * @param string $url The URL to compare.
	 *
	 * @return bool
	 */
	private function is_same_origin( $url ) {
		$target = wp_parse_url( $url );
		$home   = wp_parse_url( home_url() );

		if ( empty( $target['host'] ) || empty( $home['host'] ) ) {
			return false;
		}

		$target_scheme = isset( $target['scheme'] ) ? strtolower( $target['scheme'] ) : 'http';
		$home_scheme   = isset( $home['scheme'] ) ? strtolower( $home['scheme'] ) : 'http';
		$target_port   = isset( $target['port'] ) ? (int) $target['port'] : ( 'https' === $target_scheme ? 443 : 80 );
		$home_port     = isset( $home['port'] ) ? (int) $home['port'] : ( 'https' === $home_scheme ? 443 : 80 );

		return $target_scheme === $home_scheme
			&& strtolower( $target['host'] ) === strtolower( $home['host'] )
			&& $target_port === $home_port;
	}
}
