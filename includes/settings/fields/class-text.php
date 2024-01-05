<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Text extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'text';

	/**
	 * Input type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $input_type = 'text';

	protected $step;

	protected $append;



	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {

		// Get value.
		$value = $this->get_value();

		// Prepare after_input.
		// Dynamic after_input content should use a callable to render.
		if ( isset( $this->after_input ) && is_callable( $this->after_input ) ) {
			$this->after_input = call_user_func( $this->after_input, $value, $this );
		}

		// Prepare markup.
		// Display description.
		$html = $this->get_description();

		$html .= sprintf(
			'<span class="%s"><input type="%s" name="%s_%s" value="%s" %s %s />%s %s</span>',
			esc_attr( $this->get_container_classes() ),
			esc_attr( $this->input_type ),
			esc_attr( $this->settings->get_input_name_prefix() ),
			esc_attr( $this->name ),
			$value ? esc_attr( htmlspecialchars( $value, ENT_QUOTES ) ) : '',
			$this->get_describer() ? sprintf( 'aria-describedby="%s"', $this->get_describer() ) : '',
			implode( ' ', $this->get_attributes() ),
			isset( $this->append ) ? sprintf( '<span class="gform-settings-field__text-append">%s</span>', esc_html( $this->append ) ) : '',
			$this->get_error_icon()
		);

		// Insert after input markup.

		$html .= isset( $this->after_input ) ? $this->after_input : '';

		return $html;

	}





	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Validate posted field value.
	 *
	 * @since 2.5
	 *
	 * @param string $value Posted field value.
	 */
	public function do_validation( $value ) {

		// If field is required and value is missing, set field error.
		if ( $this->required && rgblank( $value ) ) {
			$this->set_error( rgobj( $this, 'error_message' ) );
		}

		// Sanitize posted value.
		$sanitized_value = sanitize_text_field( $value );

		// If posted and sanitized values do not match, add field error.
		if ( $value !== $sanitized_value ) {

			// Prepare correction script.
			$double_encoded_safe_value = htmlspecialchars( htmlspecialchars( $sanitized_value, ENT_QUOTES ), ENT_QUOTES );
			$script                    = sprintf(
				'jQuery("input[name=\"%s_%s\"]").val(jQuery(this).data("safe"));',
				$this->settings->get_input_name_prefix(),
				$this->name
			);

			// Prepare message.
			$message = sprintf(
				"%s <a href='javascript:void(0);' onclick='%s' data-safe='%s'>%s</a>",
				esc_html__( 'The text you have entered is not valid. For security reasons, some characters are not allowed. ', 'gravityforms' ),
				htmlspecialchars( $script, ENT_QUOTES ),
				$double_encoded_safe_value,
				esc_html__( 'Fix it', 'gravityforms' )
			);

			// Set field error.
			$this->set_error( $message );

		}

	}

}

Fields::register( 'text', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Text' );
