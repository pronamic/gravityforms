<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class ChoiceDecorator {

	/**
	 * @var GF_Field
	 */
	protected $field;

	public function __construct( $field ) {
		$this->field = $field;
	}

	public function __call( $name, $args ) {
		return call_user_func_array( array( $this->field, $name ), $args );
	}

	/**
	 * Get the style classes for the image choice field.
	 *
	 * @since 2.9
	 *
	 * @param $form_id
	 * @param $field_id
	 *
	 * @return string
	 */
	public function get_field_classes( $form_id, $field ) {
		// Choice label visibility class
		$choice_label_visibility = GF_Field_Image_Choice::get_image_choice_label_visibility_setting( $field );
		$label_visibility_class  = "ginput_container_image_choice--label-{$choice_label_visibility}";

		// Choice input visibility class
		$choice_input_visibility = GF_Field_Image_Choice::get_image_choice_input_visibility_setting( $field );
		$input_visibility        = ( $choice_input_visibility === 'show' ) && ( $choice_label_visibility === 'show' ) ? 'show' : 'hide';
		$input_visibility_class  = "ginput_container_image_choice--input-{$input_visibility}";

		return $label_visibility_class . ' ' . $input_visibility_class;
	}

	/**
	 * Get the image markup for a choice field.
	 *
	 * @since 2.9
	 *
	 * @param $choice
	 * @param $choice_id
	 * @param $choice_number
	 * @param $form
	 *
	 * @return string
	 */
	public function get_image_markup( $choice, $choice_id, $choice_number, $form ) {
		$image_aria_describedby = 'gchoice_image_' . $choice_id;

		if ( ! empty( $choice['attachment_id'] ) ) {
			$image_alt  = get_post_meta( $choice['attachment_id'], '_wp_attachment_image_alt', true );
			$image_alt  = ! empty( $image_alt ) ? $image_alt : sprintf( '%s %d', __( 'Image for choice number', 'gravityforms' ), $choice_number );
			$image_size = isset( $form['styles'] ) && rgar( $form['styles'], 'inputImageChoiceSize' ) ? $form['styles']['inputImageChoiceSize'] : 'md';

			$image = wp_get_attachment_image(
				$choice['attachment_id'],
				'gform-image-choice-' . $image_size,
				false,
				array(
					'class'   => 'gfield-choice-image',
					'alt'     => $image_alt,
					'id'      => $image_aria_describedby,
					'loading' => 'false',
				)
			);
		} else {
			$image = sprintf(
				'<span class="gfield-choice-image-no-image" id="%s"><span>%s</span></span>',
				$image_aria_describedby,
				sprintf(
					'%s %d %s',
					__( 'Choice number', 'gravityforms' ),
					$choice_number,
					__( 'does not have an image', 'gravityforms' )
				)
			);
		}

		return sprintf( '<div class="gfield-choice-image-wrapper">%s</div>', $image );
	}
}

GFCommon::glob_require_once( '/includes/fields/field-decorator-choice/class-gf-field-decorator-choice-*.php' );
