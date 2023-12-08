<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_CAPTCHA extends GF_Field {
	/**
	 * @var string
	 */
	public $type = 'captcha';


	/**
	 * The reCAPTCHA API response.
	 *
	 * @var \stdClass
	 */
	private $response;

	/**
	 * The reCAPTCHA site key.
	 *
	 * @var string
	 */
	private $site_key;

	/**
	 * The reCAPTCHA secret key.
	 *
	 * @var string
	 */
	private $secret_key;

	public function get_form_editor_field_title() {
		return esc_attr__( 'CAPTCHA', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Adds a captcha field to your form to help protect your website from spam and bot abuse.', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--recaptcha';
	}

	function get_form_editor_field_settings() {
		return array(
			'captcha_type_setting',
			'captcha_badge_setting',
			'captcha_size_setting',
			'captcha_fg_setting',
			'captcha_bg_setting',
			'captcha_language_setting',
			'captcha_theme_setting',
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'description_setting',
			'css_class_setting',
		);
	}

	/**
	 * Returns the warning message to be displayed in the form editor sidebar.
	 *
	 * @since 2.8
	 *
	 * @return string
	 */
	public function get_field_sidebar_messages() {
		if ( $this->captchaType === 'math' || $this->captchaType === 'simple_captcha' || ( ! empty( $this->get_site_key() ) && ! empty( $this->get_secret_key() ) ) ) {
			return '';
		}

		// Translators: 1. Opening <a> tag with link to the Forms > Settings > reCAPTCHA page. 2. closing <a> tag.
		return sprintf( __( 'To use reCAPTCHA v2 you must configure the site and secret keys on the %1$sreCAPTCHA Settings%2$s page.', 'gravityforms' ), "<a href='?page=gf_settings&subview=recaptcha' target='_blank'>", '</a>' );
	}

	/**
	 * Validate the reCAPTCHA field.
	 *
	 * This method always gets called on the last page of a form, as well as on the page where the field is assigned.
	 *
	 * @since Unknown
	 *
	 * @param array|string $value The field value.
	 * @param array        $form  The form data.
	 */
	public function validate( $value, $form ) {
		switch ( $this->captchaType ) {
			case 'simple_captcha' :
				if ( class_exists( 'ReallySimpleCaptcha' ) ) {
					$prefix      = $_POST[ "input_captcha_prefix_{$this->id}" ];
					$captcha_obj = $this->get_simple_captcha();

					if ( ! $captcha_obj->check( $prefix, str_replace( ' ', '', $value ) ) ) {
						$this->set_failed_validation( esc_html__( "The CAPTCHA wasn't entered correctly. Go back and try it again.", 'gravityforms' ) );
					}

					//removes old files in captcha folder (older than 1 hour);
					$captcha_obj->cleanup();
				}
				break;

			case 'math' :
				$prefixes    = explode( ',', $_POST[ "input_captcha_prefix_{$this->id}" ] );
				$captcha_obj = $this->get_simple_captcha();

				//finding first number
				for ( $first = 0; $first < 10; $first ++ ) {
					if ( $captcha_obj->check( $prefixes[0], $first ) ) {
						break;
					}
				}

				//finding second number
				for ( $second = 0; $second < 10; $second ++ ) {
					if ( $captcha_obj->check( $prefixes[2], $second ) ) {
						break;
					}
				}

				//if it is a +, perform the sum
				if ( $captcha_obj->check( $prefixes[1], '+' ) ) {
					$result = $first + $second;
				} else {
					$result = $first - $second;
				}



				if ( intval( $result ) != intval( $value ) ) {
					$this->set_failed_validation( esc_html__( "The CAPTCHA wasn't entered correctly. Go back and try it again.", 'gravityforms' ) );
				}

				//removes old files in captcha folder (older than 1 hour);
				$captcha_obj->cleanup();

				break;

			default:
				$this->validate_recaptcha( $form );
		}

	}

	/**
	 * Validates the reCAPTCHA response.
	 *
	 * In our application flow, we create a decoded string out of the reCAPTCHA service response if the reCAPTCHA field
	 * is added to the form on a page other than the last page. We therefore first attempt to validate the decoded response,
	 * falling back to validating the reCAPTCHA with a request to Google.
	 *
	 * @see GF_Field_CAPTCHA::verify_decoded_response()
	 *
	 * @since unknown
	 *
	 * @param array $form The form data.
	 *
	 * @return bool
	 */
	public function validate_recaptcha( $form ) {
		$response = $this->get_posted_recaptcha_response();

		if ( ! ( $this->verify_decoded_response( $form, $response ) || $this->verify_recaptcha_response( $response ) ) ) {
			$this->set_failed_validation( __( 'The reCAPTCHA was invalid. Go back and try it again.', 'gravityforms' ) );
			return false;
		}

		return true;
	}

	/**
	 * Verifies that the decoded response meets the requirements for submitting the form.
	 *
	 * Returns false if the decoded response doesn't exist or the reCAPTCHA field is on the last page, as we'll want
	 * regular validation at that point instead.
	 *
	 * @since 2.4.24
	 *
	 * @param array  $form     The form data.
	 * @param string $response The encoded response to verify.
	 *
	 * @return bool
	 */
	private function verify_decoded_response( $form, $response ) {
		$decoded_response = $this->get_decoded_recaptcha_response( $response );

		// No decoded object.
		if ( ! is_object( $decoded_response ) ) {
			return false;
		}

		// Not a time that we need to verify the decoded object.
		if ( ! GFFormDisplay::is_last_page( $form ) || $this->is_on_last_page( $form ) ) {
			return false;
		}

		return (
			$decoded_response->success === true
			&& ! empty( $decoded_response->token )
			&& gmdate( time() ) <= strtotime( '+1 day', strtotime( $decoded_response->challenge_ts ) )
		);
	}

	/**
	 * Set validation failed on reCAPTCHA field.
	 *
	 * @since 2.4.24
	 *
	 * @param string $message The message to set if one does not already exist.
	 */
	private function set_failed_validation( $message ) {
		$this->failed_validation  = true;
		$this->validation_message = empty( $this->errorMessage ) ? $message : $this->errorMessage;
	}

	/**
	 * Get the saved site key.
	 *
	 * @since 2.4.24
	 *
	 * @return string
	 */
	public function get_site_key() {
		if ( ! $this->site_key ) {
			$this->site_key   = get_option( 'rg_gforms_captcha_public_key', '' );
		}

		return $this->site_key;
	}

	/**
	 * Get the saved secret key.
	 *
	 * @since 2.4.25
	 *
	 * @return string
	 */
	public function get_secret_key() {
		if ( ! $this->secret_key ) {
			$this->secret_key = get_option( 'rg_gforms_captcha_private_key', '' );
		}

		return $this->secret_key;
	}

	/**
	 * Get the value of the reCAPTCHA response input.
	 *
	 * When user clicks on the "I'm not a robot" box, the response token is populated into a hidden field by Google.
	 * If the current form is a multi-page form and the reCAPTCHA field is on a page other than the last page, this
	 * value will return an openssl encoded string with the Google reCAPTCHA validation data and some supplemental
	 * validation data instead.
	 *
	 * @see GF_Field_CAPTCHA::get_encoded_recaptcha_response()
	 *
	 * @since 2.4.24
	 *
	 * @return string
	 */
	private function get_posted_recaptcha_response() {
		return sanitize_text_field( rgpost( 'g-recaptcha-response' ) );
	}

	/**
	 * Validate the reCAPTCHA token provided by Google.
	 *
	 * @since unknown
	 *
	 * @param string $response   The token to verify.
	 * @param null   $secret_key The secret key for reCAPTCHA verification.
	 *
	 * @return bool
	 */
	public function verify_recaptcha_response( $response, $secret_key = null ) {

		$verify_url = 'https://www.google.com/recaptcha/api/siteverify';

		if ( $secret_key == null ) {
			$secret_key = $this->get_secret_key();
		}

		// pass secret key and token for verification of whether the response was valid
		$response = wp_remote_post( $verify_url, array(
			'method' => 'POST',
			'body'   => array(
				'secret'   => $secret_key,
				'response' => $response
			),
		) );

		if ( ! is_wp_error( $response ) ) {
			$this->response = json_decode( wp_remote_retrieve_body( $response ) );

			return $this->response->success == true;
		} else {
			GFCommon::log_debug( __METHOD__ . '(): Validating the reCAPTCHA response has failed due to the following: ' . $response->get_error_message() );
		}

		return false;
	}

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id       = (int) $this->id;
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		switch ( $this->captchaType ) {
			case 'simple_captcha' :
				$size    = empty($this->simpleCaptchaSize) ? 'medium' : esc_attr( $this->simpleCaptchaSize );
				$captcha = $this->get_captcha();

				$tabindex = $this->get_tabindex();

				$dimensions = $is_entry_detail || $is_form_editor ? '' : "width='" . esc_attr( rgar( $captcha, 'width' ) ) . "' height='" . esc_attr( rgar( $captcha, 'height' ) ) . "'";

				return "<div class='gfield_captcha_container'><img class='gfield_captcha' src='" . esc_url( rgar( $captcha, 'url' ) ) . "' alt='' {$dimensions} /><div class='gfield_captcha_input_container simple_captcha_{$size}'><input type='text' autocomplete='off' name='input_{$id}' id='{$field_id}' {$tabindex}/><input type='hidden' name='input_captcha_prefix_{$id}' value='" . esc_attr( rgar( $captcha, 'prefix' ) ) . "' /></div></div>";
				break;

			case 'math' :
				$size      = empty( $this->simpleCaptchaSize ) ? 'medium' : esc_attr( $this->simpleCaptchaSize );
				$captcha_1 = $this->get_math_captcha( 1 );
				$captcha_2 = $this->get_math_captcha( 2 );
				$captcha_3 = $this->get_math_captcha( 3 );

				$tabindex = $this->get_tabindex();

				$dimensions   = $is_entry_detail || $is_form_editor ? '' : "width='" . esc_attr( rgar( $captcha_1, 'width' ) ) . "' height='" . esc_attr( rgar( $captcha_1, 'height' ) ) . "'";
				$prefix_value = rgar( $captcha_1, 'prefix' ) . ',' . rgar( $captcha_2, 'prefix' ) . ',' . rgar( $captcha_3, 'prefix' );

				return "<div class='gfield_captcha_container'><img class='gfield_captcha' src='" . esc_url( rgar( $captcha_1, 'url' ) ) . "' alt='' {$dimensions} /><img class='gfield_captcha' src='" . esc_url( rgar( $captcha_2, 'url' ) ) . "' alt='' {$dimensions} /><img class='gfield_captcha' src='" . esc_url( rgar( $captcha_3, 'url' ) ) . "' alt='' {$dimensions} /><div class='gfield_captcha_input_container math_{$size}'><input type='text' autocomplete='off' name='input_{$id}' id='{$field_id}' {$tabindex}/><input type='hidden' name='input_captcha_prefix_{$id}' value='" . esc_attr( $prefix_value ) . "' /></div></div>";
				break;

			default:

				$this->site_key   = $this->get_site_key();
				$this->secret_key = $this->get_secret_key();
				$theme      = in_array( $this->captchaTheme, array( 'blackglass', 'dark' ) ) ? 'dark' : 'light';
				$type 		= get_option( 'rg_gforms_captcha_type' );
				if ( $is_entry_detail || $is_form_editor ){

					//for admin, show a thumbnail depending on chosen theme
					if ( empty( $this->site_key ) || empty( $this->secret_key ) ) {

						return "<div class='ginput_container'><div class='captcha_message'>" . __( 'To use the reCAPTCHA field you must do the following:', 'gravityforms' ) . "</div><div class='captcha_message'>1 - <a href='https://www.google.com/recaptcha/admin' target='_blank'>" . sprintf( __( 'Sign up%s for an API key pair for your site.', 'gravityforms' ), '</a>' ) . "</div><div class='captcha_message'>2 - " . sprintf( __( 'Enter your reCAPTCHA site and secret keys in the %sreCAPTCHA Settings%s.', 'gravityforms' ), "<a href='?page=gf_settings&subview=recaptcha' target='_blank'>", '</a>' ) . '</div></div>';
					}

					$type_suffix = $type == 'invisible' ? 'invisible_' : '';
					$alt         = esc_attr__( 'An example of reCAPTCHA', 'gravityforms' );

					return "<div class='ginput_container'><img class='gfield_captcha' src='" . GFCommon::get_base_url() . "/images/captcha_{$type_suffix}{$theme}.jpg' alt='{$alt}' /></div>";
				}

				if ( empty( $this->site_key ) || empty( $this->secret_key ) ) {
					GFCommon::log_error( __METHOD__ . sprintf( '(): reCAPTCHA secret keys not saved in the reCAPTCHA Settings (%s). The reCAPTCHA field will always fail validation during form submission.', admin_url( 'admin.php' ) . '?page=gf_settings&subview=recaptcha' ) );
				}

				$stoken = '';

				if ( ! empty( $this->secret_key ) && ! empty( $secure_token ) && $this->use_stoken() ) {
					// The secure token is a deprecated feature of the reCAPTCHA API.
					// https://developers.google.com/recaptcha/docs/secure_token
					$secure_token = self::create_recaptcha_secure_token( $this->secret_key );
					$stoken = sprintf( 'data-stoken=\'%s\'', esc_attr( $secure_token ) );
				}

				$size  = '';
				$badge = '';

				if ( $type == 'invisible' ) {
					$size     = "data-size='invisible'";
					$badge    = $this->captchaBadge ? $this->captchaBadge : 'bottomright';
					$tabindex = -1;
				} else {
					$tabindex = GFCommon::$tab_index > 0 ? GFCommon::$tab_index++ : 0;
				}

				$output = "<div id='" . esc_attr( $field_id ) ."' class='ginput_container ginput_recaptcha' data-sitekey='" . esc_attr( $this->site_key ) . "' {$stoken} data-theme='" . esc_attr( $theme ) . "' data-tabindex='{$tabindex}' {$size} data-badge='{$badge}'></div>";

				$recaptcha_response = $this->get_posted_recaptcha_response();

				if ( ! $this->requires_encoding( $form, $recaptcha_response ) ) {
					return $output;
				}

				ob_start();
				?>
				<input
					type="hidden"
					name="g-recaptcha-response"
					value="<?php esc_attr_e( $this->get_encoded_recaptcha_response( $form, $recaptcha_response) ); ?>"
				/>
				<?php
				return $output .= ob_get_clean();
		}
	}

	/**
	 * Encode the reCAPTCHA response with details from Google.
	 *
	 * @since 2.4.24
	 *
	 * @param array  $form     The form data.
	 * @param string $response The posted response data.
	 *
	 * @return string
	 */
	private function get_encoded_recaptcha_response( $form, $response ) {
		if ( ! $this->response ) {
			return $response;
		}

		$this->response->token = $response;

		return GFCommon::openssl_encrypt( base64_encode( json_encode( $this->response ) ), $this->secret_key );
	}

	/**
	 * Decode and return the value of g-recaptcha-response field.
	 *
	 * The first time this method is called, the $response parameter will be the result of the reCAPTCHA callback,
	 * and decryption will fail. On subsequent requests, it should contain an encoded string of the reCAPTCHA response
	 * and the original token used to make the request.
	 *
	 * @since 2.4.24
	 *
	 * @param string $response An openssl encoded string, or the reCAPTCHA token on the very first call.
	 *
	 * @return string
	 */
	private function get_decoded_recaptcha_response( $response ) {
		$decoded_response = GFCommon::openssl_decrypt( $response, $this->get_secret_key() );

		if ( ! $decoded_response ) {
			return;
		}

		return json_decode( base64_decode( $decoded_response ) );
	}

	/**
	 * Check whether the reCAPTCHA response should be saved and encoded for validation on the final form page.
	 *
	 * @since 2.4.24
	 *
	 * @param array  $form               The form data.
	 * @param string $recaptcha_response The reCAPTCHA response.
	 *
	 * @return bool
	 */
	private function requires_encoding( $form, $recaptcha_response ) {
		return $recaptcha_response && ! $this->failed_validation && GFFormDisplay::get_current_page( rgar( $form, 'id' ) ) != $this->pageNumber && ! $this->is_on_last_page( $form );
	}

	/**
	 * Returns true if this CAPTCHA field is on the last page of the given form.
	 *
	 * @since 2.4.24
	 *
	 * @param array $form The form data.
	 *
	 * @return bool
	 */
	private function is_on_last_page( $form ) {
	    $pages = GFAPI::get_fields_by_type( $form, array( 'page' ) );

	    return count( $pages ) + 1 === (int) $this->pageNumber;
    }

	public function get_captcha() {
		if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
			return array();
		}

		$captcha = $this->get_simple_captcha();

		//If captcha folder does not exist and can't be created, return an empty captcha
		if ( ! wp_mkdir_p( $captcha->tmp_dir ) ) {
			return array();
		}

		$captcha->char_length = 5;
		switch ( $this->simpleCaptchaSize ) {
			case 'small' :
				$captcha->img_size        = array( 100, 28 );
				$captcha->font_size       = 18;
				$captcha->base            = array( 8, 20 );
				$captcha->font_char_width = 17;

				break;

			case 'large' :
				$captcha->img_size        = array( 200, 56 );
				$captcha->font_size       = 32;
				$captcha->base            = array( 18, 42 );
				$captcha->font_char_width = 35;
				break;

			default :
				$captcha->img_size        = array( 150, 42 );
				$captcha->font_size       = 26;
				$captcha->base            = array( 15, 32 );
				$captcha->font_char_width = 25;
				break;
		}

		if ( ! empty( $this->simpleCaptchaFontColor ) ) {
			$captcha->fg = $this->hex2rgb( $this->simpleCaptchaFontColor );
		}
		if ( ! empty( $this->simpleCaptchaBackgroundColor ) ) {
			$captcha->bg = $this->hex2rgb( $this->simpleCaptchaBackgroundColor );
		}

		$word     = $captcha->generate_random_word();
		$prefix   = mt_rand();
		$filename = $captcha->generate_image( $prefix, $word );
		$url      = RGFormsModel::get_upload_url( 'captcha' ) . '/' . $filename;
		$path     = $captcha->tmp_dir . $filename;

		if ( GFCommon::is_ssl() && strpos( $url, 'http:' ) !== false ) {
			$url = str_replace( 'http:', 'https:', $url );
		}

		return array( 'path' => $path, 'url' => $url, 'height' => $captcha->img_size[1], 'width' => $captcha->img_size[0], 'prefix' => $prefix );
	}

	public function get_simple_captcha() {
		$captcha          = new ReallySimpleCaptcha();
		$captcha->tmp_dir = RGFormsModel::get_upload_path( 'captcha' ) . '/';

		return $captcha;
	}

	public function get_math_captcha( $pos ) {
		if ( ! class_exists( 'ReallySimpleCaptcha' ) ) {
			return array();
		}

		$captcha = $this->get_simple_captcha();

		//If captcha folder does not exist and can't be created, return an empty captcha
		if ( ! wp_mkdir_p( $captcha->tmp_dir ) ) {
			return array();
		}

		$captcha->char_length = 1;
		if ( $pos == 1 || $pos == 3 ) {
			$captcha->chars = '0123456789';
		} else {
			$captcha->chars = '+';
		}

		switch ( $this->simpleCaptchaSize ) {
			case 'small' :
				$captcha->img_size        = array( 23, 28 );
				$captcha->font_size       = 18;
				$captcha->base            = array( 6, 20 );
				$captcha->font_char_width = 17;

				break;

			case 'large' :
				$captcha->img_size        = array( 36, 56 );
				$captcha->font_size       = 32;
				$captcha->base            = array( 10, 42 );
				$captcha->font_char_width = 35;
				break;

			default :
				$captcha->img_size        = array( 30, 42 );
				$captcha->font_size       = 26;
				$captcha->base            = array( 9, 32 );
				$captcha->font_char_width = 25;
				break;
		}

		if ( ! empty( $this->simpleCaptchaFontColor ) ) {
			$captcha->fg = $this->hex2rgb( $this->simpleCaptchaFontColor );
		}
		if ( ! empty( $this->simpleCaptchaBackgroundColor ) ) {
			$captcha->bg = $this->hex2rgb( $this->simpleCaptchaBackgroundColor );
		}

		$word     = $captcha->generate_random_word();
		$prefix   = mt_rand();
		$filename = $captcha->generate_image( $prefix, $word );
		$url      = RGFormsModel::get_upload_url( 'captcha' ) . '/' . $filename;
		$path     = $captcha->tmp_dir . $filename;

		if ( GFCommon::is_ssl() && strpos( $url, 'http:' ) !== false ) {
			$url = str_replace( 'http:', 'https:', $url );
		}

		return array( 'path' => $path, 'url' => $url, 'height' => $captcha->img_size[1], 'width' => $captcha->img_size[0], 'prefix' => $prefix );
	}

	private function hex2rgb( $color ) {
		if ( $color[0] == '#' ) {
			$color = substr( $color, 1 );
		}

		if ( strlen( $color ) == 6 ) {
			list( $r, $g, $b ) = array(
				$color[0] . $color[1],
				$color[2] . $color[3],
				$color[4] . $color[5],
			);
		} elseif ( strlen( $color ) == 3 ) {
			list( $r, $g, $b ) = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
			return false;
		}

		$r = hexdec( $r );
		$g = hexdec( $g );
		$b = hexdec( $b );

		return array( $r, $g, $b );
	}

	public function create_recaptcha_secure_token( $secret_key ) {

		// If required cypher is not available, skip
		if ( ! defined( 'MCRYPT_RIJNDAEL_128' ) ) {
			GFCommon::log_error( __METHOD__ . sprintf( '(): Legacy MCRYPT_RIJNDAEL_128 cypher not available on system. Generate new reCAPTCHA v2 keys (https://www.google.com/recaptcha/admin/create) and update your Gravity Forms reCAPTCHA Settings (%s) to resolve.', admin_url( 'admin.php' ) . '?page=gf_settings&subview=recaptcha' ) );

			return '';
		}

		$secret_key = substr( hash( 'sha1', $secret_key, true ), 0, 16 );
		$session_id = uniqid( 'recaptcha' );
		$ts_ms      = round( ( microtime( true ) - 1 ) * 1000 );

		//create json string
		$params    = array( 'session_id' => $session_id, 'ts_ms' => $ts_ms );
		$plaintext = json_encode( $params );
		GFCommon::log_debug( 'recaptcha token parameters: ' . $plaintext );

		//pad json string
		$pad    = 16 - ( strlen( $plaintext ) % 16 );
		$padded = $plaintext . str_repeat( chr( $pad ), $pad );

		//encrypt as 128
		$encrypted = GFCommon::openssl_encrypt( $padded, $secret_key, MCRYPT_RIJNDAEL_128 );

		$token = str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), $encrypted );
		GFCommon::log_debug( ' token being used is: ' . $token );

		return $token;
	}

	public function use_stoken() {
		// 'gform_recaptcha_keys_status' will be set to true if new keys have been entered
		return ! get_option( 'gform_recaptcha_keys_status', false );
	}

}

GF_Fields::register( new GF_Field_CAPTCHA() );
