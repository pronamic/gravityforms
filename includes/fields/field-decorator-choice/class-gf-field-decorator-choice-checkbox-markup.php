<?php

require_once( plugin_dir_path( __FILE__ ) . 'class-gf-field-decorator-choice.php' );

class GF_Field_Decorator_Choice_Checkbox_Markup extends ChoiceDecorator {

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id = absint( $form['id'] );

		$is_entry_detail = $this->field->is_entry_detail();
		$is_form_editor  = $this->field->is_form_editor();

		$id            = $this->field->id;
		$field_id      = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';
		$limit_message = $this->get_limit_message();

		$image_style_classes = $this->get_field_classes( $form_id, $this->field );

		// Get checkbox choices markup.
		$choices_markup = $this->get_checkbox_choices( $value, $disabled_text, $form, $field_id );

		return sprintf(
			"<div class='ginput_container ginput_container_checkbox ginput_container_image_choice %s'>%s%s</div>",
			$image_style_classes,
			$limit_message,
			$choices_markup
		);
	}

	public function get_checkbox_choices( $value, $disabled_text, $form, $field_id ) {
		$form_id = absint( $form['id'] );

		if ( GFCommon::is_legacy_markup_enabled( $form_id ) ) {
			return '';
		}

		$choices         = '';
		$is_entry_detail = $this->field->is_entry_detail();
		$is_form_editor  = $this->field->is_form_editor();

		if ( is_array( $this->field->choices ) ) {

			$choice_number = 1;
			$count         = 1;

			/**
			 * A filter that allows for the setting of the maximum number of choices shown in
			 * the form editor for choice based fields (radio, checkbox, image, and multiple choice).
			 *
			 * @since 2.9
			 *
			 * @param int    $max_choices_visible_count The default number of choices visible is 8.
			 * @param object $field                     The current field object.
			 */
			$max_choices_count = gf_apply_filters( array( 'gform_field_choices_max_count_visible', $this->field->formId ), 8, $this->field );

			$choices .= sprintf( '<div class="gfield_checkbox" id="%s">', esc_attr( $field_id ) );

			foreach ( $this->field->choices as $choice ) {
				// Hack to skip numbers ending in 0, so that 5.1 doesn't conflict with 5.10.
				if ( $choice_number % 10 == 0 ) {
					$choice_number ++;
				}

				// Prepare input ID.
				if ( $is_entry_detail || $is_form_editor || $form_id == 0 ) {
					$id = $this->field->id . '_' . $choice_number;
				} else {
					$id = $form_id . '_' . $this->field->id . '_' . $choice_number;
				}

				// Handling of input/image aria-describedby
				$image                  = $this->get_image_markup( $choice, $id, $choice_number, $form );
				$image_aria_describedby = 'gchoice_image_' . $id;
				$aria_describedby       = '';

				if ( $choice_number === 1 ) {
					$image_aria_describedby = $image_aria_describedby ? array( $image_aria_describedby ) : array();
					$aria_describedby       = $this->field->get_choice_aria_describedby( $form_id, $image_aria_describedby );
				} elseif ( $image_aria_describedby ) {
					$aria_describedby = sprintf( 'aria-describedby="%s"', $image_aria_describedby );
				}

				$choice_number ++;

				// Prepare choice attributes.
				$input_id     = $this->get_input_id_from_choice_key( $choice['key'] );
				$checked      = $this->field->get_checked_attribute( $choice, $value, $input_id, $form_id );
				$tabindex     = $this->field->get_tabindex();
				$choice_value = $choice['value'];

				if ( $this->field->enablePrice ) {
					$price        = rgempty( 'price', $choice ) ? 0 : GFCommon::to_number( rgar( $choice, 'price' ) );
					$choice_value .= '|' . $price;
				}

				$choice_value  = esc_attr( $choice_value );
				$choice_markup = "<div class='gchoice gchoice_{$id}'>
					<span class='gfield-image-choice-wrapper-outer'>
						{$image}
						<span class='gfield-image-choice-wrapper-inner'>
							<input class='gfield-choice-input' name='input_{$input_id}' type='checkbox'  value='{$choice_value}' {$checked} id='choice_{$id}' {$tabindex} {$disabled_text} {$aria_describedby}/>
							<label for='choice_{$id}' id='label_{$id}' class='gform-field-label gform-field-label--type-inline'>
								{$choice['text']}
							</label>
						</span>
					</span>
				</div>";

				/**
				 * Override the default choice markup used when rendering radio button, checkbox and drop down type fields.
				 *
				 * @since 1.9.6
				 *
				 * @param string $choice_markup The string containing the choice markup to be filtered.
				 * @param array  $choice        An associative array containing the choice properties.
				 * @param object $field         The field currently being processed.
				 * @param string $value         The value to be selected if the field is being populated.
				 */
				$choices .= gf_apply_filters( array(
					'gform_field_choice_markup_pre_render',
					$this->field->formId,
					$this->field->id
				), $choice_markup, $choice, $this->field, $value );

				$is_admin = $is_entry_detail || $is_form_editor;

				if ( $is_admin && rgget( 'view' ) != 'entry' && $count >= $max_choices_count ) {
					break;
				}

				$count ++;
			}

			$choices .= '</div>';

			$total = sizeof( $this->field->choices );

			if ( $count < $total ) {
				$choices .= "<div class='gchoice_total'><span>"
	                . sprintf( esc_html__( '%d of %d items shown. Edit choices to view all.', 'gravityforms' ), $count, $total ) .
	            "</span></div>";
			}
		}

		/**
		 * Modify the checkbox items before they are added to the checkbox list.
		 *
		 * @since Unknown
		 *
		 * @param string $choices The string containing the choices to be filtered.
		 * @param object $field   The field currently being processed.
		 */
		return gf_apply_filters( array( 'gform_field_choices', $this->field->formId ), $choices, $this->field );

	}

}
