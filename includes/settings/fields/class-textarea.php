<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;
use GFCommon;

defined( 'ABSPATH' ) || die();

class Textarea extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'textarea';

	/**
	 * Allow HTML in field value.
	 *
	 * @since 2.5
	 *
	 * @var bool
	 */
	public $allow_html = false;


	/**
	 * Initialize as Rich Text Editor.
	 *
	 * @since 2.5
	 *
	 * @var bool
	 */
	public $use_editor = false;

	/**
	 * Number of rows.
	 *
	 * @since 2.5
	 *
	 * @var int
	 */
	public $rows;

	/**
	 * Editor height.
	 *
	 * @since 2.5
	 *
	 * @var int
	 */
	public $editor_height;

	/**
	 * Initialize Textarea field.
	 *
	 * @since 2.5
	 *
	 * @param array                                $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		parent::__construct( $props, $settings );

		// Set default row count.
		if ( ! isset( $this->rows ) ) {
			$this->rows = 4;
		}

		// Set default editor height.
		if ( ! isset( $this->editor_height ) ) {
			$this->editor_height = 200;
		}

	}

	private function get_editor_id() {
		return esc_attr( $this->settings->get_input_name_prefix() ) . '_' . esc_attr( $this->name );
	}


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

		if ($value === null) {
			$value = '';
		}

		// Initialize rich text editor.
		if ( $this->use_editor ) {

			// Create editor container.
			$html = sprintf(
				'<span class="mt-gaddon-editor mt-%s_%s"></span>',
				esc_attr( $this->settings->get_input_name_prefix() ),
				esc_attr( $this->name )
			);

			// Display description.
			$html .= $this->get_description();

			$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

			// Insert editor.
			ob_start();
			wp_editor(
				$value,
				$this->get_editor_id(),
				array(
					'autop'         => false,
					'editor_class'  => $this->get_editor_class(),
					'editor_height' => $this->editor_height,
				)
			);
			$html .= ob_get_contents();
			ob_end_clean();

			// If field failed validation, add error icon.
			$html .= $this->get_error_icon();

			$html .= '</span>';

		} else {

			$escaped_value = $value !== null ? esc_textarea( $value ) : '';

			// Prepare markup.
			// Display description.
			$html = $this->get_description();

			$html .= sprintf(
				'<span class="%s"><textarea name="%s_%s" %s %s>%s</textarea>%s</span>',
				esc_attr( $this->get_container_classes() ),
				esc_attr( $this->settings->get_input_name_prefix() ),
				esc_attr( $this->name ),
				$this->get_describer() ? sprintf( 'aria-describedby="%s"', $this->get_describer() ) : '',
				implode( ' ', $this->get_attributes() ),
				$escaped_value,
				// If field failed validation, add error icon.
				$this->get_error_icon()
			);

		}

		return $html;

	}

	/**
	 * Get the CSS classes for the rich text editor.
	 *
	 * @since 2.6
	 *
	 * @return string
	 */
	public function get_editor_class() {
		$editor_class = ! is_null( $this->class ) ? $this->class : 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right';

		// If a rich text editor has custom classes and merge tag support, make sure it includes the 'mt-manual_position' class to prevent layout problems.
		$classes = explode( ' ', $editor_class );
		if ( in_array( 'merge-tag-support', $classes ) && ! in_array( 'mt-manual_position', $classes ) ) {
			$editor_class .= ' mt-manual_position';
		}

		return $editor_class;
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

		$sanitized_value = $this->get_sanitized_value( $value );

		// If posted and sanitized values match, we're done here.
		if ( $value === $sanitized_value ) {
			return;
		}

		// Failed validation. Prepare field error.
		$message = sprintf(
			"%s <a href='javascript:void(0);' onclick='%s' data-safe='%s'>%s</a>",
			esc_html__( 'The text you have entered is not valid. For security reasons, some characters are not allowed. ', 'gravityforms' ),
			$this->get_validation_correction_script(),
			htmlspecialchars( $sanitized_value, ENT_QUOTES ),
			esc_html__( 'Fix it', 'gravityforms' )
		);

		// Set field error.
		$this->set_error( $message );
	}

	/**
	 * Gets the sanitized value of the user input.
	 *
	 * Textarea fields must explicitly opt in to allowing HTML, either by indicating the editor type or by passing the
	 * allow_html setting. In those cases, we run the content through wp_kses based on user permissions (users with
	 * the unfiltered_html capability can enter in raw html).
	 *
	 * By default, HTML is not allowed, so we simply sanitize the field, even if the user has permission to include
	 * unfiltered html.
	 *
	 * @since 2.5.2
	 *
	 * @param string $value The input value to sanitized.
	 *
	 * @return string
	 */
	private function get_sanitized_value( $value ) {
		add_filter( 'safe_style_css', array( $this, 'disable_style_attr_parsing' ), 10, 1 );
		$sanitized = ( $this->use_editor || $this->allow_html ) ? GFCommon::maybe_wp_kses( $value ) : sanitize_textarea_field( $value );
		remove_filter( 'safe_style_css', array( $this, 'disable_style_attr_parsing' ), 10 );

		return $sanitized;
	}

	public function disable_style_attr_parsing( $allowed ) {
		return array();
	}

	/**
	 * Get the correction script for the field.
	 *
	 * @since 2.5.2
	 *
	 * @return string
	 */
	protected function get_validation_correction_script() {
		$script = sprintf(
			'jQuery("textarea[name=\"%s_%s\"]").val(jQuery(this).data("safe"));',
			$this->settings->get_input_name_prefix(),
			$this->name
		);

		return htmlspecialchars( $script, ENT_QUOTES );
	}

}

Fields::register( 'textarea', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Textarea' );
