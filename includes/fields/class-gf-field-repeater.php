<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * The Repeater field.
 *
 * 2.4
 *
 * Class GF_Field_Repeater
 */
class GF_Field_Repeater extends GF_Field {

	public $type = 'repeater';

	/**
	 * Repeater field constructor.
	 *
	 * @since 3.0
	 */
	public function __construct( $properties = array() ) {
		parent::__construct( $properties );
		if ( isset( $this->fields ) && is_array( $this->fields ) ) {
			$this->normalize_fields();
		}
	}

	/**
	 * Ensures $this->fields is always an array of GF_Field objects.
	 *
	 * @since 3.0
	 */
	protected function normalize_fields() {
		if ( ! is_array( $this->fields ) ) {
			return;
		}
		foreach ( $this->fields as $key => $field ) {
			if ( is_array( $field ) ) {
				$this->fields[ $key ] = GF_Fields::create( $field );
			}
			// Handle nested repeaters
			if ( isset( $this->fields[ $key ] ) && is_object( $this->fields[ $key ] ) && property_exists( $this->fields[ $key ], 'fields' ) && is_array( $this->fields[ $key ]->fields ) ) {
				if ( method_exists( $this->fields[ $key ], 'normalize_fields' ) ) {
					$this->fields[ $key ]->normalize_fields();
				}
			}
		}
	}

	/**
	 * Returns the field title for the form editor.
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( 'Repeater', 'gravityforms' );
	}

	/**
	 * Returns the field description for the form editor.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to submit a repeating field or group of fields.', 'gravityforms' );
	}

	/**
	 * Returns the field icon for the form editor.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--repeater';
	}

	public function get_form_editor_button() {
		if ( apply_filters( 'gform_enable_repeater_field_button', false ) ) {
			return parent::get_form_editor_button();
		} else {
			return array();
		}
	}

	/**
	 * Returns the field settings for the form editor.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'visibility_setting',
			'description_setting',
			'css_class_setting',
			'max_items_setting',
			'add_button_text_setting',
			'remove_button_text_setting',
		);
	}

	/**
	 * Used to determine the required validation result.
	 *
	 * @since 2.4
	 *
	 * @param int $form_id The ID of the form currently being processed.
	 *
	 * @return bool
	 */
	public function is_value_submission_empty( $form_id ) {
		return false;
	}

	/**
	 * Is the given value considered empty for this field.
	 *
	 * @since 3.0
	 *
	 * @param null|array $value The field value.
	 *
	 * @return bool
	 */
	public function is_value_empty( $value ) {
		return GFCommon::is_empty_array( $value );
	}

	/**
	 * Validates each sub-field.
	 *
	 * @since 2.4
	 * @since 3.0 Updated to validate all the subfields using GFFormDisplay::validate_field().
	 *
	 * @param string|array $value The field values from get_value_submission().
	 * @param array        $form  The Form Object currently being processed.
	 */
	public function validate( $value, $form ) {

		/* @var GF_Field[] $fields */
		$fields = $this->fields;
		if ( empty( $fields ) ) {
			return;
		}

		$items   = empty( $value ) ? array( array() ) : array_values( $value );
		$context = GFFormDisplay::get_submission_context();

		$summary_errors = array();
		$is_empty       = null;

		$repeater_item_index = $this->get_context_property( 'index_chain' );
		$repeater_item_index = is_null( $repeater_item_index ) ? '' : $repeater_item_index . '-';
		$repeater_label      = '';

		$no_duplicate_values = array();

		foreach ( $items as $i => $item ) {
			foreach ( $fields as $field ) {
				$field_index_chain = $repeater_item_index . $i;
				if ( $field instanceof GF_Field_Repeater ) {
					$field->set_context_property( 'index_chain', $field_index_chain );
				}

				$field->set_context_property( 'itemIndex', $i );
				$field_value = $this->get_field_value( $field, $item );

				if ( $field->noDuplicates && ! $field->is_value_empty( $field_value ) ) {
					/**
					 * Filter the value checked during duplicate value checks.
					 *
					 * @since 2.9.2
					 *
					 * @param string     $value   The value being checked against existing entries for duplicates.
					 * @param \GF_Field  $field   The field being checked for duplicates.
					 * @param int        $form_id The ID of the form being checked for duplicates.
					 */
					$filtered_value   = apply_filters( 'gform_value_pre_duplicate_check', $field_value, $field, $form['id'] );
					$comparison_value = is_array( $filtered_value ) ? rgar( $filtered_value, 0 ) : $filtered_value;

					if ( ! isset( $no_duplicate_values[ $field->id ] ) ) {
						$no_duplicate_values[ $field->id ] = array();
					}

					if ( in_array( $comparison_value, $no_duplicate_values[ $field->id ], true ) ) {
						$field->failed_validation = true;

						switch ( $field->get_input_type() ) {
							case 'date':
								$message = esc_html__( 'This date has already been taken. Please select a new date.', 'gravityforms' );
								break;
							default:
								$message = is_array( $filtered_value ) ? esc_html__( 'This field requires a unique entry and the values you entered have already been used.', 'gravityforms' ) :
									// translators: %s: the value that has already been used.
									sprintf( esc_html__( "This field requires a unique entry and '%s' has already been used", 'gravityforms' ), $filtered_value );
								break;
						}

						/**
						 * Allows the no duplicate validation message to be customized.
						 *
						 * @param string $message The no duplicate validation message.
						 * @param array $form The form currently being validated.
						 * @param GF_Field $field The field currently being validated.
						 * @param mixed $value The value currently being validated.
						 *
						 * @since 1.8.5 Added $field and $value params.
						 * @since 2.7   Moved from GFFormDisplay::validate().
						 *
						 * @since 1.5
						 */
						$field->validation_message = gf_apply_filters( array( 'gform_duplicate_message', $form['id'] ), $message, $form, $field, $filtered_value );
					}

					$no_duplicate_values[ $field->id ][] = $comparison_value;
				}

				$result = GFFormDisplay::validate_field( $field, $form, $context, $field_value );

				$result['failed_state_validation'] = (bool) $field->get_context_property( 'failed_state_validation' );
				$result['is_empty_1']              = $field->get_context_property( 'is_empty_1' );
				GFCommon::log_debug( __METHOD__ . sprintf( '(): row %s; field #%d; value = %s; result = %s', $i, $field->id, print_r( $field_value, true ), print_r( $result, true ) ) );
				$this->set_sub_field_validation_result( $field, $i, $result );

				if ( ! is_null( $result['is_empty_1'] ) && ( is_null( $is_empty ) || $is_empty ) ) {
					$is_empty = $result['is_empty_1'];
				}

				if ( $field->failed_validation ) {
					// A field has failed validation, so the entire repeater fails.
					$this->failed_validation = true;

					if ( $field instanceof GF_Field_Repeater ) {
						$summary_errors = array_merge( $summary_errors, $field->get_context_property( 'validation_summary_errors' ) );
					} else {
						if ( empty( $repeater_label ) ) {
							$repeater_label = $this->get_field_label();
						}

						$summary_errors[] = array(
							'field_label'    => sprintf( '%s (#%d - %s)', $field->get_field_label(), $i + 1, $repeater_label ),
							'field_selector' => sprintf( '#field_%d_%d-%s', $field->formId, $field->id, $field_index_chain ),
							'message'        => $result['message'],
						);
					}
				}

				// Reset the field properties.
				unset( $field->failed_validation, $field->validation_messsage );
				$field->set_context_property( 'itemIndex', null );
				$field->set_context_property( 'failed_state_validation', null );
				$field->set_context_property( 'is_empty_1', null );
			}
		}

		if ( ! is_null( $is_empty ) ) {
			// Cache the result for the checks performed by GFFormDisplay::is_form_empty().
			$this->set_context_property( 'is_empty_0', $is_empty );
		}

		if ( ! empty( $summary_errors ) ) {
			$this->set_context_property( 'validation_summary_errors', $summary_errors );
		}
	}

	/**
	 * Populates the sub field repeater_validation_results context property with the validation result for the given index.
	 *
	 * @since 3.0
	 *
	 * @param GF_Field $field  The current sub field.
	 * @param int      $index  The current row index.
	 * @param array    $result The validation result.
	 *
	 * @return void
	 */
	public function set_sub_field_validation_result( $field, $index, $result ) {
		$results = $field->get_context_property( 'repeater_validation_results' );
		if ( ! is_array( $results ) ) {
			$results = array();
		}
		$results[ $index ] = $result;
		$field->set_context_property( 'repeater_validation_results', $results );
	}

	/**
	 * Retrieve the field value on submission.
	 *
	 * @since 2.4
	 *
	 * @param array     $field_values             The dynamic population parameter names with their corresponding values to be populated.
	 * @param bool|true $get_from_post_global_var Whether to get the value from the $_POST array as opposed to $field_values.
	 *
	 * @return array|string
	 */
	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		if ( ! $field_values && ! $get_from_post_global_var ) {
			return array();
		}

		$submission_values = $this->get_value_submission_recursive( $field_values, $get_from_post_global_var );

		$items = $this->hydrate( $submission_values );
		return $items[ $this->id ];
	}

	/**
	 * Returns the submission values for the repeater.
	 *
	 * @since 2.4
	 *
	 * @param array $field_values
	 * @param bool $get_from_post_global_var
	 *
	 * @return array
	 */
	public function get_value_submission_recursive( $field_values, $get_from_post_global_var ) {

		$items = array();

		if ( isset( $this->fields ) && is_array( $this->fields ) ) {

			foreach ( $this->fields as $sub_field ) {
				/* @var GF_Field $sub_field */
				if ( isset( $sub_field->fields ) && is_array( $sub_field->fields ) ) {
					/* @var GF_Field_Repeater $sub_field */
					$field_items = $sub_field->get_value_submission_recursive( $field_values, $get_from_post_global_var );
				} else {
					$values = $sub_field->get_value_submission( $field_values, $get_from_post_global_var );

					$inputs = $sub_field->get_repeater_inputs();

					if ( is_array( $inputs ) && ! is_array( $values ) && $sub_field->get_input_type() === 'password' && $get_from_post_global_var ) {
						$collected_values = array();
						foreach ( $inputs as $input_index => $input ) {
							$input_name = 'input_' . str_replace( '.', '_', $input['id'] );
							$post_value = rgpost( $input_name );
							if ( is_array( $post_value ) ) {
								$collected_values[ $input_index ] = $post_value;
							}
						}
						if ( ! empty( $collected_values ) ) {
							$values = $collected_values;
						}
					}

					if ( is_array( $inputs ) ) {
						$prefix = '';
					} else {
						$prefix = $sub_field->id . '_';
					}

					if ( $sub_field instanceof GF_Field_List ) {
						// List field expects an array. If we flatten the List Field values, we end up adding extra repeater items instead of populating the list.
						if ( is_array( $values ) ) {
							$field_items = array();
							foreach ( $values as $key => $value ) {
								$field_items[ $prefix . $key ] = $value;
							}
						} else {
							$field_items = array( $prefix . '0' => $values );
						}
					} elseif ( is_array( $inputs ) && is_array( $values ) && ! $sub_field->is_value_submission_array()
						&& in_array( $sub_field->get_input_type(), array( 'email', 'password' ) ) ) {
						// Special handling for confirmation fields (email/password) where each element
						// in the values array corresponds to an input (main value, confirmation value)
						$field_items = array();
						$input_index = 0;
						foreach ( $inputs as $input ) {
							if ( isset( $values[ $input_index ] ) ) {
								$flattened   = $this->flatten(
									is_array( $values[ $input_index ] ) ? $values[ $input_index ] : array( $values[ $input_index ] ),
									$input['id'] . '_'
								);
								$field_items = array_merge( $field_items, $flattened );
							}
							++$input_index;
						}
					} else {
						$field_items = $this->flatten( is_array( $values ) ? $values : array( $values ), $prefix, $sub_field->is_value_submission_array() );
					}
				}
				$items = array_merge( $items, $field_items );
			}
		} else {
			$values      = $this->get_value_submission( $field_values, $get_from_post_global_var );
			$field_items = $this->flatten( $values, $this->id . '_', $this->is_value_submission_array() );
			$items       = array_merge( $items, $field_items );
		}

		return $items;
	}

	/**
	 * Utility to flatten array values recursively so they can be saved with the appropriate index.
	 *
	 * We save the data in this format to make it possible to search entries by sub-field.
	 *
	 * @since 2.4
	 *
	 * @param        $ary
	 * @param string $prefix
	 * @param bool   $field_value_is_array
	 *
	 * @return array
	 */
	private function flatten( $ary, $prefix = '', $field_value_is_array = false ) {
		$result = array();
		if ( ! is_array( $ary ) ) {
			return $result;
		}
		foreach ( $ary as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( $field_value_is_array && ! is_array( $value[0] ) ) {
					$result[ $prefix . $key ] = $value;
				} else {
					$result = $result + $this->flatten( $value, $prefix . $key . '_' );
				}
			} else {
				$index = $prefix ? $prefix . $key : $key . '_0'; // if no prefix, add '_0' so that prepopulation of complex fields work correctly.
				$result[ $index ] = $value;
			}
		}

		return $result;
	}

	/**
	 * Returns the HTML tag for the field container.
	 *
	 * @since 3.0.0
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

	/**
	 * Returns the field inner markup.
	 *
	 * @since 2.4
	 *
	 * @param array        $form  The Form Object currently being processed.
	 * @param string|array $value The field values. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$values = empty( $value ) ? array( '' ) : $value;

		$this->set_context_property( 'form', $form );
		$input_top     = $this->get_input_top( $values );
		$input_bottom  = $this->get_input_bottom();
		$description   = $this->get_description( $this->description, 'gfield_description' );
		$limit_message = $this->get_limit_message();

		$description_above = '';
		$description_below = '';

		if ( $this->is_description_above( $form ) ) {
			$description_above = $description . $limit_message;
		} else {
			$description_below = $description . $limit_message;
		}

		$message = '';
		if ( $this->is_form_editor() ) {
			$message = $this->get_form_editor_field_message( $form );
		}

		if ( $this->is_form_editor() && empty( $this->fields ) ) {
			$items = self::get_empty_wrapper( $this );
		} else {
			$items = $this->get_input_items( $values, $entry );
		}

		$html = $input_top . $description_above . $items . $description_below . $input_bottom;

		$max_items = intval( $this->maxItems );

		return $message . sprintf( "<div class='gfield_repeater_wrapper gfield_repeater_id_{$this->id}' data-max_items='{$max_items}'>%s</div>", $html );
	}

	/**
	 * Display a message in the form editor if a field has been added programmatically and cannot be edited.
	 *
	 * @since 3.0
	 *
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_form_editor_field_message( $form ) {
		$message             = '';
		$gform_post_get_meta = array( 'gform_form_post_get_meta_' . $form['id'] );
		$is_new_field        = true;

		if ( is_array( $form ) && array_key_exists( 'fields', $form ) ) {
			foreach ( $form['fields'] as $field ) {
				// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
				if ( $field->id == $this->id ) {
					$is_new_field = false;
				}
			}
		}

		if ( gf_has_filter( $gform_post_get_meta ) && ! isset( $this->layoutGroupId ) && ! $is_new_field ) {
			$message = '<div class="gform-alert gform-alert--info gform-alert--theme-cosmos gform-spacing gform-spacing--bottom-5 gform-theme__disable">
								<span
									class="gform-icon gform-icon--information-simple gform-icon--preset-active gform-icon-preset--status-info gform-alert__icon"
									aria-hidden="true"
								></span>
								<div class="gform-alert__message-wrap">
									<div class="gform-alert__message">
										' . __( 'Uneditable Repeater Field', 'gravityforms' ) . '
										<div class="gform-spacing gform-spacing--top-1">' . sprintf(
											// translators: %1$s: the link to the documentation, %2$s: the link to the documentation.
											__( 'This field was added to the form programmatically, and changes you make to it in the form editor will not be saved. %1$sLearn how to make this field editable.%2$s', 'gravityforms' ),
											'<a href="https://docs.gravityforms.com/make-repeater-editable" target="_blank">',
											'<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'gravityforms' ) . '</span>&nbsp;<span class="gform-icon gform-icon--external-link"></span></a>'
										) . '</div>
									</div>
								</div>
							</div>';
		}

		return $message;
	}

	/**
	 * Generates the HTML for the empty state of the repeater list.
	 *
	 * @since 3.0.0
	 *
	 * @param int $field_id The ID of the repeater field.
	 *
	 * @return string The HTML for the empty list placeholder.
	 */
	public static function get_empty_wrapper( $field = null ) {
		if ( ! $field  ) {
			$field = new self;
		}

		$inner_html = '<div class="gfield-repeater-spacer" id="#spacer_id" data-groupid="#space_group_id" style="grid-column: span 6"></div>
			<div class="gform-box gform-repeater-fields-list-empty-wrapper">
				<span class="gform-empty-repeater-text">' . esc_html__( 'Drag Fields Here', 'gravityforms' ) . '</span>
			</div>' . $field->get_buttons( array() );

		return sprintf(
			'<div class="gfield_repeater_items"><div class="gfield_repeater_item gform-repeater-fields-list" data-repeater-field-id="%s">%s</div></div>',
			esc_attr( $field->id ),
			$inner_html
		);
	}

	/**
	 * Returns the markup for the top of the repeater container.
	 *
	 * This method must return the opening tag for the container and this tag must have the class 'gfield_repeater_container'
	 *
	 * @since 2.4
	 *
	 * @param $values
	 *
	 * @return string
	 */
	public function get_input_top( $values ) {
		$html                = $this->get_repeater_template( $this->id, $values );
		$html               .= "<div class='gfield_repeater gfield_repeater_container'>\n";
		$label               = esc_html( $this->label );
		$required_div        = $this->isRequired ? sprintf( "<span class='gfield_required'>%s</span>", $this->get_required_indicator() ) : '';
		$admin_hidden_markup = ( $this->visibility === 'hidden' ) ? $this->get_hidden_admin_markup() : '';
		$html               .= "{$admin_hidden_markup}<legend class='gfield_label gform-field-label'><span><span class='gform-field-label__text'>{$label}</span>{$required_div}</span></legend>";
		return $html;
	}

	/**
	 * Returns the markup for the items.
	 *
	 * This method must return a single HTML element with the class 'gfield_repeater_items'. This element must contain
	 * all the items as direct children and each item must have the class 'gfield_repeater_item'.
	 *
	 * @since 2.4
	 *
	 * @param $values
	 * @param $entry
	 *
	 * @return string
	 */
	public function get_input_items( $values, $entry ) {
		if ( empty( $this->fields ) ) {
			return '';
		}

		$rows = '<div class="gfield_repeater_items">';

		foreach ( $values as $index => $value ) {
			$row   = $this->get_repeater_row( $index, $values, $value, $entry );
			$rows .= $row;
		}

		$rows .= '</div>';

		return $rows;
	}

	/**
	 * Returns the markup for a single repeater row.
	 *
	 * @since 3.0
	 *
	 * @param int   $i        The index of the row.
	 * @param array $values   The values of the repeater field.
	 * @param array $value    The value of the current row.
	 * @param array $entry    The Entry Object currently being processed.
	 * @param bool  $template Whether this is a template row.
	 *
	 * @return string
	 */
	public function get_repeater_row( $i, $values, $value, $entry, $template = false ) {
		$fields = $this->fields;
		if ( empty( $fields ) ) {
			return '';
		}

		$form = $this->get_context_property( 'form' );

		if ( $template ) {
			// get the nesting level, and add '-{ID}' to the index for each nesting level
			$nesting_level = $this->nestingLevel;
			$index         = '{ID}';
			for ( $i = 0; $i < $nesting_level; $i++ ) {
				$index .= '-{ID}';
			}
			$i = $index;
		}

		$row = "<div class='gfield_repeater_item gform-repeater-fields-list' data-repeater-field-id='{$this->id}' data-repeater-index='{$i}' data-nesting-level='{$this->nestingLevel}'>";

		foreach ( $fields as $field ) {
			if ( ! $template ) {
				$this->restore_sub_field_validation_result( $field, $i );
			}

			$field->set_context_property( 'itemIndex', $i );
			$field_value = $this->get_field_value( $field, $value );
			$field_input = $this->get_sub_field_input( $field, $form, $field_value, $entry, $i, $template );

			$row .= $field_input;

			$field->set_context_property( 'itemIndex', null );
		}

		$buttons = $this->get_buttons( $values );
		$row    .= $buttons;
		$row    .= '</div>';

		return $row;
	}

	/**
	 * Populates the sub field properties with the cached validation result for the current index.
	 *
	 * @since 3.0
	 *
	 * @param GF_Field $field The current sub field.
	 * @param int      $index The current row index.
	 *
	 * @return void
	 */
	public function restore_sub_field_validation_result( $field, $index ) {
		$results = $field->get_context_property( 'repeater_validation_results' );
		if ( is_null( $results ) ) {
			return;
		}

		$result                    = rgar( $results, $index );
		$field->failed_validation  = isset( $result['is_valid'] ) && ! $result['is_valid'];
		$field->validation_message = rgar( $result, 'message' );
		$field->set_context_property( 'failed_state_validation', rgar( $result, 'failed_state_validation' ) );
		$field->set_context_property( 'is_empty_1', rgar( $result, 'is_empty_1' ) );
	}

	/*
	 * Return the repeater template, which is cloned to create new repeater items.
	 *
	 * @since 3.0
	 *
	 * @param string $field_id The ID of the repeater field.
	 * @return string The HTML template for the repeater.
	 */
	public function get_repeater_template( $field_id, $values ) {
		if ( $this->is_form_editor() ) {
			return '';
		}

		// If this is a nested repeater, we don't need a template.
		if ( $this->nestingLevel > 0 ) {
			return '';
		}

		$template    = "<div class='gfield_repeater_template' data-repeater-template-id='{$field_id}' style='display:none;'>";
		$first_value = reset( $values );
		$template   .= $this->get_repeater_row( '{ID}', $values, $first_value, null, true );
		$template   .= '</div>';

		// Replace the name attribute with template_name so that it won't be submitted in the POST data.
		$template = str_replace( 'name=', 'template_name=', $template );

		return $template;
	}

	/**
	 * Return the markup for the bottom of the repeater. Close the tags opened in the top.
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function get_input_bottom() {
		return '</div>';
	}

	public function get_field_content( $value, $force_frontend_label, $form ) {

		$field_content = '{FIELD}';
		if ( $this->is_form_editor() ) {
			$field_content = $this->get_admin_buttons() . '{FIELD}';
		}

		return $field_content;
	}

	/**
	 * Returns the repeater buttons.
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function get_buttons( $values ) {
		$is_form_editor = $this->is_form_editor();

		$delete_display = count( $values ) === 1 ? 'visibility:hidden;' : '';

		$add_events    = $is_form_editor ? '' : "onclick='gformAddRepeaterItem(this)'";
		$delete_events = $is_form_editor ? '' : sprintf( "onclick='if(confirm(\"%s\")){gformDeleteRepeaterItem(this)};'", esc_js( __( 'Are you sure you want to remove this item?', 'gravityforms' ) ) );

		$disabled_icon_class = ! empty( $this->maxItems ) && count( $values ) >= intval( $this->maxItems ) ? 'gfield_icon_disabled' : '';

		$add_button_text    = $this->addButtonText ? $this->addButtonText : esc_html__( 'Add', 'gravityforms' );
		$remove_button_text = $this->removeButtonText ? $this->removeButtonText : esc_html__( 'Remove', 'gravityforms' );

		$add_button_class    = $this->addButtonText ? 'add_repeater_item_text' : 'add_repeater_item_plus';
		$remove_button_class = $this->removeButtonText ? 'remove_repeater_item_text' : 'remove_repeater_item_minus';

		$add_button_data = "data-repeater-id='{$this->id}'";

		return "<div class='gfield_repeater_buttons'>
					<button type='button' class='add_repeater_item gform-theme-button gform-theme-button--size-sm add_repeater_item {$disabled_icon_class}' {$add_events} {$add_button_data}>{$add_button_text}</button>
		        	<button type='button' class='remove_repeater_item gform-theme-button gform-theme-button--size-sm remove_repeate_item' {$delete_events} style='{$delete_display}'>{$remove_button_text}</button>
		        	</div>";
	}

	/**
	 * Return the maximum number of repeats description.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_limit_message() {
		$text = $this->get_limit_message_text();
		if ( ! $text ) {
			return '';
		}

		$form_id = $this->formId;

		$id = $this->id;
		return "<div class='gfield_repeater_max_message gfield_description' id='gfield_repeater_max_message_{$form_id}_{$id}'>{$text}</div>";
	}

	/**
	 * Get the description for the maximum number of repeats, otherwise return blank.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_limit_message_text() {
		if ( empty( $this->maxItems ) ) {
			return '';
		}

		$max = intval( $this->maxItems );

		// translators: %s: the maximum number of repeated fields.
		return sprintf( esc_html__( 'Add up to %s repeated fields.', 'gravityforms' ), "<strong>$max</strong>" );
	}

	/**
	 * Gets merge tag values.
	 *
	 * @since 2.4
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

		$modifiers = $this->get_modifiers();
		$use_value = in_array( 'value', $modifiers );
		$use_text  = ! $use_value;

		// :count modifier returns the number of items in the repeater
		if ( in_array( 'count', $modifiers ) ) {
			$merge_tag = is_array( $raw_value ) ? count( $raw_value ) : 0;
			return $merge_tag;
		}

		// :text modifier forces text format
		if ( in_array( 'text', $modifiers ) || in_array( 'value', $modifiers ) ) {
			$format = 'text';
		}

		// if the $modifiers array contains anything that starts with 'field_ids=', get the field ID after 'field_ids=' and use it as the input ID
		$field_ids = array();
		foreach ( $modifiers as $mod ) {
			if ( strpos( $mod, 'field_ids=' ) === 0 ) {
				$field_ids = substr( $mod, 10 );
				// if field_ids contains separators, convert it to an array
				if ( strpos( $field_ids, '|' ) !== false ) {
					$field_ids = explode( '|', $field_ids );
				} else {
					$field_ids = array( $field_ids );
				}
			}
		}

		if ( $format === 'html' ) {
			$merge_tag = $this->get_value_recursive( $raw_value, $entry, $use_text, $format, $field_ids, 'merge_tag' );
		} else {
			$value_only = in_array( 'value', $modifiers );
			$merge_tag  = $this->get_value_export_recursive( $entry, $input_id, $use_text, false, 0, '&nbsp;&nbsp;&nbsp;&nbsp;', $field_ids, $value_only );
		}

		return $merge_tag;
	}

	/**
	 * Format the entry value safe for displaying on the entry list page.
	 *
	 * @since 2.4
	 *
	 * @param string $value    The field value.
	 * @param array  $entry    The Entry Object currently being processed.
	 * @param string $field_id The field or input ID currently being processed.
	 * @param array  $columns  The properties for the columns being displayed on the entry list page.
	 * @param array  $form     The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {

		/* translators: %d: the number of items in value of the repeater field. */
		$display_value = is_array( $value ) ? sprintf( esc_html__( 'Number of items: %d', 'gravityforms' ), count( $value ) ) : '';

		return $display_value;
	}

	/**
	 * Format the entry value safe for displaying on the entry detail page.
	 *
	 * @since 2.4
	 * @since 2.9.29 Changed the second parameter $currency (string) to $entry (array).
	 * @since 3.0    Added the $field_ids parameter to allow filtering of sub-fields.
	 *
	 * @param string|array $item_values The field value.
	 * @param array        $entry       The entry.
	 * @param bool|false   $use_text    When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format      The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param string       $media       The location where the value will be displayed. Possible values: screen or email.
	 * @param array        $field_ids    An array of field IDs to include in the output. If empty, all sub-fields are included.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $item_values, $entry = array(), $use_text = false, $format = 'html', $media = 'screen' ) {
		return $this->get_value_recursive( $item_values, $entry, $use_text, $format, array(), 'entry_detail' );
	}

	/**
	 * Returns the field value formatted for the {all_fields} merge tag.
	 *
	 * @since 3.0
	 *
	 * @param string|array $value    The field value.
	 * @param array        $entry    The entry.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format   The format requested for the location the merge is being used. Possible values: html, text or url.
	 *
	 * @return string|false
	 */
	public function get_value_all_fields_merge_tag( $value, $entry, $use_text, $format ) {
		return $this->get_value_recursive( $value, $entry, $use_text, $format, array(), 'all_fields_merge_tag' );
	}

	/**
	 * Format the entry value safe for displaying on the entry detail page and for the {all_fields} merge tag.
	 *
	 * @since 2.4
	 * @since 2.9.29 Changed the second parameter $currency (string) to $entry (array).
	 * @since 3.0    Added the $field_ids parameter to allow filtering of sub-fields.
	 *
	 * @param string|array $item_values The field value.
	 * @param array        $entry       The entry.
	 * @param bool|false   $use_text    When processing choice based fields should the choice text be returned instead of the value.
	 * @param string       $format      The format requested for the location the merge is being used. Possible values: html, text or url.
	 * @param array        $field_ids   An array of field IDs to include in the output. If empty, all sub-fields are included.
	 * @param string       $context     The location/context where the value will be displayed. Possible values: 'entry_detail', 'all_fields_merge_tag', 'merge_tag'.
	 *
	 * @return string
	 */
	public function get_value_recursive( $item_values, $entry = array(), $use_text = false, $format = 'html', $field_ids = array(), $context = 'entry_detail', $already_filtered = false ) {
		if ( empty( $item_values ) || ! is_array( $item_values ) ) {
			return '';
		}
		if ( $format === 'text' ) {
			return $this->get_value_export_recursive( array( $this->id => $item_values ), $this->id, $use_text, false, 0, '&nbsp;&nbsp;&nbsp;&nbsp;' );
		}
		$fields = $this->fields;
		if ( ! empty( $field_ids ) && ! $already_filtered ) {
			$fields = $this->filter_fields_by_id( $fields, $field_ids );
			$already_filtered = true;
		}
		$html = '';
		foreach ( $item_values as $key => $item_value ) {
			// Adds a margin-bottom if this is not the last item
			end( $item_values );
			$last_key         = key( $item_values );
			$is_last_item     = $last_key === $key;
			$margin_bottom    = $is_last_item ? 'margin-bottom: 10px;' : '';
			$table_attributes = $context === 'entry_detail' ? "class='entry-details-table' style='box-shadow:none; {$margin_bottom}' cellspacing='0'" : "style='width: 100%;background-color: #FFFFFF;border: 1px solid #EAEAEA;padding: 5px;'";
			$item_html        = '';

			foreach ( $fields as $sub_field ) {
				// Skip password fields for consistency with main entry detail display
				if ( $sub_field->get_input_type() === 'password' ) {
					continue;
				}

				// Skip product fields in entry detail - they are shown in the order summary.
				if ( $context === 'entry_detail' && GFCommon::is_product_field( $sub_field->type ) ) {
					continue;
				}

				if ( $sub_field->fields ) {
					$sub_field_value = rgar( $item_value, $sub_field->id );
				} else {
					$sub_field_value = $this->get_field_value( $sub_field, $item_value );
				}
				$label = wp_kses( $sub_field->get_field_label( true, $item_values ), wp_kses_allowed_html( 'post' ) );

				if ( $sub_field->type === 'repeater' ) {
					if ( $context === 'entry_detail' ) {
						$sub_field->displayEmptyFields = $this->displayEmptyFields;

						// If the child repeater only contains product fields, skip it entirely.
						if ( $sub_field->has_only_product_fields() ) {
							continue;
						}
					}
					$value = wp_kses( $sub_field->get_value_recursive( $sub_field_value, $entry, $use_text, $format, $field_ids, $context, $already_filtered ), wp_kses_allowed_html( 'post' ) );
				} elseif ( $context === 'all_fields_merge_tag' ) {
					$value = wp_kses( $sub_field->get_value_all_fields_merge_tag( $sub_field_value, $entry, $use_text, $format, $field_ids ), wp_kses_allowed_html( 'post' ) );
				} else {
					$value = wp_kses( $sub_field->get_value_entry_detail( $sub_field_value, $entry, $use_text, $format ), wp_kses_allowed_html( 'post' ) );
				}

				if ( $this->displayEmptyFields || ! rgblank( $value ) ) {

					if ( $context === 'entry_detail' ) {
						$item_html .= "	<tr>
										<td class='entry-view-field-name'>{$label}</td>
									</tr>
									<tr>
										<td class='entry-view-field-value'>{$value}</td>
									</tr>";
					} else {
						$item_html .= "<tr style='border-bottom: 1px solid #EAEAEA;'>
										<td style='font-family: sans-serif; font-size:12px; padding: 8px 16px; '>
											<strong>{$label}</strong>
										</td>
									</tr>
									<tr style='background-color:#FFFFFF; border-bottom: 1px solid #EAEAEA;'>
										<td style='font-family: sans-serif; font-size:12px; padding: 16px;'>
											{$value}
										</td>
									</tr>";
					}
				}
			}

			// Only render the item table if it has visible content.
			if ( ! empty( $item_html ) ) {
				$html .= "<table {$table_attributes}><tbody>{$item_html}</tbody></table>";
				if ( ! $is_last_item ) {
					$html .= "<hr style='border:0;border-top:10px solid #EAF2FA;margin:20px 0;'>";
				}
			}
		}
		return $html;
	}

	/**
	 * Checks if the repeater only contains product/pricing fields recursively.
	 *
	 * @since 3.0.0
	 *
	 * @return bool True if the repeater only contains product/pricing fields or child repeaters that only contain product fields.
	 */
	public function has_only_product_fields() {
		if ( ! is_array( $this->fields ) ) {
			return false;
		}

		foreach ( $this->fields as $sub_field ) {
			if ( $sub_field->type === 'repeater' ) {
				if ( ! $sub_field->has_only_product_fields() ) {
					return false;
				}
			} elseif ( ! GFCommon::is_product_field( $sub_field->type ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Recursively filter fields and their sub_fields by the field IDs specified in a merge tag.
	 *
	 * @since 3.0
	 *
	 * @param array $fields     Array of field objects.
	 * @param array $field_ids  Allowed field IDs.
	 * @return array            Filtered array of field objects.
	 */
	private function filter_fields_by_id( $fields, $field_ids ) {
		$filtered = array();
		foreach ( $fields as $field ) {
			$cloned_field = clone $field;
			if ( $cloned_field->type === 'repeater' ) {
				if ( in_array( $cloned_field->id, $field_ids ) ) {
					// If repeater's ID is in $field_ids, include it with all subfields (no filtering)
					$filtered[] = $cloned_field;
				} else {
					// Otherwise, recursively filter subfields
					$cloned_field->fields = $this->filter_fields_by_id( $cloned_field->fields, $field_ids );
					if ( is_array( $cloned_field->fields ) && ! empty( $cloned_field->fields ) ) {
						$filtered[] = $cloned_field;
					}
				}
			} else {
				if ( in_array( $cloned_field->id, $field_ids ) ) {
					$filtered[] = $cloned_field;
				}
			}
		}
		return $filtered;
	}

	/**
	 * Returns the value for a field inside a repeater.
	 *
	 * @since 2.4
	 * @since 3.0 Updated to return null when the field failed state validation.
	 *
	 * @param GF_Field $field
	 * @param array|string $value
	 *
	 * @return array|string|null
	 */
	public function get_field_value( $field, $value ) {
		if ( $field->get_context_property( 'failed_state_validation' ) ) {
			return null;
		}

		$field_id = $field->id;

		if ( $field->fields ) {
			$field_value = isset( $value[ $field_id ] ) ? $value[ $field_id ] : '';
		} else {
			$inputs = $field->get_repeater_inputs();
			if ( is_array( $value ) ) {
				if ( is_array( $inputs ) ) {
					$field_value = array();
					$field_keys  = array_keys( $value );
					natsort( $field_keys );
					foreach ( $field_keys as $input_id ) {
						if ( is_numeric( $input_id ) && absint( $input_id ) === $field_id ) {
							$field_value[ $input_id ] = $value[ $input_id ];
						}
					}
				} else {
					$field_value = isset( $value[ $field_id ] ) ? $value[ $field_id ] : '';
				}
			} else {
				$field_value = '';
			}
		}

		return $field_value;
	}

	/**
	 * Returns the input markup for a field inside a repeater.
	 *
	 * Appends the item index to the name and id attributes and validates the value.
	 *
	 * @since 2.4
	 *
	 * @param GF_Field $field
	 * @param array $form
	 * @param array $field_value
	 * @param array $entry
	 * @param int $index
	 *
	 * @return mixed
	 */
	public function get_sub_field_input( $field, $form, $field_value, $entry, $index, $is_template = false ) {
		$field_content = $this->get_sub_field_content( $field, $field_value, $form, $entry );

		if ( $this->is_form_editor() ) {
			return $field_content;
		}

		if ( ! class_exists( 'WP_HTML_Processor' ) ) {
			require_once ABSPATH . WPINC . '/html-api/class-wp-html-processor.php';
		}

		$html_processor = WP_HTML_Processor::create_fragment( $field_content );

		$html_processor->next_tag( array( 'class' => 'gfield' ) );
		$html_processor->set_bookmark( 'repeater_start' );

		// Adjust name attributes for input elements
		while ( $html_processor->next_tag() ) {
			$name = $html_processor->get_attribute( 'name' );
			if ( $name && str_starts_with( $name, 'input_' ) ) {
				$new_name_attr = $this->update_bracket_attribute( $name, $index, $is_template );
				$html_processor->set_attribute( 'name', $new_name_attr );
			}
		}

		$html_processor->seek( 'repeater_start' );

		// Adjust id attributes for all elements
		do {
			$id = $html_processor->get_attribute( 'id' );
			if ( $id && ( str_starts_with( $id, 'field_' ) || str_starts_with( $id, 'input_' ) || str_starts_with( $id, 'choice_' ) ) ) {
				$new_id_attr = $this->update_dash_attribute( $id, $index, $is_template );
				$html_processor->set_attribute( 'id', $new_id_attr );
			}
		} while ( $html_processor->next_tag() );

		$html_processor->seek( 'repeater_start' );

		// Adjust for attributes for label elements
		while ( $html_processor->next_tag( array( 'tag_name' => 'label' ) ) ) {
			$for = $html_processor->get_attribute( 'for' );
			if ( $for && ( str_starts_with( $for, 'input_' ) || str_starts_with( $for, 'choice_' ) ) ) {
				$new_for_attr = $this->update_dash_attribute( $for, $index, $is_template );
				$html_processor->set_attribute( 'for', $new_for_attr );
			}
		}

		if ( $field->failed_validation ) {
			$html_processor->seek( 'repeater_start' );
			// Adjust aria-describedby attributes
			while ( $html_processor->next_tag() ) {
				$aria_describedby = $html_processor->get_attribute( 'aria-describedby' );
				if ( $aria_describedby && str_starts_with( $aria_describedby, 'validation_message_' ) ) {
					$new_aria_attr = $this->update_dash_attribute( $aria_describedby, $index, $is_template );
					$html_processor->set_attribute( 'aria-describedby', $new_aria_attr );
				}
			}

			$html_processor->seek( 'repeater_start' );
			// Adjust validation message IDs
			while ( $html_processor->next_tag() ) {
				$id = $html_processor->get_attribute( 'id' );
				if ( $id && str_starts_with( $id, 'validation_message_' ) ) {
					$new_id_attr = $this->update_dash_attribute( $id, $index, $is_template );
					$html_processor->set_attribute( 'id', $new_id_attr );
				}
			}
		} else {
			$html_processor->seek( 'repeater_start' );
			// Add gfield_valid class to the field wrapper
			while ( $html_processor->next_tag() ) {
				$class = $html_processor->get_attribute( 'class' );
				if ( $class && str_starts_with( $class, 'gfield ' ) ) {
					$html_processor->set_attribute( 'class', $class . ' gfield_valid' );
				}
			}
		}

		/*
		 * Filter the sub field input HTML.
		 *
		 * @since 3.0.0
		 *
		 * @param string    $updated_input The updated HTML for the sub field input.
		 * @param GF_Field  $field         The sub field object.
		 * @param array     $form          The form object.
		 * @param mixed     $field_value   The value of the sub field.
		 * @param array     $entry         The entry object.
		 * @param int|string $index        The index of the repeater item.
		 * @param bool      $is_template   Whether this is a template row.
		 */
		return apply_filters( 'gform_repeater_sub_field_input', $html_processor->get_updated_html(), $field, $form, $field_value, $entry, $index, $is_template );
	}

	/**
	 * Helper function to handle attribute updates for bracket-based attributes (like name attributes).
	 *
	 * @since 3.0
	 *
	 * @param string $attribute_value The current attribute value.
	 * @param string $index The index to insert.
	 * @param bool $is_template Whether this is a template.
	 * @param int $nesting_level The nesting level of the repeater.
	 *
	 * @return string The updated attribute value.
	 */
	private function update_bracket_attribute( $attribute_value, $index, $is_template = false ) {
		if ( $is_template ) {
			$attribute_value = str_replace( '[0]', "[{$index}]", $attribute_value );
		}

		// Find the first bracketed index (e.g., [0])
		if ( preg_match( '/(\[\d*\])/', $attribute_value, $matches, PREG_OFFSET_CAPTURE ) ) {
			$pos = $matches[1][1];
			// Insert [index] before the first bracketed index
			return substr( $attribute_value, 0, $pos ) . "[{$index}]" . substr( $attribute_value, $pos );
		} else {
			// No brackets found, just append
			return $attribute_value . "[{$index}]";
		}
	}

	/**
	 * Helper function to handle attribute updates for dash-based attributes (like id, for, aria-describedby attributes).
	 *
	 * @since 3.0
	 *
	 * @param string $attribute_value The current attribute value.
	 * @param string $index The index to insert.
	 * @param bool $is_template Whether this is a template.
	 *
	 * @return string The updated attribute value.
	 */
	private function update_dash_attribute( $attribute_value, $index, $is_template = false ) {
		if ( $is_template ) {
			$attribute_value = str_replace( '-0', "-{$index}", $attribute_value );
		}

		// Check if attribute already has index pattern (like field_1_2-1)
		if ( preg_match( '/-(\d+)/', $attribute_value ) ) {
			// Insert the index before the first dash-number pattern
			return preg_replace( '/^([^-]+)(-\d+.*)$/', "$1-{$index}$2", $attribute_value );
		} else {
			// No existing index, just append the index
			return $attribute_value . "-{$index}";
		}
	}

	/**
	 * Validates the sub field.
	 *
	 * @since 2.9.10
	 * @deprecated 3.0
	 * @remove-in 5.0
	 *
	 * @param GF_Field $field
	 * @param string   $field_value
	 * @param array    $form
	 */
	public function validate_subfield( $field, $field_value, $form ) {
		$field->failed_validation = false;
		if ( $field->isRequired && $field->is_value_empty( $field_value ) ) {
			$field->failed_validation  = true;
			$field->validation_message = empty( $field->errorMessage ) ? __( 'This field is required.', 'gravityforms' ) : $field->errorMessage;
		}

		if ( ! $field->failed_validation ) {
			$field->validate( $field_value, $form );
		}

		$context                   = GFFormDisplay::get_submission_context();
		$custom_validation_result  = gf_apply_filters(
			array( 'gform_field_validation', $form['id'], $field->id ),
			array(
				'is_valid' => $field->failed_validation ? false : true,
				'message'  => $field->validation_message,
			),
			$field_value,
			$form,
			$field,
			$context
		);
		$field->failed_validation  = rgar( $custom_validation_result, 'is_valid' ) ? false : true;
		$field->validation_message = rgar( $custom_validation_result, 'message' );
	}

	/**
	 * Returns the markup for the sub field.
	 *
	 * @since 2.4
	 *
	 * @param GF_Field     $field
	 * @param string|array $value
	 * @param array        $form
	 * @param array        $entry
	 *
	 * @return string
	 */
	public function get_sub_field_content( $field, $value, $form, $entry ) {

		$validation_status = $field->failed_validation;

		if ( ! class_exists( 'GFFormDisplay' ) ) {
			require_once GFCommon::get_base_path() . '/form_display.php';
		}

		return GFFormDisplay::get_field( $field, $value, true, $form );
	}

	/**
	 * Builds the repeater's array of items.
	 *
	 * @since 2.4
	 * @since 2.5 Added the $apply_filters parameter.
	 *
	 * @param      $entry
	 * @param bool $apply_filters Whether to apply the filter_input_value filter to the entry.
	 *
	 * @return mixed
	 */
	public function hydrate( $entry, $apply_filters = false ) {
		$entry[ $this->id ] = $this->get_repeater_items( $entry, '', '', $apply_filters );
		return $entry;
	}

	/**
	 * Recursively converts the repeater values from flattened values in the entry array into a multidimensional array
	 * of items.
	 *
	 * @since 2.4
	 * @since 2.5 Added the $apply_filters parameter.
	 *
	 * @param array             $entry
	 * @param GF_Field_Repeater $repeater_field
	 * @param string            $index
	 * @param bool              $apply_filters Whether to apply the filter_input_value filter to the entry.
	 *
	 * @return array
	 */
	public function get_repeater_items( &$entry, $repeater_field = null, $index = '', $apply_filters = false ) {
		if ( ! $repeater_field ) {
			$repeater_field = $this;
		}

		$items           = array();
		$repeater_fields = array();

		// Build a map of field_id => field so that we can build the array of items in the expected order
		$field_map = array();
		foreach ( $repeater_field->fields as $field ) {
			$field_map[ $field->id ] = $field;
			if ( is_array( $field->fields ) ) {
				$repeater_fields[] = $field;
			}
		}

		// Iterate over $entry keys to find repeater values
		foreach ( $entry as $key => $value ) {
			// Match keys like "1_0", "2_1", "4_0_0", "4.1_0", etc.
			if ( preg_match( '/^(\d+)(?:\.\d+)?' . preg_quote( $index, '/' ) . '_(\d+)/', $key, $matches ) ) {
				$field_id   = $matches[1];
				$item_index = $matches[2];

				if ( isset( $field_map[ $field_id ] ) ) {
					$field = $field_map[ $field_id ];

					$inputs = $field->get_repeater_inputs();

					// Handle multi-input fields
					if ( is_array( $inputs ) ) {
						foreach ( $inputs as $input ) {
							$input_id  = (string) $input['id'];
							$input_key = $input_id . $index . '_' . $item_index;

							if ( isset( $entry[ $input_key ] ) ) {
								$items[ $item_index ][ $input_id ] = $apply_filters ? $field->filter_input_value( $entry[ $input_key ], $entry ) : $entry[ $input_key ];
								unset( $entry[ $input_key ] );
							}
						}
					} else {
						$items[ $item_index ][ $field_id ] = $apply_filters ? $field->filter_input_value( $value, $entry ) : $value;
						unset( $entry[ $key ] );
					}
				}
			}
		}

		// Handle nested repeaters
		if ( ! empty( $repeater_fields ) ) {
			// Get all existing parent item indices
			$parent_indices = array_keys( $items );

			// Also scan entry keys to find indices that only have nested repeater values.
			foreach ( $entry as $key => $value ) {
				if ( preg_match( '/^\d+(?:\.\d+)?' . preg_quote( $index, '/' ) . '_(\d+)_/', $key, $matches ) ) {
					$item_index = (int) $matches[1];
					if ( ! in_array( $item_index, $parent_indices, true ) ) {
						$parent_indices[] = $item_index;
					}
				}
			}

			if ( ! empty( $parent_indices ) ) {
				$max_index = max( $parent_indices );

				for ( $i = 0; $i <= $max_index; $i++ ) {
					foreach ( $repeater_fields as $repeater ) {
						$v        = $this->get_repeater_items( $entry, $repeater, $index . '_' . $i );
						$is_empty = $this->empty_deep( $v );
						if ( ! $is_empty ) {
							$items[ $i ][ $repeater->id ] = $v;
						}
					}
				}
			}
		}

		return $items;
	}

	/**
	 * Recursively checks whether a multi-dimensional array is empty.
	 *
	 * @since 2.4
	 *
	 * @param $val
	 *
	 * @return bool
	 */
	public function empty_deep( $val ) {

		$result = true;

		if ( is_array( $val ) && count( $val ) > 0 ) {
			foreach ( $val as $v ) {
				$result = $result && $this->empty_deep( $v );
			}
		} else {
			$result = $val === '' || $val === null || ( is_array( $val ) && empty( $val ) );
		}

		return $result;
	}

	/**
	 * Returns the sub-filters for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_sub_filters() {
		$filters = array();
		$fields  = $this->fields;

		if ( ! $fields ) {
			return $filters;
		}
		foreach ( $fields as $field ) {
			/** @var GF_Field $field */
			$filter_settings = array(
				'key'  => $field->id,
				'text' => GFFormsModel::get_label( $field, false, true ),
			);

			if ( is_array( $field->fields ) ) {
				$filter_settings = $field->get_filter_settings();
				$filters[]       = $filter_settings;
				continue;
			}
			$sub_filters = $field->get_filter_sub_filters();

			if ( ! empty( $sub_filters ) ) {
				$filter_settings['group']   = true;
				$filter_settings['filters'] = $sub_filters;
			} else {
				$filter_settings['preventMultiple'] = false;
				$filter_settings['operators']       = $field->get_filter_operators();

				$values = $field->get_filter_values();
				if ( ! empty( $values ) ) {
					$filter_settings['values'] = $values;
				}
			}

			$values = $field->get_filter_values();
			if ( ! empty( $values ) ) {
				$filter_settings['values'] = $values;
			}

			$filters[] = $filter_settings;
		}

		return $filters;
	}

	/**
	 * Returns the filter settings for the current field.
	 *
	 * If overriding to add custom settings call the parent method first to get the default settings.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_settings() {

		$filter_settings = parent::get_filter_settings();

		$filter_settings['isNestable'] = true;

		return $filter_settings;
	}

	/**
	 * Format the entry value before it is used in entry exports and by framework add-ons using GFAddOn::get_field_value().
	 *
	 * @since 2.4
	 *
	 * @param array      $entry    The entry currently being processed.
	 * @param string     $input_id The field or input ID.
	 * @param bool|false $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param bool|false $is_csv   Is the value going to be used in the .csv entries export?
	 *
	 * @return string|array
	 */
	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		$export = $this->get_value_export_recursive( $entry, $input_id, $use_text, $is_csv );
		return $export;
	}

	/**
	 * Format the entry value before it is used in entry exports and by framework add-ons using GFAddOn::get_field_value().
	 *
	 * @since 2.4
	 * @since 3.0 Added the $field_ids and $value_only parameters.
	 *
	 * @param array        $entry    The entry currently being processed.
	 * @param string       $input_id The field or input ID.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of the value.
	 * @param bool|false   $is_csv   Is the value going to be used in the .csv entries export?
	 * @param int          $depth    The current depth of the recursion.
	 * @param string       $padding  The string used for padding based on the depth.
	 * @param string|array $field_ids The field IDs to include in the export.
	 * @param bool         $value_only Whether to return only the values without labels.
	 *
	 * @return string|array
	 */
	public function get_value_export_recursive( $entry, $input_id = '', $use_text = false, $is_csv = false, $depth = 0, $padding = '    ', $field_ids = '', $value_only = false ) {
		if ( empty( $input_id ) ) {
			$input_id = $this->id;
		}

		$items = rgar( $entry, $input_id );
		if ( empty( $items ) ) {
			return '';
		}

		/* @var GF_Field[] $fields */
		$fields = $this->fields;

		$csv       = array();
		$separator = "\n";

		foreach ( $items as $item ) {

			foreach ( $fields as $field ) {
				// Skip password fields for consistency with main entry detail display
				if ( $field->get_input_type() === 'password' ) {
					continue;
				}

				// If field_ids is set, skip fields that are not in the list
				if ( ! empty( $field_ids ) ) {
					if ( ! in_array( strval( $field->id ), $field_ids ) ) {
						continue;
					}
				}

				$inputs = $field->get_repeater_inputs();

				if ( is_array( $inputs ) ) {
					$field_value = array();
					$field_keys  = array_keys( $item );
					foreach ( $field_keys as $input_id ) {
						if ( is_numeric( $input_id ) && absint( $input_id ) === absint( $field->id ) ) {
							$field_value[ $input_id ] = $item[ $input_id ];
						}
					}
				} else {
					$field_value = isset( $item[ $field->id ] ) ? $item[ $field->id ] : '';
					$field_value = array( (string) $field->id => $field_value );
				}

				if ( $value_only ) {
					$label     = '';
					$separator = ', ';
				} else {
					$label = str_repeat( $padding, $depth ) . GFFormsModel::get_label( $field ) . ': ';
				}

				if ( is_array( $field->fields ) ) {
					$new_depth = $depth + 1;
					$line      = $label . "\n" . $field->get_value_export_recursive( $field_value, $field->id, $use_text, $is_csv, $new_depth, $padding );
					if ( $depth === 0 ) {
						$line .= "\n";
					}
				} else {

					if ( 'list' === $field->get_input_type() && ! empty( $field_value[ $field->id ] ) ) {

						$list_rows = maybe_unserialize( $field_value[ $field->id ] );

						if ( is_array( $list_rows[0] ) ) {
							$lines = array();
							foreach ( $list_rows as $i => $list_row ) {
								$row_label = str_repeat( $padding, $depth ) . GFFormsModel::get_label( $field ) . ' ' . ( $i + 1 ) . ': ';

								// Prepare row value.
								$row_value = implode( '|', $list_row );
								if ( strpos( $row_value, '=' ) === 0 ) {
									// Prevent Excel formulas
									$row_value = "'" . $row_value;
								}

								$lines[] = $row_label . $row_value;
							}
							$line = implode( "\n", $lines );
						} else {
							$value = implode( '|', $list_rows );
							if ( strpos( $value, '=' ) === 0 ) {
								// Prevent Excel formulas
								$value = "'" . $value;
							}
							$line = $label . $value;
						}
					} else {
						$line = $label . $field->get_value_export( $field_value, $field->id, $use_text, $is_csv );
					}
				}

				$csv[] = $line;
			}
		}

		return implode( $separator, $csv );
	}

	/**
	 * Store the modifiers so they can be accessed when preparing the {all_fields} and field merge tag output.
	 *
	 * @since 2.4
	 *
	 * @param array $modifiers An array of modifiers to be stored.
	 */
	public function set_modifiers( $modifiers ) {
		parent::set_modifiers( $modifiers );

		/* @var GF_Field $sub_field */
		foreach ( $this->fields as $sub_field ) {
			$sub_field->set_modifiers( $modifiers );
		}
	}
}

GF_Fields::register( new GF_Field_Repeater() );
