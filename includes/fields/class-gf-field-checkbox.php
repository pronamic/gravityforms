<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

require_once( plugin_dir_path( __FILE__ ) . 'field-decorator-choice/class-gf-field-decorator-choice-checkbox-markup.php' );

class GF_Field_Checkbox extends GF_Field {

	/**
	 * @var string $type The field type.
	 */
	public $type = 'checkbox';

	/**
	 * Indicates if this field supports state validation.
	 *
	 * @since 2.5.11
	 *
	 * @var bool
	 */
	protected $_supports_state_validation = true;

	// # FORM EDITOR & FIELD MARKUP -------------------------------------------------------------------------------------

	/**
	 * Returns the field title.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {

		return esc_attr__( 'Checkboxes', 'gravityforms' );

	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to select one or many checkboxes.', 'gravityforms' );
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
		return 'gform-icon--check-box';
	}

	/**
	 * The class names of the settings which should be available on the field in the form editor.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {

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
			'description_setting',
			'css_class_setting',
			'select_all_choices_setting',
		);

	}

	/**
	 * Indicate if this field type can be used when configuring conditional logic rules.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {

		return true;

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

	public function get_default_properties() {
		return array(
			'selectAllText' => esc_html__( 'Select All', 'gravityforms' ),
		);
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @since Unknown
	 * @since 2.5 Implement Select All directly.
	 * @since 2.7 Added `gfield_choice_all_toggle` class to Select All button.
	 *
	 * @param array        $form  The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		if ( $this->type == 'image_choice' ) {
			$this->image_markup = new GF_Field_Decorator_Choice_Checkbox_Markup( $this );
			return $this->image_markup->get_field_input( $form, $value, $entry );
		}

		$form_id = absint( $form['id'] );

		if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
			return $this->get_legacy_field_input( $form, $value, $entry );
		}

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id            = $this->id;
		$field_id      = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

		// Get checkbox choices markup.
		$choices_markup = $this->get_checkbox_choices( $value, $disabled_text, $form_id );

		// Get button markup.
		$button_markup = $this->get_button_markup( $value, $entry );

		$select_all_enabled_class = $this->enableSelectAll ? 'gfield_choice--select_all_enabled' : '';

		$limit_message = $this->get_limit_message();

		if ( 'multi_choice' == $this->type || ! $this->enableSelectAll ) {
			return sprintf(
				"<div class='ginput_container ginput_container_checkbox'>%s<div class='gfield_checkbox %s' id='%s'>%s</div></div>",
				$limit_message,
				$select_all_enabled_class,
				esc_attr( $field_id ),
				$choices_markup
			);
		}

		return sprintf(
			"<div class='ginput_container ginput_container_checkbox'>%s<div class='gfield_checkbox %s' id='%s'>%s%s</div></div>",
			$limit_message,
			$select_all_enabled_class,
			esc_attr( $field_id ),
			$choices_markup,
			$button_markup
		);

	}

	public function get_button_markup( $value, $entry ) {
		/**
		 * Modify the "Select All" checkbox label.
		 *
		 * @since 2.3
		 *
		 * @param string $select_label The "Select All" label.
		 * @param object $field        The field currently being processed.
		 */
		$select_label = gf_apply_filters( array( 'gform_checkbox_select_all_label', $this->formId, $this->id ), esc_html__( 'Select All', 'gravityforms' ), $this );
		$select_label = esc_html( $select_label );

		/**
		 * Modify the "Deselect All" checkbox label.
		 *
		 * @since 2.3
		 *
		 * @param string $deselect_label The "Deselect All" label.
		 * @param object $field          The field currently being processed.
		 */
		$deselect_label = gf_apply_filters( array( 'gform_checkbox_deselect_all_label', $this->formId, $this->id ), esc_html__( 'Deselect All', 'gravityforms' ), $this );
		$deselect_label = esc_html( $deselect_label );

		// Determine if all checkboxes are selected.
		$all_selected = $this->get_selected_choices_count( $value, $entry ) === count( $this->choices );

		// Prepare button markup.
		$button_markup = sprintf(
			'<button type="button" id="button_%1$d_select_all" class="gfield_choice_all_toggle gform-theme-button--size-sm" onclick="gformToggleCheckboxes( this )" data-checked="%4$d" data-label-select="%2$s" data-label-deselect="%3$s"%6$s>%5$s</button>',
			$this->id,
			$select_label,
			$deselect_label,
			$all_selected ? 1 : 0,
			$all_selected ? $deselect_label : $select_label,
			$this->is_form_editor() ? ' disabled="disabled"' : ''
		);

		return $button_markup;
	}

	/**
	 * Get the message that describes the choice limit.
	 *
	 * @since 2.9.0
	 *
	 * @return string
	 */
	public function get_limit_message() {
		$text = $this->get_limit_message_text();
		if ( ! $text ) {
			return '';
		}

		$form_id = $this->formId;
		$form    = GFAPI::get_form( $form_id );

		// If the validation message is the same as the limit message, and they both display above the field, don't display the limit message.
		if ( $this->failed_validation && $this->validation_message === $text && $this->is_validation_above( $form ) ) {
			return;
		}

		$id = $this->id;

		return "<span class='gfield_choice_limit_message gfield_description' id='gfield_choice_limit_message_{$form_id}_{$id}'>{$text}</span>";
	}

	/**
	 * Get the text of the choice limit message, or return false if there is no limit.
	 *
	 * @since 2.9.0
	 *
	 * @return false|string
	 */
	public function get_limit_message_text() {
		if ( $this->choiceLimit === 'exactly' && $this->choiceLimitNumber ) {
			$message = sprintf(
				esc_attr(
					_n(
						'Select exactly %s choice.',
						'Select exactly %s choices.',
						$this->choiceLimitNumber,
						'gravityforms'
					)
				),
				"<strong>$this->choiceLimitNumber</strong>"
			);

			/**
			 * Modify the message displayed when a checkbox is limited to an exact number of entries.
			 *
			 * @since 2.9.0
			 *
			 * @param string $message The message to filter.
			 * @param int    $number  The number of choices that must be selected.
			 * @param object $field   The field currently being processed.
			 */
			return gf_apply_filters( array( 'gform_checkbox_limit_exact_message', $this->formId, $this->id ), $message, $this->choiceLimitNumber, $this );
		}
		if ( $this->choiceLimit === 'range' ) {
			$min  = $this->choiceLimitMin;
			$max  = $this->choiceLimitMax;
			if ( ! $min && $max ) {
				$message = sprintf(
					esc_attr(
						_n(
							'Select up to %s choice.',
							'Select up to %s choices.',
							$max,
							'gravityforms'
						)
					),
					"<strong>$max</strong>"
				);

				/**
				 * Modify the message displayed when a checkbox is limited to a maximum number of choices.
				 *
				 * @since 2.9.0
				 *
				 * @param string $message The message to filter.
				 * @param int $max The maximum number of choices that must be selected.
				 * @param object $field The field currently being processed.
                */
				return gf_apply_filters( array( 'gform_checkbox_limit_max_message', $this->formId, $this->id ), $message, $max, $this );
			}
			if ( ! $max && $min ) {
				$message = sprintf(
					esc_attr(
						_n(
							'Select at least %s choice.',
							'Select at least %s choices.',
							$min,
							'gravityforms'
						)
					),
					"<strong>$min</strong>"
				);

				/**
				 * Modify the message displayed when a checkbox is limited to a minimum number of choices.
				 *
				 * @since 2.9.0
				 *
				 * @param string $message The message to filter.
				 * @param int $min The minimum number of choices that must be selected.
				 * @param object $field The field currently being processed.
				 */
				return gf_apply_filters( array( 'gform_checkbox_limit_min_message', $this->formId, $this->id ), $message, $min, $this );
			}
			if( $min && $max ) {
				$message = sprintf( esc_html__( 'Select between %s and %s choices.', 'gravityforms' ), "<strong>$min</strong>", "<strong>$max</strong>" );

				/**
				 * Modify the message displayed when a checkbox is limited to a maximum number of entries.
				 *
				 * @since 2.9.0
				 *
				 * @param string $message The message to filter.
				 * @param int $min The minimum number of choices that must be selected.
				 * @param int $max The maximum number of choices that must be selected.
				 * @param object $field The field currently being processed.
				 */
				return gf_apply_filters( array( 'gform_checkbox_limit_range_message', $this->formId, $this->id ), $message, $min, $max, $this );
			}
		}

		return false;
	}

	/**
	 * Returns the field inner markup.
	 *
	 * @since  2.5
	 *
	 * @param array        $form  The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_legacy_field_input( $form, $value = '', $entry = null ) {

		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$id            = $this->id;
		$field_id      = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';
		$tag           = GFCommon::is_legacy_markup_enabled( $form ) ? 'ul' : 'div';

		return sprintf(
			"<div class='ginput_container ginput_container_checkbox'><{$tag} class='gfield_checkbox' id='%s'>%s</{$tag}></div>",
			esc_attr( $field_id ),
			$this->get_checkbox_choices( $value, $disabled_text, $form_id )
		);

	}

	/**
	 * Returns the number of selected choices.
	 * Used during field rendering to set the initial state of the (De)Select All toggle.
	 *
	 * @since 2.5
	 *
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return int
	 */
	private function get_selected_choices_count( $value = '', $entry = null ) {

		// Initialize selected, choice number counts.
		$checkboxes_selected = 0;
		$choice_number       = 1;

		foreach ( $this->choices as $choice ) {

			// Hack to skip numbers ending in 0, so that 5.1 doesn't conflict with 5.10.
			if ( $choice_number % 10 == 0 ) {
				$choice_number ++;
			}

			// Prepare input ID.
			if ( rgar( $choice, 'key' ) ) {
				$input_id = $this->get_input_id_from_choice_key( $choice['key'] );
			} else {
				$input_id = $this->id . '.' . $choice_number;
			}

			if ( ( $this->is_form_editor() || ( ! isset( $_GET['gf_token'] ) && empty( $_POST ) ) ) && rgar( $choice, 'isSelected' ) ) {
				$checkboxes_selected++;
			} else if ( is_array( $value ) && GFFormsModel::choice_value_match( $this, $choice, rgget( $input_id, $value ) ) ) {
				$checkboxes_selected++;
			} else if ( ! is_array( $value ) && GFFormsModel::choice_value_match( $this, $choice, $value ) ) {
				$checkboxes_selected++;
			}

			$choice_number++;

		}

		return $checkboxes_selected;

	}





	// # SUBMISSION -----------------------------------------------------------------------------------------------------

	/**
	 * Retrieve the field value on submission.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array     $field_values             The dynamic population parameter names with their corresponding values to be populated.
	 * @param bool|true $get_from_post_global_var Whether to get the value from the $_POST array as opposed to $field_values.
	 *
	 * @uses GFFormsModel::choice_value_match()
	 * @uses GFFormsModel::get_parameter_value()
	 *
	 * @return array|string
	 */
	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		// Get parameter values for field.
		$parameter_values = GFFormsModel::get_parameter_value( $this->inputName, $field_values, $this );

		// If parameter values exist but are not an array, convert to array.
		if ( ! empty( $parameter_values ) && ! is_array( $parameter_values ) ) {
			$parameter_values = explode( ',', $parameter_values );
		}

		// If no inputs are defined, return an empty string.
		if ( ! is_array( $this->inputs ) ) {
			return '';
		}

		// Set initial choice index.
		$choice_index = 0;

		// Initialize submission value array.
		$value = array();

		// Loop through field inputs.
		foreach ( $this->inputs as $input ) {

			if ( ! empty( $_POST[ 'is_submit_' . $this->formId ] ) && $get_from_post_global_var ) {

				$input_value = rgpost( 'input_' . str_replace( '.', '_', strval( $input['id'] ) ) );

				$value[ strval( $input['id'] ) ] = $input_value;

			} else {

				if ( is_array( $parameter_values ) ) {

					foreach ( $parameter_values as $item ) {

						$item = trim( $item );

						if ( rgar( $input, 'key' ) ) {
							$choice_id = $this->get_choice_id_from_input_key( $input['key'] );
							if ( '' == $choice_id ) {
								continue;
							}
							$choice = $this->choices[ $choice_id ];
						} else {
							$choice = $this->choices[ $choice_index ];
						}

						if ( GFFormsModel::choice_value_match( $this, $choice, $item ) ) {
							$value[ $input['id'] . '' ] = $item;
							break;
						}

					}

				}

			}

			// Increase choice index.
			$choice_index ++;

		}

		return $value;

	}

	public function validate( $value, $form ) {
		if ( $this->choiceLimit == 'exactly' ) {
			$selected_choices_count = $this->get_selected_choices_count( $value );
			if ( 0 === $selected_choices_count ) {
				return;
			}
			if ( $selected_choices_count != $this->choiceLimitNumber ) {
				$this->failed_validation  = true;
				$this->validation_message = $this->get_limit_message_text();
			}
		} elseif ( $this->choiceLimit == 'range' ) {
			$selected_choices_count = $this->get_selected_choices_count( $value );
			if ( 0 === $selected_choices_count ) {
				return;
			}
			if ( ( $this->choiceLimitMin && $selected_choices_count < $this->choiceLimitMin ) || ( $this->choiceLimitMax && $selected_choices_count > $this->choiceLimitMax ) ) {
				$this->failed_validation  = true;
				$this->validation_message = $this->get_limit_message_text();
			}
		}
	}



	// # ENTRY RELATED --------------------------------------------------------------------------------------------------

	/**
	 * Format the entry value for display on the entries list page.
	 *
	 * Return a value that's safe to display on the page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string|array $value    The field value.
	 * @param array        $entry    The Entry Object currently being processed.
	 * @param string       $field_id The field or input ID currently being processed.
	 * @param array        $columns  The properties for the columns being displayed on the entry list page.
	 * @param array        $form     The Form Object currently being processed.
	 *
	 * @uses GFCommon::implode_non_blank()
	 * @uses GFCommon::prepare_post_category_value()
	 * @uses GFCommon::selection_display()
	 * @uses GF_Field_Checkbox::is_checkbox_checked()
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {

		// If this is the main checkbox field (not an input), display a comma separated list of all inputs.
		if ( absint( $field_id ) == $field_id ) {

			$lead_field_keys = array_keys( $entry );
			$items           = array();

			foreach ( $lead_field_keys as $input_id ) {
				if ( is_numeric( $input_id ) && absint( $input_id ) == $field_id ) {
					$items[] = $this->get_selected_choice_output( rgar( $entry, $input_id ), rgar( $entry, 'currency' ) );
				}
			}

			$value = GFCommon::implode_non_blank( ', ', $items );

			// Special case for post category checkbox fields.
			if ( $this->type == 'post_category' ) {
				$value = GFCommon::prepare_post_category_value( $value, $this, 'entry_list' );
			}
		} else {

			$value = '';

			if ( ! rgblank( $this->is_checkbox_checked( $field_id, $columns[ $field_id ]['label'], $entry ) ) ) {
				$value = "<i class='fa fa-check gf_valid'></i>";
			}

		}

		return $value;

	}

	/**
	 * Format the entry value for display on the entry detail page and for the {all_fields} merge tag.
	 *
	 * Return a value that's safe to display for the context of the given $format.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string|array $value    The field value.
	 * @param string       $currency The entry currency code.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string       $media    The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @uses GFCommon::selection_display()
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( is_array( $value ) ) {

			$items = '';

			foreach ( $value as $key => $item ) {
				if ( ! rgblank( $item ) ) {
					switch ( $format ) {
						case 'text' :
							$items .= $this->get_selected_choice_output( $item, $currency, $use_text ) . ', ';
							break;

						default:
							$items .= '<li>' . $this->get_selected_choice_output( $item, $currency, $use_text ) . '</li>';
							break;
					}
				}
			}

			if ( empty( $items ) ) {
				return '';
			} elseif ( $format == 'text' ) {
				return substr( $items, 0, strlen( $items ) - 2 ); // Removing last comma.
			} else {
				return "<ul class='bulleted'>$items</ul>";
			}

		} else {

			return $value;

		}

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
	 * @uses GFCommon::format_post_category()
	 * @uses GFCommon::format_variable_value()
	 * @uses GFCommon::implode_non_blank()
	 * @uses GFCommon::to_money()
	 * @uses GFFormsModel::is_field_hidden()
	 *
	 * @return string The processed merge tag.
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {

		// Check for passed modifiers.
		$modifiers       = $this->get_modifiers();
		$use_value       = in_array( 'value', $modifiers );
		$format_currency = in_array( 'currency', $modifiers );
		$use_price       = $format_currency || in_array( 'price', $modifiers );
		$image_url 	     = in_array( 'img_url', $modifiers );

		if ( is_array( $raw_value ) && (string) intval( $input_id ) != $input_id ) {
			$items = array( $input_id => $value ); // Float input IDs. (i.e. 4.1 ). Used when targeting specific checkbox items.
		} elseif ( is_array( $raw_value ) ) {
			$items = $raw_value;
		} else {
			$items = array( $input_id => $raw_value );
		}

		$ary = array();

		// Get the items available within the merge tags.
		foreach ( $items as $input_id => $item ) {
			switch (true) {
				// If the 'value' modifier was passed.
				case $use_value:
					list( $val, $price ) = rgexplode( '|', $item, 2 );
					break;

				// If the 'price' or 'currency' modifiers were passed.
				case $use_price:
					list( $name, $val ) = rgexplode( '|', $item, 2 );
					if ( $format_currency ) {
						$val = GFCommon::to_money( $val, rgar( $entry, 'currency' ) );
					}
					break;

				// If the 'image_url' modifier was passed.
				case $image_url:
					$image_choice = new GF_Field_Image_Choice( $this );
					$val = $image_choice->get_merge_tag_img_url( $raw_value, $input_id, $entry, $form, $this );
					break;

				// If this is a post category checkbox.
				case $this->type == 'post_category':
					$use_id     = strtolower( $modifier ) == 'id';
					$item_value = GFCommon::format_post_category( $item, $use_id );
					$val = GFFormsModel::is_field_hidden( $form, $this, array(), $entry ) ? '' : $item_value;
					break;

				// If no modifiers were passed.
				default:
					$val = GFFormsModel::is_field_hidden( $form, $this, array(), $entry ) ? '' : RGFormsModel::get_choice_text( $this, $raw_value, $input_id );
					break;
			}

			$ary[] = GFCommon::format_variable_value( $val, $url_encode, $esc_html, $format );
		}

		return GFCommon::implode_non_blank( ', ', $ary );

	}

	/**
	 * Sanitize and format the value before it is saved to the Entry Object.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $value      The value to be saved.
	 * @param array  $form       The Form Object currently being processed.
	 * @param string $input_name The input name used when accessing the $_POST.
	 * @param int    $lead_id    The ID of the Entry currently being processed.
	 * @param array  $lead       The Entry Object currently being processed.
	 *
	 * @uses GF_Field_Checkbox::sanitize_entry_value()
	 *
	 * @return array|string The safe value.
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {

		if ( rgblank( $value ) ) {

			return '';

		} elseif ( is_array( $value ) ) {

			foreach ( $value as &$v ) {

				if ( is_array( $v ) ) {
					$v = '';
				}

				$v = $this->sanitize_entry_value( $v, $form['id'] );

			}

			return implode( ',', $value );

		} else {

			return $this->sanitize_entry_value( $value, $form['id'] );

		}

	}

	/**
	 * Return the entry inputs in the order they are configured in the form editor.
	 *
	 * @since  2.9
	 *
	 * @return array|null
	 */
	public function get_entry_inputs() {
		if ( $this->has_persistent_choices() && is_array( $this->inputs ) ) {
			$inputs_by_key = array_column( $this->inputs, null, 'key' );
			$sorted_inputs = array();

			foreach ( $this->choices as $choice ) {
				if ( isset( $inputs_by_key[ $choice['key'] ] ) ) {
					$sorted_inputs[] = $inputs_by_key[ $choice['key'] ];
				}
			}
			return $sorted_inputs;
		} else {
			return $this->inputs;
		}
	}

	/**
	 * Format the entry value before it is used in entry exports and by framework add-ons using GFAddOn::get_field_value().
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array      $entry    The entry currently being processed.
	 * @param string     $input_id The field or input ID.
	 * @param bool|false $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param bool|false $is_csv   Is the value going to be used in the .csv entries export?
	 *
	 * @uses GFCommon::get_label()
	 * @uses GFCommon::selection_display()
	 * @uses GF_Field_Checkbox::is_checkbox_checked()
	 *
	 * @return string
	 */
	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {

		if ( empty( $input_id ) || absint( $input_id ) == $input_id ) {

			$selected = array();

			foreach ( $this->inputs as $input ) {

				$index = (string) $input['id'];

				if ( ! rgempty( $index, $entry ) ) {
					$selected[] = GFCommon::selection_display( rgar( $entry, $index ), $this, rgar( $entry, 'currency' ), $use_text );
				}

			}

			return implode( ', ', $selected );

		} else if ( $is_csv ) {

			$value = $this->is_checkbox_checked( $input_id, GFCommon::get_label( $this, $input_id ), $entry );

			return empty( $value ) ? '' : $value;

		} else {

			return GFCommon::selection_display( rgar( $entry, $input_id ), $this, rgar( $entry, 'currency' ), $use_text );

		}

	}





	// # INPUT ATTRIBUTE HELPERS ----------------------------------------------------------------------------------------

	/**
	 * Get checkbox choice inputs for field.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string|array $value         The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param string       $disabled_text The HTML disabled attribute.
	 * @param int          $form_id       The current form ID.
	 *
	 * @uses GFCommon::to_number()
	 * @uses GF_Field::get_conditional_logic_event()
	 * @uses GF_Field::get_tabindex()
	 * @uses GF_Field::is_entry_detail()
	 * @uses GF_Field::is_form_editor()
	 * @uses GFFormsModel::choice_value_match()
	 *
	 * @return string
	 */
	public function get_checkbox_choices( $value, $disabled_text, $form_id = 0 ) {

		$choices = '';
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		if ( is_array( $this->choices ) ) {

			$choice_number = 1;
			$count         = 1;

			/**
			 * A filter that allows for the setting of the maximum number of choices shown in
			 * the form editor for choice based fields (radio, checkbox, image, and multiple choice).
			 *
			 * @since 2.9
			 *
			 * @param int    $max_choices_visible_count The default number of choices visible is 5.
			 * @param object $field                     The current field object.
			 */
			$max_choices_count = gf_apply_filters( array( 'gform_field_choices_max_count_visible', $form_id ), 5, $this );

			$legacy_markup = GFCommon::is_legacy_markup_enabled( $form_id );

			$tag = $legacy_markup ? 'li' : 'div';

			// Add Select All choice for legacy markup.
			if ( $this->enableSelectAll && $legacy_markup ) {

				/**
				 * Modify the "Select All" checkbox label.
				 *
				 * @since 2.3
				 *
				 * @param string $select_label The "Select All" label.
				 * @param object $field        The field currently being processed.
				 */
				$select_label = gf_apply_filters( array( 'gform_checkbox_select_all_label', $this->formId, $this->id ), esc_html__( 'Select All', 'gravityforms' ), $this );
				$select_label = esc_html( $select_label );

				/**
				 * Modify the "Deselect All" checkbox label.
				 *
				 * @since 2.3
				 *
				 * @param string $deselect_label The "Deselect All" label.
				 * @param object $field          The field currently being processed.
				 */
				$deselect_label = gf_apply_filters( array( 'gform_checkbox_deselect_all_label', $this->formId, $this->id ), esc_html__( 'Deselect All', 'gravityforms' ), $this );
				$deselect_label = esc_html( $deselect_label );

				// Get tabindex.
				$tabindex = $this->get_tabindex();

				// Prepare choice ID.
				$id = 'choice_' . $this->id . '_select_all';

				// Determine if all checkboxes are selected.
				if ( $this->get_selected_choices_count( $value ) === count( $this->choices ) ) {
					$checked      = ' checked="checked"';
					$toggle_label = $deselect_label;
				} else {
					$checked      = '';
					$toggle_label = $select_label;
				}

				// Prepare choice markup.
				$choice_markup = "<{$tag} class='gchoice gchoice_select_all'>
						<input class='gfield-choice-input' type='checkbox' id='{$id}' {$tabindex} {$disabled_text} onclick='gformToggleCheckboxes( this )' onkeypress='gformToggleCheckboxes( this )'{$checked} />
						<label for='{$id}' id='label_" . $this->id . "_select_all' class='gform-field-label  gform-field-label--type-inline' data-label-select='{$select_label}' data-label-deselect='{$deselect_label}'>{$toggle_label}</label>
					</{$tag}>";

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
				$choices .= gf_apply_filters( array( 'gform_field_choice_markup_pre_render', $this->formId, $this->id ), $choice_markup, array(), $this, $value );

			}

			$need_aria_describedby = true;

			// Add select all choice for the Multiple Choice field.
			if ( $this->type == 'multi_choice' && $this->enableSelectAll ) {
				$this->choice_field     = new GF_Field_Multiple_Choice( $this );
				$selected_choices_count = $this->get_selected_choices_count( $value );
				$tabindex               = $this->get_tabindex();
				$need_aria_describedby  = false; // We're going to add the aria-describedby attribute to the "Select All" choice, so we don't need it later on the first choice.

				$choices .=  $this->choice_field->get_choice_field_select_all_markup( $value, $tabindex, $selected_choices_count );
			}

			// Loop through field choices.
			foreach ( $this->choices as $choice ) {

				$aria_describedby = ( $choice_number === 1 && $need_aria_describedby ) ? $this->get_choice_aria_describedby( $form_id ) : '';

				// Hack to skip numbers ending in 0, so that 5.1 doesn't conflict with 5.10.
				if ( $choice_number % 10 == 0 ) {
					$choice_number ++;
				}

				// Prepare input ID.
				$input_id = '';
				if ( $this->has_persistent_choices() ) {
					$input_id = $this->get_input_id_from_choice_key( $choice['key'] );
				} else {
					// Regular checkboxes field generates input IDs on the fly.
					$input_id = $this->id . '.' . $choice_number;
				}

				if ( $is_entry_detail || $is_form_editor || $form_id == 0 ) {
					$id = $this->id . '_' . $choice_number ++;
				} else {
					$id = $form_id . '_' . $this->id . '_' . $choice_number ++;
				}

				$checked      = $this->get_checked_attribute( $choice, $value, $input_id, $form_id );
				$tabindex     = $this->get_tabindex();
				$choice_value = $choice['value'];

				if ( $this->enablePrice ) {
					$price = rgempty( 'price', $choice ) ? 0 : GFCommon::to_number( rgar( $choice, 'price' ) );
					$choice_value .= '|' . $price;
				}

				$choice_value  = esc_attr( $choice_value );
				$choice_markup = "<{$tag} class='gchoice gchoice_{$id}'>
								<input class='gfield-choice-input' name='input_{$input_id}' type='checkbox'  value='{$choice_value}' {$checked} id='choice_{$id}' {$tabindex} {$disabled_text} {$aria_describedby}/>
								<label for='choice_{$id}' id='label_{$id}' class='gform-field-label gform-field-label--type-inline'>{$choice['text']}</label>
							</{$tag}>";

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
				$choices .= gf_apply_filters( array( 'gform_field_choice_markup_pre_render', $this->formId, $this->id ), $choice_markup, $choice, $this, $value );

				$is_admin = $is_entry_detail || $is_form_editor;

				if ( $is_admin && rgget('view') != 'entry' && $count >= $max_choices_count ) {
					break;
				}

				$count ++;

			}

			$total = sizeof( $this->choices );

			if ( $count < $total ) {
				$choices .= "<{$tag} class='gchoice_total'><span>" . sprintf( esc_html__( '%d of %d items shown. Edit choices to view all.', 'gravityforms' ), $count, $total ) . "</span></{$tag}>";
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
		return gf_apply_filters( array( 'gform_field_choices', $this->formId, $this->id ), $choices, $this );

	}

	public function get_checked_attribute( $choice, $value, $input_id, $form_id ) {
		$is_form_editor  = $this->is_form_editor();

		if ( ( $is_form_editor || ( ! isset( $_GET['gf_token'] ) && empty( $_POST ) ) ) && rgar( $choice, 'isSelected' ) ) {
			$checked = "checked='checked'";
		} elseif ( is_array( $value ) && GFFormsModel::choice_value_match( $this, $choice, rgget( $input_id, $value ) ) ) {
			$checked = "checked='checked'";
		} elseif ( ! is_array( $value ) && GFFormsModel::choice_value_match( $this, $choice, $value ) && ! empty( $_POST[ 'is_submit_' . $form_id ] ) ) {
			$checked = "checked='checked'";
		} else {
			$checked = '';
		}

		return $checked;
	}

	/**
	 * Get the aria-describedby attribute for the first choice.
	 *
	 * @since  2.9.0
	 * @access public
	 *
	 * @param int $form_id The current form ID.
	 * @param array $describedby Additional describedby attribute value.
	 *
	 * @return string
	 */
	public function get_choice_aria_describedby( $form_id, $describedby = array() ) {
		$limit_describedby = array();

		if ( is_array( $describedby ) ) {
			$limit_describedby = array_merge( $limit_describedby, $describedby );
		}

		if ( $this->get_limit_message_text() ) {
			$limit_describedby[] = 'gfield_choice_limit_message_' . $form_id . '_' . $this->id;
		}

		return $this->get_aria_describedby( $limit_describedby );
	}

	/**
	 * Determine if a specific checkbox is checked.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param int    $field_id    Field ID.
	 * @param string $field_label Field label.
	 * @param array  $entry       Entry object.
	 *
	 * @return bool
	 */
	public function is_checkbox_checked( $field_id, $field_label, $entry ) {

		$allowed_tags = wp_kses_allowed_html( 'post' );

		$entry_field_keys = array_keys( $entry );

		// Looping through lead detail values trying to find an item identical to the column label. Mark with a tick if found.
		foreach ( $entry_field_keys as $input_id ) {

			// Mark as a tick if input label (from form meta) is equal to submitted value (from lead)
			if ( is_numeric( $input_id ) && absint( $input_id ) == absint( $field_id ) ) {

				$sanitized_value = wp_kses( $entry[ $input_id ], $allowed_tags );
				$sanitized_label = wp_kses( $field_label, $allowed_tags );

				if ( $sanitized_value == $sanitized_label ) {

					return $entry[ $input_id ];

				} else {

					if ( $this->enableChoiceValue || $this->enablePrice ) {

						foreach ( $this->choices as $choice ) {

							if ( $choice['value'] == $entry[ $field_id ] ) {

								return $choice['value'];

							} else if ( $this->enablePrice ) {

								$ary   = explode( '|', $entry[ $field_id ] );
								$val   = count( $ary ) > 0 ? $ary[0] : '';
								$price = count( $ary ) > 1 ? $ary[1] : '';

								if ( $val == $choice['value'] ) {
									return $choice['value'];
								}

							}

						}

					}

				}

			}

		}

		return false;

	}





	// # OTHER HELPERS --------------------------------------------------------------------------------------------------

	/**
	 * Returns the input ID to be assigned to the field label for attribute.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_first_input_id( $form ) {

		return '';

	}

	/**
	 * Retrieve the field default value.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCommon::replace_variables_prepopulate()
	 * @uses GF_Field::is_form_editor()
	 *
	 * @return array|string
	 */
	public function get_value_default() {

		return $this->is_form_editor() ? $this->defaultValue : GFCommon::replace_variables_prepopulate( $this->defaultValue );

	}





	// # SANITIZATION ---------------------------------------------------------------------------------------------------

	/**
	 * If the field should allow html tags to be saved with the entry value. Default is false.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return bool
	 */
	public function allow_html() {

		return true;

	}

	/**
	 * Forces settings into expected values while saving the form object.
	 *
	 * No escaping should be done at this stage to prevent double escaping on output.
	 *
	 * Currently called only for forms created after version 1.9.6.10.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 */
	public function sanitize_settings() {

		parent::sanitize_settings();

		if ( 'option' === $this->type ) {
			$this->productField = absint( $this->productField );
		}

		if ( 'post_category' === $this->type ) {
			$this->displayAllCategories = (bool) $this->displayAllCategories;
		}

	}

	/**
	 * Strip scripts and some HTML tags.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $value   The field value to be processed.
	 * @param int    $form_id The ID of the form currently being processed.
	 *
	 * @uses GF_Field::get_allowable_tags()
	 *
	 * @return string
	 */
	public function sanitize_entry_value( $value, $form_id ) {

		// If the value is an array, return an empty string.
		if ( is_array( $value ) ) {
			return '';
		}

		// Get allowable tags for field value.
		$allowable_tags = $this->get_allowable_tags( $form_id );

		// If allowable tags are defined, strip unallowed tags.
		if ( $allowable_tags !== true ) {
			$value = strip_tags( $value, $allowable_tags );
		}

		// Sanitize value.
		$allowed_protocols = wp_allowed_protocols();
		$value             = wp_kses_no_null( $value, array( 'slash_zero' => 'keep' ) );
		$value             = wp_kses_hook( $value, 'post', $allowed_protocols );
		$value             = wp_kses_split( $value, 'post', $allowed_protocols );

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
		return array( 'is' );
	}

}

GF_Fields::register( new GF_Field_Checkbox() );
