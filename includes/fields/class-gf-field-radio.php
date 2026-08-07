<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

require_once( plugin_dir_path( __FILE__ ) . 'field-decorator-choice/class-gf-field-decorator-choice-radio-markup.php' );

class GF_Field_Radio extends GF_Field {

	public $type = 'radio';

	/**
	 * Indicates if this field supports state validation.
	 *
	 * @since 2.5.11
	 *
	 * @var bool
	 */
	protected $_supports_state_validation = true;

	public function get_form_editor_field_title() {
		return esc_attr__( 'Radio Buttons', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to select one option from a list.', 'gravityforms' );
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
		return 'gform-icon--radio-button';
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'choices_setting',
			'rules_setting',
			'visibility_setting',
			'duplicate_setting',
			'description_setting',
			'css_class_setting',
			'other_choice_setting',
			'display_choices_columns_setting',
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Determines the if the field has been tampered with before submission.
	 *
	 * @since 3.0
	 *
	 * @param string|array $value The submitted value.
	 *
	 * @return bool
	 */
	public function is_state_valid( $value ) {
		if ( $this->enableOtherChoice && rgpost( "is_submit_{$this->formId}" ) && $this->is_other_choice_selected() ) {
			return true;
		}

		return parent::is_state_valid( $value );
	}

	/**
	 * Indicates if state validation should be skipped if the submitted value is blank.
	 *
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function skip_state_validation_if_blank( $key ) {
		return true;
	}

	/**
	 * Prepares the value that will be hashed on form display as part of the state.
	 *
	 * @since 3.0
	 *
	 * @param string|array $value The default value.
	 *
	 * @return null|array
	 */
	public function get_values_for_state_hash( $value ) {
		$id = $this->id;

		return array(
			$id => $this->get_choices_for_state_hash(),
		);
	}

	public function validate( $value, $form ) {
		if ( $this->isRequired && $this->enableOtherChoice && $this->is_other_choice_selected() ) {
			$is_empty = $value === '' || $value === null;
			if ( $is_empty || strtolower( $value ) == strtolower( GFCommon::get_other_choice_value( $this ) ) ) {
				$this->failed_validation  = true;
				$this->validation_message = empty( $this->errorMessage ) ? esc_html__( 'This field is required.', 'gravityforms' ) : $this->errorMessage;
			}
		}
	}

	/**
	 * Returns the value to use when the state is validated.
	 *
	 * @since 3.0
	 *
	 * @param string|array $value The submitted value.
	 *
	 * @return array
	 */
	public function get_value_for_state_validation( $value ) {
		if ( $this->enableOtherChoice && $value == 'gf_other_choice' ) {
			$item_index = $this->get_context_property( 'itemIndex' );
			$value      = $this->get_other_choice_value_from_post( $item_index );
		}

		return parent::get_value_for_state_validation( $value );
	}

	public function get_first_input_id( $form ) {
		return '';
	}

	/**
	 * Returns the HTML tag for the field container.
	 *
	 * @since 2.5
	 *
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container_tag( $form ) {

		if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
			return parent::get_field_container_tag( $form );
		}

		return 'fieldset';

	}

	public function get_field_input( $form, $value = '', $entry = null ) {

		if ( $this->type == 'image_choice' ) {
			$this->image_markup = new GF_Field_Decorator_Choice_Radio_Markup( $this );
			return $this->image_markup->get_field_input( $form, $value, $entry );
		}

		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id            = $this->id;
		$field_id      = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';
		$tag           = GFCommon::is_legacy_markup_enabled( $form ) ? 'ul' : 'div';

		return sprintf( "<div class='ginput_container ginput_container_radio'><{$tag} class='gfield_radio' id='%s'>%s</{$tag}></div>", $field_id, $this->get_radio_choices( $value, $disabled_text, $form_id ) );

	}

	public function get_radio_choices( $value = '', $disabled_text = '', $form_id = 0 ) {
		$choices = '';

		if ( is_array( $this->choices ) ) {
			$is_entry_detail    = $this->is_entry_detail();
			$is_form_editor     = $this->is_form_editor();
			$is_admin           = $is_entry_detail || $is_form_editor;

			$field_choices      = $this->choices;
			$needs_other_choice = $this->enableOtherChoice;
			$editor_limited     = false;

			$choice_id = 0;
			$count     = 1;
			// Determine max choices to show in the form editor if Display in columns setting is enabled.
			$max_choices = $this->enableDisplayInColumns === true || ( isset( $this->choiceAlignment ) && $this->choiceAlignment === 'columns' ) ? 10 : 5;

			/**
			 * A filter that allows for the setting of the maximum number of choices shown in
			 * the form editor for choice based fields (radio, checkbox, image, and multiple choice).
			 *
			 * @since 2.9
			 *
			 * @param int    $max_choices_visible_count The default number of choices visible is 5.
			 * @param object $field                     The current field object.
			 */
			$max_choices_count = gf_apply_filters( array( 'gform_field_choices_max_count_visible', $form_id ), $max_choices, $this );

			$tag = GFCommon::is_legacy_markup_enabled( $form_id ) ? 'li' : 'div';

			foreach ( $field_choices as $choice ) {
				if ( rgar( $choice, 'isOtherChoice' ) ) {
					if ( ! $needs_other_choice ) {
						continue;
					}
					$needs_other_choice = false;
				}

				$choices .= $this->get_choice_html( $choice, $choice_id, $value, $disabled_text, $is_admin );

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

			$total = sizeof( $field_choices );
			if ( $is_form_editor && ( $count < $total ) ) {
				$choices .= "<{$tag} class='gchoice_total'><span>" . sprintf( esc_html__( '%d of %d items shown. Edit choices to view all.', 'gravityforms' ), $count, $total ) . "</span></{$tag}>";
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
		return gf_apply_filters( array( 'gform_field_choices', $this->formId ), $choices, $this );
	}

	/**
	* Determine if we should add the aria description to a radio input.
	*
	* @since 2.5
	*
	* @param string $checked      The checked attribute or a blank string.
	* @param int    $choice_id    The choice number.
	*
	* @return string
	*/
	public function add_aria_description( $checked, $choice_id ) {

		// Determine if any choices are pre-selected.
		foreach ( $this['choices'] as $choice ) {
			$is_any_selected = rgar( $choice, 'isSelected' );
			if ( $is_any_selected ) {
				break;
			}
		}

		// Return true if any choices are pre-selected, or if no choices are pre-selected and this is the first choice.
		return ( ! $is_any_selected && $choice_id === 1 ) || $checked;

	}

	/**
	 * Returns the choice HTML.
	 *
	 * @since 2.4.17
	 * @since 2.7 Added `gchoice_other_control` class to Other choice text input.
	 *
	 * @param array  $choice        The choice properties.
	 * @param int    &$choice_id    The choice number.
	 * @param string $value         The current field value.
	 * @param string $disabled_text The disabled attribute or an empty string.
	 * @param bool   $is_admin      Indicates if this is the form editor or entry detail page.
	 *
	 * @return string
	 */
	public function get_choice_html( $choice, &$choice_id, $value, $disabled_text, $is_admin ) {
		$form_id = absint( $this->formId );

		if ( GFCommon::is_legacy_markup_enabled( $form_id ) ) {
			return $this->get_legacy_choice_html( $choice, $choice_id, $value, $disabled_text, $is_admin );
		}

		if ( $is_admin || $form_id == 0 ) {
			$id = $this->id . '_' . $choice_id ++;
		} else {
			$id = $form_id . '_' . $this->id . '_' . $choice_id ++;
		}

		$field_value = ! empty( $choice['value'] ) || $this->enableChoiceValue ? $choice['value'] : $choice['text'];

		if ( $this->enablePrice ) {
			$price       = rgempty( 'price', $choice ) ? 0 : GFCommon::to_number( rgar( $choice, 'price' ) );
			$field_value .= '|' . $price;
		}

		if ( rgblank( $value ) && rgget( 'view' ) != 'entry' ) {
			$checked = rgar( $choice, 'isSelected' ) ? "checked='checked'" : '';
		} else {
			$checked = GFFormsModel::choice_value_match( $this, $choice, $value ) ? "checked='checked'" : '';
		}

		$aria_describedby = $this->add_aria_description( $checked, $choice_id ) ? $this->get_aria_describedby() : '';

		$tabindex = $this->get_tabindex();
		$label    = sprintf( "<label for='choice_%s' id='label_%s' class='gform-field-label gform-field-label--type-inline'>%s</label>", $id, $id, $choice['text'] );

		// Handle 'other' choice.
		if ( $this->enableOtherChoice && rgar( $choice, 'isOtherChoice' ) ) {
			$input_disabled_text = $disabled_text;

			$item_index = $this->get_context_property( 'itemIndex' );

			$posted_other = rgpost( "input_{$this->id}_other" );
			if ( $value == 'gf_other_choice' && is_string( $posted_other ) && $item_index !== '{ID}' ) {
				$other_value = $this->get_other_choice_value_from_post( $item_index );
			} elseif ( $value !== '' && $value !== null && ! GFFormsModel::choices_value_match( $this, $this->choices, $value ) ) {
				$other_value = $value;
				$value       = 'gf_other_choice';
				$checked     = "checked='checked'";
			} else {
				if ( ! $input_disabled_text ) {
					$input_disabled_text = "disabled='disabled'";
				}
				$other_value = empty( $choice['text'] ) ? GFCommon::get_other_choice_value( $this ) : $choice['text'];
			}

			$label .= "<br /><input id='input_{$this->formId}_{$this->id}_other' class='gchoice_other_control' name='input_{$this->id}_other' type='text' value='" . esc_attr( $other_value ) . "' aria-label='" . esc_attr__( 'Other Choice, please specify', 'gravityforms' ) . "' $tabindex $input_disabled_text />";
		}

		$choice_markup = sprintf( "
			<div class='gchoice gchoice_$id'>
					<input class='gfield-choice-input' name='input_%d' type='radio' value='%s' %s id='choice_%s' onchange='gformToggleRadioOther( this )' %s $tabindex %s />
					%s
			</div>",
			$this->id, esc_attr( $field_value ), $checked, $id, $aria_describedby, $disabled_text, $label
		);

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
		return gf_apply_filters( array( 'gform_field_choice_markup_pre_render', $this->formId, $this->id ), $choice_markup, $choice, $this, $value );
	}

	/**
	 * Returns the choice HTML.
	 *
	 * @since 2.5
	 *
	 * @param array  $choice        The choice properties.
	 * @param int    &$choice_id    The choice number.
	 * @param string $value         The current field value.
	 * @param string $disabled_text The disabled attribute or an empty string.
	 * @param bool   $is_admin      Indicates if this is the form editor or entry detail page.
	 *
	 * @return string
	 */
	public function get_legacy_choice_html( $choice, &$choice_id, $value, $disabled_text, $is_admin ) {
		$form_id = absint( $this->formId );

		if ( $is_admin || $form_id == 0 ) {
			$id = $this->id . '_' . $choice_id ++;
		} else {
			$id = $form_id . '_' . $this->id . '_' . $choice_id ++;
		}

		$field_value = ! empty( $choice['value'] ) || $this->enableChoiceValue ? $choice['value'] : $choice['text'];

		if ( $this->enablePrice ) {
			$price       = rgempty( 'price', $choice ) ? 0 : GFCommon::to_number( rgar( $choice, 'price' ) );
			$field_value .= '|' . $price;
		}

		if ( rgblank( $value ) && rgget( 'view' ) != 'entry' ) {
			$checked = rgar( $choice, 'isSelected' ) ? "checked='checked'" : '';
		} else {
			$checked = GFFormsModel::choice_value_match( $this, $choice, $value ) ? "checked='checked'" : '';
		}

		$tabindex    = $this->get_tabindex();
		$label       = sprintf( "<label for='choice_%s' id='label_%s' class='gform-field-label gform-field-label--type-inline'>%s</label>", $id, $id, $choice['text'] );
		$input_focus = '';

		// Handle 'other' choice.
		if ( $this->enableOtherChoice && rgar( $choice, 'isOtherChoice' ) ) {
			$other_default_value = empty( $choice['text'] ) ? GFCommon::get_other_choice_value( $this ) : $choice['text'];

			$onfocus = ! $is_admin ? 'jQuery(this).prev("input")[0].click(); if(jQuery(this).val() == "' . $other_default_value . '") { jQuery(this).val(""); }' : '';
			$onblur  = ! $is_admin ? 'if(jQuery(this).val().replace(" ", "") == "") { jQuery(this).val("' . $other_default_value . '"); }' : '';

			$input_focus  = ! $is_admin ? "onfocus=\"jQuery(this).next('input').focus();\"" : '';
			$value_exists = GFFormsModel::choices_value_match( $this, $this->choices, $value );

			$posted_other = rgpost( "input_{$this->id}_other" );
			if ( $value == 'gf_other_choice' && is_string( $posted_other ) ) {
				$item_index  = $this->get_context_property( 'itemIndex' );
				$other_value = $this->get_other_choice_value_from_post( $item_index );
			} elseif ( ! $value_exists && $value !== '' && $value !== null ) {
				$other_value = $value;
				$value       = 'gf_other_choice';
				$checked     = "checked='checked'";
			} else {
				$other_value = $other_default_value;
			}

			$label = "<input class='small' id='input_{$this->formId}_{$this->id}_other' name='input_{$this->id}_other' type='text' value='" . esc_attr( $other_value ) . "' aria-label='" . esc_attr__( 'Other', 'gravityforms' ) . "' onfocus='$onfocus' onblur='$onblur' $tabindex $disabled_text />";
		}

		$choice_markup = sprintf( "
			<li class='gchoice gchoice_$id'>
				<input name='input_%d' type='radio' value='%s' %s id='choice_%s' $tabindex %s %s />
				%s
			</li>",
			$this->id, esc_attr( $field_value ), $checked, $id, $disabled_text, $input_focus, $label
		);

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
		return gf_apply_filters( array( 'gform_field_choice_markup_pre_render', $this->formId, $this->id ), $choice_markup, $choice, $this, $value );
	}

	public function get_value_default() {
		return $this->is_form_editor() ? $this->defaultValue : GFCommon::replace_variables_prepopulate( $this->defaultValue );
	}

	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		$value = $this->get_input_value_submission( 'input_' . $this->id, $this->inputName, $field_values, $get_from_post_global_var );
		if ( $value == 'gf_other_choice' ) {
			$item_index = $this->get_context_property( 'itemIndex' );
			$value      = $this->get_other_choice_value_from_post( $item_index );
		}

		return $value;
	}

	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		return $this->get_selected_choice_output( $value, rgar( $entry, 'currency' ) );
	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
	 *
	 * @since 1.9
	 * @since 2.9.29 Changed the second parameter $currency (string) to $entry (array).
	 *
	 * @param string|array $value    The field value.
	 * @param array        $entry    The entry.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string       $media    The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $entry = array(), $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( $this->type === 'post_category' ) {
			$value = GFCommon::prepare_post_category_value( $value, $this, 'entry_detail' );
		}

		return $this->get_selected_choice_output( $value, rgar( $entry, 'currency' ), $use_text );
	}

	/**
	 * Gets merge tag values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCommon::to_money()
	 * @uses GFCommon::format_post_category()
	 * @uses GFFormsModel::is_field_hidden()
	 * @uses GFFormsModel::get_choice_text()
	 * @uses GFCommon::format_variable_value()
	 * @uses GFCommon::implode_non_blank()
	 *
	 * @param array|string $value      The value of the input.
	 * @param string       $input_id   The input ID to use.
	 * @param array        $entry      The Entry Object.
	 * @param array        $form       The Form Object
	 * @param string       $modifier   The modifier passed.
	 * @param array|string $raw_value  The raw value of the input.
	 * @param bool         $url_encode If the result should be URL encoded.
	 * @param bool         $esc_html   If the HTML should be escaped.
	 * @param string       $format     The format that the value should be.
	 * @param bool         $nl2br      If the nl2br function should be used.
	 *
	 * @return string The processed merge tag.
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {
		$modifiers       = $this->get_modifiers();
		$use_value       = in_array( 'value', $modifiers );
		$format_currency = ! $use_value && in_array( 'currency', $modifiers );
		$use_price       = $format_currency || ( ! $use_value && in_array( 'price', $modifiers ) );
		$image_url 	     = in_array( 'img_url', $modifiers );

		if ( is_array( $raw_value ) && (string) intval( $input_id ) != $input_id ) {
			$items = array( $input_id => $value ); // Float input Ids. (i.e. 4.1 ). Used when targeting specific checkbox items.
		} elseif ( is_array( $raw_value ) ) {
			$items = $raw_value;
		} else {
			$items = array( $input_id => $raw_value );
		}

		$ary = array();

		foreach ( $items as $input_id => $item ) {
			switch (true) {
				case $use_value:
					list( $val, $price ) = rgexplode( '|', $item, 2, true );
					break;

				case $use_price:
					list( $name, $val ) = rgexplode( '|', $item, 2, true );
					if ( $format_currency ) {
						$val = GFCommon::to_money( $val, rgar( $entry, 'currency' ) );
					}
					break;

				case $image_url:
					$image_choice = new GF_Field_Image_Choice( $this );
					$val = $image_choice->get_merge_tag_img_url( $raw_value, $input_id, $entry, $form, $this );
					break;

				case $this->type == 'post_category':
					$use_id     = strtolower( $modifier ) == 'id';
					$item_value = GFCommon::format_post_category( $item, $use_id );
					$val = RGFormsModel::is_field_hidden( $form, $this, array(), $entry ) ? '' : $item_value;
					break;

				default:
					$val = RGFormsModel::is_field_hidden( $form, $this, array(), $entry ) ? '' : RGFormsModel::get_choice_text( $this, $raw_value, $input_id );
					break;
			}

			$ary[] = GFCommon::format_variable_value( $val, $url_encode, $esc_html, $format );
		}

		return GFCommon::implode_non_blank( ', ', $ary );
	}

	/**
	 * Sanitize and format the value before it is saved to the Entry Object. For radio fields with 'other' option enabled, extracts the user-entered text from the array.
	 *
	 * @since 3.0.0
	 *
	 * @param string $value          The value to be saved.
	 * @param array  $form           The Form object currently being processed.
	 * @param string $input_name     The input name used when accessing the $_POST.
	 * @param int    $entry_id       The ID of the entry currently being processed.
	 * @param array  $entry          The entry currently being processed.
	 * @param string $repeater_index The repeater index if the field is inside a repeater.
	 *
	 * @return array|string The sanitized and formatted input value to be saved.
	 */
	public function get_value_save_input( $value, $form, $input_name, $entry_id, $entry, $repeater_index = '' ) {
		if ( $this->enableOtherChoice && $value == 'gf_other_choice' ) {
			$value = $this->get_other_choice_value_from_post( $repeater_index );
		}

		$value = $this->sanitize_entry_value( $value, $form['id'] );

		return $this->clear_blank_price_value( $value );
	}

	/**
	 * Checks if the "other choice" option was selected, handling nested repeater arrays.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if "other choice" was selected.
	 */
	private function is_other_choice_selected() {
		$posted_value = rgpost( "input_{$this->id}" );

		if ( ! is_array( $posted_value ) ) {
			return $posted_value == 'gf_other_choice';
		}

		$indices = $this->get_repeater_indices();
		if ( $indices === null ) {
			return false;
		}

		$value = $this->get_deep_value( $posted_value, $indices );

		return $value == 'gf_other_choice';
	}

	/**
	 * Extracts the "other" choice value from POST data, handling nested repeater arrays.
	 *
	 * @since 3.0.0
	 *
	 * @param string|null $item_index Optional explicit index (for get_value_save_input).
	 *
	 * @return string The extracted other value, or empty string if not found.
	 */
	public function get_other_choice_value_from_post( $item_index = null ) {
		$other_value = rgpost( "input_{$this->id}_other" );

		if ( ! is_array( $other_value ) ) {
			return is_string( $other_value ) ? $other_value : '';
		}

		$indices = $this->get_repeater_indices( $item_index );
		if ( $indices === null ) {
			return '';
		}

		$value = $this->get_deep_value( $other_value, $indices );

		return is_string( $value ) ? $value : '';
	}

	public function allow_html() {
		return true;
	}

	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		$value = rgar( $entry, $input_id );

		return $is_csv ? $value : GFCommon::selection_display( $value, $this, rgar( $entry, 'currency' ), $use_text );
	}

	/**
	 * Strip scripts and some HTML tags.
	 *
	 * @param string $value The field value to be processed.
	 * @param int $form_id The ID of the form currently being processed.
	 *
	 * @return string
	 */
	public function sanitize_entry_value( $value, $form_id ) {

		if ( is_array( $value ) ) {
			return '';
		}

		$allowable_tags = $this->get_allowable_tags( $form_id );

		if ( $allowable_tags !== true ) {
			$value = strip_tags( $value, $allowable_tags );
		}

		$original_value = $value;

		$allowed_protocols = wp_allowed_protocols();
		$value             = wp_kses_no_null( $value, array( 'slash_zero' => 'keep' ) );
		$value             = wp_kses_hook( $value, 'post', $allowed_protocols );
		$value             = wp_kses_split( $value, 'post', $allowed_protocols );

		$this->post_entry_value_sanitization( $original_value, $value, 'wp_kses' );

		return $value;
	}

	// # FIELD FILTER UI HELPERS ---------------------------------------------------------------------------------------

	/**
	 * Returns the filter operators for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_operators() {
		$operators = $this->type == 'product' ? array( 'is' ) : array( 'is', 'isnot', '>', '<' );

		return $operators;
	}

	/**
	 * Override to return null instead of the array of inputs in case this is a choice field.
	 *
	 * @since 2.9
	 *
	 * @return array|null
	 */
	public function get_entry_inputs() {
		return null;
	}

}

GF_Fields::register( new GF_Field_Radio() );
