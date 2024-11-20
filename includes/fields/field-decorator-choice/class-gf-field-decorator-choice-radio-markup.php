<?php

require_once( plugin_dir_path( __FILE__ ) . 'class-gf-field-decorator-choice.php' );

class GF_Field_Decorator_Choice_Radio_Markup extends ChoiceDecorator {

	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = $form['id'];
		$is_entry_detail = $this->field->is_entry_detail();
		$is_form_editor  = $this->field->is_form_editor();

		$id            = $this->field->id;
		$field_id      = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

		$image_style_classes = $this->get_field_classes( $form_id, $this->field );

		return sprintf( "<div class='ginput_container ginput_container_radio ginput_container_image_choice {$image_style_classes}'>%s</div>", $this->get_radio_choices( $value, $disabled_text, $form, $field_id ) );
	}

	public function get_radio_choices( $value, $disabled_text, $form, $field_id ) {
		$choices = '';

		if ( is_array( $this->field->choices ) ) {
			$is_entry_detail    = $this->field->is_entry_detail();
			$is_form_editor     = $this->field->is_form_editor();
			$is_admin           = $is_entry_detail || $is_form_editor;

			$field_choices      = $this->field->choices;
			$needs_other_choice = $this->field->enableOtherChoice;
			$editor_limited     = false;

			$choice_id = 0;
			$count     = 1;

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

			$choices .= sprintf( '<div class="gfield_radio" id="%s">', esc_attr( $field_id ) );

			foreach ( $field_choices as $choice ) {
				if ( rgar( $choice, 'isOtherChoice' ) ) {
					if ( ! $needs_other_choice ) {
						continue;
					}
					$needs_other_choice = false;
				}

				$choices .= $this->get_choice_html( $choice, $choice_id, $value, $disabled_text, $is_admin, $form );

				if ( $is_form_editor && $count >= $max_choices_count ) {
					$editor_limited = true;
					break;
				}

				$count ++;
			}

			if ( $needs_other_choice ) {
				$other_choice    = array(
					'text'          => GFCommon::get_other_choice_value( $this ),
					'value'         => 'gf_other_choice',
					'isSelected'    => false,
					'isOtherChoice' => true,
				);
				$field_choices[] = $other_choice;

				if ( ! $is_form_editor || ! $editor_limited ) {
					$choices .= $this->get_choice_html( $other_choice, $choice_id, $value, $disabled_text, $is_admin );
					$count ++;
				}
			}

			$choices .= '</div>';

			$total = sizeof( $field_choices );
			if ( $is_form_editor && ( $count < $total ) ) {
				$choices .= "<div class='gchoice_total'><span>" . sprintf( esc_html__( '%d of %d items shown. Edit choices to view all.', 'gravityforms' ), $count, $total ) . "</span></div>";
			}
		}

		/**
		 * Allows the HTML for multiple choices to be overridden.
		 *
		 * @since unknown
		 *
		 * @param string $choices The choices HTML.
		 * @param object $field   The current field object.
		 */
		return gf_apply_filters( array( 'gform_field_choices', $this->field->formId ), $choices, $this->field );
	}

	public function get_choice_html( $choice, &$choice_id, $value, $disabled_text, $is_admin, $form = null ) {
		$form_id = absint( $this->field->formId );

		if ( GFCommon::is_legacy_markup_enabled( $form_id ) ) {
			return '';
		}

		if ( $is_admin || $form_id == 0 ) {
			$id = $this->field->id . '_' . $choice_id ++;
		} else {
			$id = $form_id . '_' . $this->field->id . '_' . $choice_id ++;
		}

		$field_value = ! empty( $choice['value'] ) || $this->field->enableChoiceValue ? $choice['value'] : $choice['text'];

		if ( $this->field->enablePrice ) {
			$price       = rgempty( 'price', $choice ) ? 0 : GFCommon::to_number( rgar( $choice, 'price' ) );
			$field_value .= '|' . $price;
		}

		if ( rgblank( $value ) && rgget( 'view' ) != 'entry' ) {
			$checked = rgar( $choice, 'isSelected' ) ? "checked='checked'" : '';
		} else {
			$checked = GFFormsModel::choice_value_match( $this->field, $choice, $value ) ? "checked='checked'" : '';
		}

		$tabindex = $this->field->get_tabindex();

		$image                  = $this->get_image_markup( $choice, $id, $choice_id, $form );
		$image_aria_describedby = 'gchoice_image_' . $id;

		// Handle 'other' choice.
		$other = '';
		if ( $this->field->enableOtherChoice && rgar( $choice, 'isOtherChoice' ) ) {
			$input_disabled_text = $disabled_text;

			if ( $value == 'gf_other_choice' && rgpost( "input_{$this->field->id}_other" ) ) {
				$other_value = rgpost( "input_{$this->field->id}_other" );
			} elseif ( ! empty( $value ) && ! GFFormsModel::choices_value_match( $this->field, $this->field->choices, $value ) ) {
				$other_value = $value;
				$value       = 'gf_other_choice';
				$checked     = "checked='checked'";
			} else {
				if ( ! $input_disabled_text ) {
					$input_disabled_text = "disabled='disabled'";
				}
				$other_value = empty( $choice['text'] ) ? GFCommon::get_other_choice_value( $this->field ) : $choice['text'];
			}

			$other = "<br /><input id='input_{$this->field->formId}_{$this->field->id}_other' class='gchoice_other_control' name='input_{$this->field->id}_other' type='text' value='" . esc_attr( $other_value ) . "' aria-label='" . esc_attr__( 'Other Choice, please specify', 'gravityforms' ) . "' $tabindex $input_disabled_text />";
		}

		// Handling of input/image aria-describedby
		$aria_describedby = '';
		if ( $this->add_aria_description( $checked, $choice_id ) ) {
			$image_aria_describedby = $image_aria_describedby ? array( $image_aria_describedby ) : array();
			$aria_describedby       = $this->get_aria_describedby( $image_aria_describedby );
		} else if ( $image_aria_describedby ) {
			$aria_describedby = sprintf( 'aria-describedby="%s"', $image_aria_describedby );
		}

		$choice_value = esc_attr( $field_value );

		$choice_markup = "<div class='gchoice gchoice_{$id}'>
			<span class='gfield-image-choice-wrapper-outer'>
				{$image}
				<span class='gfield-image-choice-wrapper-inner'>
					<input class='gfield-choice-input' name='input_{$this->field->id}' type='radio' value='{$choice_value}' {$checked} id='choice_{$id}' onchange='gformToggleRadioOther( this )' {$tabindex} {$disabled_text} {$aria_describedby}/>
					<label for='choice_{$id}' id='label_{$id}' class='gform-field-label gform-field-label--type-inline'>
						{$choice['text']}
					</label>
				</span>
				{$other}
			</span>
		</div>";

		/**
		 * Allows the HTML for a specific choice to be overridden.
		 *
		 * @since 1.9.6
		 * @since 1.9.12 Added the field specific version.
		 * @since 2.4.17 Moved from GF_Field_Radio::get_radio_choices().
		 *
		 * @param string         $choice_markup The choice HTML.
		 * @param array          $choice        The choice properties.
		 * @param GF_Field_Radio $field         The current field object.
		 * @param string         $value         The current field value.
		 */
		return gf_apply_filters( array( 'gform_field_choice_markup_pre_render', $this->field->formId, $this->field->id ), $choice_markup, $choice, $this->field, $value );
	}

}
