<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Handles submission ability callbacks.
 *
 * @since 3.1.0
 */
class GF_Abilities_Handler_Submissions {

	/**
	 * Row errors of failed repeaters, keyed by repeater field ID.
	 *
	 * @since 3.2.2
	 *
	 * @var array
	 */
	private static $repeater_errors = array();

	/**
	 * Submit a form.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function submit_form( $input ) {
		$form = \GFAPI::get_form( absint( $input['form_id'] ) );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		$blocked = self::check_form_accepts_api_submissions( $form, $input['input_values'] );

		if ( is_wp_error( $blocked ) ) {
			return $blocked;
		}

		$field_values = isset( $input['field_values'] ) && is_array( $input['field_values'] ) ? $input['field_values'] : array();
		$input_values = self::normalize_submission_input_values( $form, $input['input_values'] );
		$input_values = self::apply_field_values( $form, $input_values, $field_values );
		$input_values = self::backfill_consent_inputs( $form, $input_values );

		// The pipeline records the current request URL as source_url; an API
		// submission has no embedding page. Clear it before notifications and
		// feeds read the entry, then detach the filter whatever happens.
		add_filter( 'gform_entry_post_save', array( __CLASS__, 'clear_source_url' ), 5 );
		add_filter( 'gform_validation', array( __CLASS__, 'capture_repeater_errors' ), 999 );

		try {
			$result = \GFAPI::submit_form( absint( $input['form_id'] ), $input_values, $field_values );
		} finally {
			remove_filter( 'gform_entry_post_save', array( __CLASS__, 'clear_source_url' ), 5 );
			remove_filter( 'gform_validation', array( __CLASS__, 'capture_repeater_errors' ), 999 );
		}

		if ( is_wp_error( $result ) ) {
			return self::plain_text_error( $result );
		}

		// Strip the full form object — it's massive and unnecessary for the caller.
		// Keep only the actionable result data.
		$output = array(
			'is_valid' => (bool) rgar( $result, 'is_valid' ),
		);

		if ( $output['is_valid'] ) {
			// Cast to int — GFAPI::submit_form() returns the entry ID as a string from the DB,
			// but MCP clients strictly validate structuredContent against the integer output schema.
			$output['entry_id']             = (int) rgar( $result, 'entry_id' );
			$output['confirmation_message'] = rgar( $result, 'confirmation_message', '' );
			$output['confirmation_type']    = rgar( $result, 'confirmation_type', 'message' );

			if ( ! empty( $result['confirmation_redirect'] ) ) {
				$output['confirmation_redirect'] = $result['confirmation_redirect'];
			}
		} else {
			$output['validation_messages'] = self::cast_validation_messages( rgar( $result, 'validation_messages', array() ) );
			$output['page_number']         = rgar( $result, 'page_number' );
		}

		return $output;
	}

	/**
	 * Strip the markup core wraps around schedule and entry-limit messages.
	 *
	 * Those messages are built for the page (<p>, <div>); an API error is text.
	 *
	 * @since 3.2.2
	 *
	 * @param \WP_Error $error The pipeline error.
	 *
	 * @return \WP_Error
	 */
	private static function plain_text_error( $error ) {
		$message = trim( wp_strip_all_tags( $error->get_error_message(), true ) );

		return new \WP_Error( $error->get_error_code(), html_entity_decode( $message, ENT_QUOTES, 'UTF-8' ), $error->get_error_data() );
	}

	/**
	 * Refuse early, with a specific message, what the pipeline would refuse vaguely or accept silently.
	 *
	 * Core answers an inactive or trashed form with "Your form could not be
	 * found", and drops a file upload value without a word; MCP cannot carry
	 * file bytes, so a value there is always a mistake.
	 *
	 * @since 3.2.2
	 *
	 * @param array $form         The form.
	 * @param array $input_values Submission values keyed by input name.
	 *
	 * @return true|\WP_Error
	 */
	private static function check_form_accepts_api_submissions( $form, $input_values ) {
		$form_id = (int) rgar( $form, 'id' );

		if ( '1' === (string) rgar( $form, 'is_trash' ) ) {
			/* translators: %d: form ID */
			return new \WP_Error( 'gf_ability_form_trashed', sprintf( __( 'Form %d is in the trash and does not accept submissions. Restore it with forms-update (is_trash: false) first.', 'gravityforms' ), $form_id ) );
		}

		if ( '0' === (string) rgar( $form, 'is_active' ) ) {
			/* translators: %d: form ID */
			return new \WP_Error( 'gf_ability_form_inactive', sprintf( __( 'Form %d is inactive and does not accept submissions. Activate it with forms-update (is_active: "1") first.', 'gravityforms' ), $form_id ) );
		}

		foreach ( rgar( $form, 'fields', array() ) as $field ) {
			if ( ! in_array( \GFFormsModel::get_input_type( $field ), array( 'fileupload', 'post_image' ), true ) ) {
				continue;
			}

			$prefix = 'input_' . $field->id;

			foreach ( (array) $input_values as $key => $value ) {
				$is_this_field = $key === $prefix || 0 === strpos( $key, $prefix . '.' ) || 0 === strpos( $key, $prefix . '_' );

				if ( $is_this_field && ! rgblank( $value ) ) {
					return new \WP_Error(
						'gf_ability_unsupported_input',
						sprintf(
							/* translators: 1: field ID, 2: field label */
							__( 'Field %1$d (%2$s) is a file upload. Files cannot be sent through this ability; omit the field, or submit the rendered form.', 'gravityforms' ),
							$field->id,
							$field->label
						)
					);
				}
			}
		}

		return self::check_repeater_input_values( rgar( $form, 'fields', array() ), (array) $input_values );
	}

	/**
	 * Reject repeater values in a shape the pipeline would drop.
	 *
	 * The pipeline reads repeater rows only from the child input names, so a
	 * value on the repeater ID is dropped and the rows are saved empty. A child
	 * of a nested repeater needs one array for each parent row; a flat array
	 * loses its rows the same way.
	 *
	 * @since 3.2.2
	 *
	 * @param array $fields       The fields to check.
	 * @param array $input_values Submission values keyed by input name.
	 * @param int   $depth        The number of repeaters around $fields.
	 *
	 * @return true|\WP_Error
	 */
	private static function check_repeater_input_values( $fields, $input_values, $depth = 0 ) {
		foreach ( $fields as $field ) {
			if ( ! isset( $field->fields ) || ! is_array( $field->fields ) ) {
				$error = $depth > 1 ? self::check_nested_child_values( $field, $input_values ) : true;

				if ( true === $error && $depth ) {
					$error = self::check_repeater_date_values( $field, $input_values, $depth );
				}

				if ( is_wp_error( $error ) ) {
					return $error;
				}

				continue;
			}

			if ( array_key_exists( 'input_' . $field->id, $input_values ) && ! empty( $field->fields ) ) {
				return new \WP_Error(
					'gf_ability_invalid_repeater_input',
					sprintf(
						/* translators: 1: repeater field ID, 2: child input name */
						__( 'Field %1$d is a repeater and takes no value of its own. Send one array of row values on each child input name, for example %2$s.', 'gravityforms' ),
						$field->id,
						'input_' . reset( $field->fields )->id
					)
				);
			}

			$result = self::check_repeater_input_values( $field->fields, $input_values, $depth + 1 );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return true;
	}

	/**
	 * Reject date strings and sub-input keys for a three-input date inside a repeater.
	 *
	 * A repeater reads such a field only as one array of date parts per row, so
	 * any other shape is dropped without an error.
	 *
	 * @since 3.2.2
	 *
	 * @param \GF_Field $field        The child field.
	 * @param array     $input_values Submission values keyed by input name.
	 * @param int       $depth        The number of repeaters around the field.
	 *
	 * @return true|\WP_Error
	 */
	private static function check_repeater_date_values( $field, $input_values, $depth ) {
		if ( 'date' !== $field->get_input_type() || ! in_array( $field->dateType, array( 'datefield', 'datedropdown' ), true ) ) {
			return true;
		}

		$key  = 'input_' . $field->id;
		$keys = preg_grep( '/^' . preg_quote( $key, '/' ) . '[._][123]$/', array_keys( $input_values ) );
		$values   = array_key_exists( $key, $input_values ) ? array( $input_values[ $key ] ) : array();
		$has_text = false;

		// Walk down to the row values; each one must be an array of parts.
		for ( $level = 0; $level <= $depth; $level++ ) {
			foreach ( $values as $value ) {
				$has_text = $has_text || ( ! is_array( $value ) && ! rgblank( $value ) );
			}

			$values = array_merge( array(), ...array_map( 'array_values', array_filter( $values, 'is_array' ) ) );
		}

		if ( ! $keys && ! $has_text ) {
			return true;
		}

		return new \WP_Error(
			'gf_ability_invalid_repeater_input',
			sprintf(
				/* translators: 1: field ID, 2: input name */
				__( 'Field %1$d is a three-input date inside a repeater. Send one array of date parts for each row on %2$s, in the order of the field date format, for example %2$s: [["01", "02", "2027"]] for mm/dd/yyyy.', 'gravityforms' ),
				$field->id,
				$key
			)
		);
	}

	/**
	 * Reject a flat value for a child field of a nested repeater.
	 *
	 * @since 3.2.2
	 *
	 * @param \GF_Field $field        The child field.
	 * @param array     $input_values Submission values keyed by input name.
	 *
	 * @return true|\WP_Error
	 */
	private static function check_nested_child_values( $field, $input_values ) {
		$prefix = 'input_' . $field->id;

		foreach ( $input_values as $key => $value ) {
			if ( $key !== $prefix && 0 !== strpos( $key, $prefix . '.' ) && 0 !== strpos( $key, $prefix . '_' ) ) {
				continue;
			}

			if ( is_array( $value ) && count( array_filter( $value, 'is_array' ) ) === count( $value ) ) {
				continue;
			}

			return new \WP_Error(
				'gf_ability_invalid_repeater_input',
				sprintf(
					/* translators: 1: field ID, 2: input name */
					__( 'Field %1$d is inside a nested repeater. Send one array of values for each parent row, for example %2$s: [["Guest A", "Guest B"], ["Guest C"]].', 'gravityforms' ),
					$field->id,
					$key
				)
			);
		}

		return true;
	}

	/**
	 * Blank the source_url of an entry created by an API submission.
	 *
	 * Attached to gform_entry_post_save only for the duration of submit_form().
	 *
	 * @since 3.2.2
	 *
	 * @param array $entry The saved entry.
	 *
	 * @return array
	 */
	public static function clear_source_url( $entry ) {
		if ( ! empty( $entry['id'] ) && '' !== (string) rgar( $entry, 'source_url' ) ) {
			\GFAPI::update_entry_property( $entry['id'], 'source_url', '' );
			$entry['source_url'] = '';
		}

		return $entry;
	}

	/**
	 * Fill dynamically populated fields from field_values.
	 *
	 * On a rendered form, field_values pre-fill the inputs and the browser posts
	 * them. The processing pipeline reads field_values only for logic and
	 * paging, so an API submission has to post the values itself. An explicit
	 * input value always wins.
	 *
	 * @since 3.2.2
	 *
	 * @param array $form         The form.
	 * @param array $input_values Submission values keyed by input name.
	 * @param array $field_values Dynamic population values keyed by parameter name.
	 *
	 * @return array
	 */
	private static function apply_field_values( $form, $input_values, $field_values ) {
		if ( empty( $field_values ) || empty( $form['fields'] ) ) {
			return $input_values;
		}

		foreach ( $form['fields'] as $field ) {
			if ( empty( $field->allowsPrepopulate ) || ! $field instanceof \GF_Field ) {
				continue;
			}

			// Any caller-supplied value for this field (input_7, input_7.3, input_7_3) wins.
			$prefix = 'input_' . $field->id;

			foreach ( array_keys( $input_values ) as $key ) {
				if ( $key === $prefix || 0 === strpos( $key, $prefix . '.' ) || 0 === strpos( $key, $prefix . '_' ) ) {
					continue 2;
				}
			}

			// Each field class resolves its own parameter(s) and shape: a scalar,
			// an array keyed by input id (name, address, checkbox, consent, ...)
			// or a list (list field). False keeps it off $_POST.
			// GF_Field_Number::get_value_submission() already cleans the number
			// (12,50 → 12.50); the pipeline must see the raw parameter instead.
			$value = 'number' === \GFFormsModel::get_input_type( $field )
				? \GFFormsModel::get_parameter_value( $field->inputName, $field_values, $field )
				: $field->get_value_submission( $field_values, false );

			$inputs = is_array( $field->inputs ) ? array_values( $field->inputs ) : array();

			if ( is_array( $value ) && is_array( $field->get_entry_inputs() ) ) {
				foreach ( $value as $input_id => $input_value ) {
					if ( ! rgblank( $input_value ) ) {
						$input_values[ 'input_' . $input_id ] = $input_value;
					}
				}
			} elseif ( is_array( $value ) && $inputs && count( $value ) === count( $inputs ) && array_keys( $value ) === range( 0, count( $value ) - 1 ) ) {
				// A positional list for a field whose inputs are posted separately
				// (email with confirmation: [email, email] for input_N and input_N.2).
				foreach ( $value as $index => $input_value ) {
					if ( ! rgblank( $input_value ) ) {
						$input_values[ 'input_' . $inputs[ $index ]['id'] ] = $input_value;
					}
				}
			} elseif ( ! rgblank( $value ) ) {
				$input_values[ $prefix ] = $value;
			}
		}

		return $input_values;
	}

	/**
	 * Validate a form submission.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function validate_submission( $input ) {
		$form = \GFAPI::get_form( absint( $input['form_id'] ) );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		$blocked = self::check_form_accepts_api_submissions( $form, $input['input_values'] );

		if ( is_wp_error( $blocked ) ) {
			return $blocked;
		}

		$field_values = isset( $input['field_values'] ) && is_array( $input['field_values'] ) ? $input['field_values'] : array();
		$input_values = self::normalize_submission_input_values( $form, $input['input_values'] );
		$input_values = self::apply_field_values( $form, $input_values, $field_values );

		add_filter( 'gform_validation', array( __CLASS__, 'capture_repeater_errors' ), 999 );

		try {
			$result = \GFAPI::validate_form( absint( $input['form_id'] ), $input_values, $field_values );
		} finally {
			remove_filter( 'gform_validation', array( __CLASS__, 'capture_repeater_errors' ), 999 );
		}

		if ( is_wp_error( $result ) ) {
			return self::plain_text_error( $result );
		}

		return array(
			'is_valid'            => (bool) rgar( $result, 'is_valid' ),
			'validation_messages' => self::cast_validation_messages( rgar( $result, 'validation_messages', array() ) ),
		);
	}

	/**
	 * Backfill the consent text and revision inputs for checked consent fields.
	 *
	 * On a rendered form these arrive as hidden inputs: the consent field markup
	 * posts the checkbox label (input .2) and the form revision ID (input .3)
	 * alongside the checkbox (input .1), and GF stores them with the entry to
	 * record exactly what the user consented to. API submissions have no
	 * rendered markup, so without this the consent audit trail is saved empty.
	 * Caller-provided values are never overridden. Consent fields inside
	 * repeaters get one value for each checked row.
	 *
	 * @since 3.1.0
	 * @since 3.2.2 Fills consent fields inside repeaters.
	 *
	 * @param array $form         The form object.
	 * @param array $input_values Normalized input values.
	 *
	 * @return array
	 */
	private static function backfill_consent_inputs( $form, $input_values ) {
		$fields = array_values( rgar( $form, 'fields', array() ) );

		// Repeater children too; their values are row arrays.
		for ( $i = 0; $i < count( $fields ); $i++ ) {
			if ( isset( $fields[ $i ]->fields ) && is_array( $fields[ $i ]->fields ) ) {
				array_push( $fields, ...array_values( $fields[ $i ]->fields ) );
			}
		}

		foreach ( $fields as $field ) {
			if ( ! is_object( $field ) || 'consent' !== $field->get_input_type() ) {
				continue;
			}

			$checked = rgar( $input_values, "input_{$field->id}.1", rgar( $input_values, "input_{$field->id}_1" ) );

			if ( empty( $checked ) ) {
				continue;
			}

			if ( ! self::has_consent_input( $input_values, $field->id, 2 ) ) {
				$input_values[ "input_{$field->id}.2" ] = self::fill_checked_rows( $checked, trim( (string) $field->checkboxLabel ) );
			}

			if ( ! self::has_consent_input( $input_values, $field->id, 3 ) ) {
				$input_values[ "input_{$field->id}.3" ] = self::fill_checked_rows( $checked, \GFFormsModel::get_latest_form_revisions_id( rgar( $form, 'id' ) ) );
			}
		}

		return $input_values;
	}

	/**
	 * Give each checked row the value, and each unchecked row a blank, keeping the row shape.
	 *
	 * @since 3.2.2
	 *
	 * @param mixed $checked The consent checkbox value, or row arrays of it.
	 * @param mixed $value   The value for a checked row.
	 *
	 * @return mixed
	 */
	private static function fill_checked_rows( $checked, $value ) {
		if ( ! is_array( $checked ) ) {
			return empty( $checked ) ? '' : $value;
		}

		foreach ( $checked as $index => $row ) {
			$checked[ $index ] = self::fill_checked_rows( $row, $value );
		}

		return $checked;
	}

	/**
	 * Check whether a consent sub-input was provided in either key style.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input_values The input values.
	 * @param int   $field_id     The consent field ID.
	 * @param int   $suffix       The input suffix (2 or 3).
	 *
	 * @return bool
	 */
	private static function has_consent_input( $input_values, $field_id, $suffix ) {
		foreach ( array( "input_{$field_id}.{$suffix}", "input_{$field_id}_{$suffix}" ) as $key ) {
			if ( isset( $input_values[ $key ] ) && '' !== $input_values[ $key ] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Cast validation messages to an object so they always JSON-encode as an object.
	 *
	 * An empty PHP array encodes as a JSON array ([]), which fails the strict
	 * structuredContent validation MCP clients run against the declared object
	 * output schema. Casting to stdClass guarantees {} for the empty case and
	 * preserves the field-ID-keyed map otherwise.
	 *
	 * @since 3.1.0
	 *
	 * @param array $messages Validation messages keyed by field ID.
	 *
	 * @return \stdClass
	 */
	private static function cast_validation_messages( $messages ) {
		$messages = is_array( $messages ) ? $messages : array();

		// A repeater reports its failures on the child fields, so its own message is empty.
		foreach ( self::$repeater_errors as $field_id => $error ) {
			if ( isset( $messages[ $field_id ] ) && rgblank( $messages[ $field_id ] ) ) {
				$messages[ $field_id ] = $error;
			}
		}

		self::$repeater_errors = array();

		return (object) $messages;
	}

	/**
	 * Keep the row errors of each failed repeater for the validation messages.
	 *
	 * Attached to gform_validation only while an ability submits or validates.
	 *
	 * @since 3.2.2
	 *
	 * @param array $validation_result The validation result.
	 *
	 * @return array
	 */
	public static function capture_repeater_errors( $validation_result ) {
		self::$repeater_errors = array();

		foreach ( rgars( $validation_result, 'form/fields', array() ) as $field ) {
			if ( empty( $field->failed_validation ) || ! isset( $field->fields ) || ! is_array( $field->fields ) ) {
				continue;
			}

			$errors = array();

			foreach ( (array) $field->get_context_property( 'validation_summary_errors' ) as $error ) {
				$errors[] = wp_strip_all_tags( rgar( $error, 'field_label' ) . ': ' . rgar( $error, 'message' ) );
			}

			self::$repeater_errors[ $field->id ] = implode( ' ', $errors );
		}

		return $validation_result;
	}

	/**
	 * Normalize submission input values for fields submitted as arrays.
	 *
	 * @since 3.1.0
	 *
	 * @param array $form         The form object.
	 * @param array $input_values Raw input values from the ability payload.
	 *
	 * @return array
	 */
	private static function normalize_submission_input_values( $form, $input_values ) {
		if ( empty( $input_values ) || ! is_array( $input_values ) ) {
			return array();
		}

		foreach ( rgar( $form, 'fields', array() ) as $field ) {
			if ( ! is_object( $field ) || ! method_exists( $field, 'is_value_submission_array' ) || ! $field->is_value_submission_array() ) {
				continue;
			}

			if ( null !== $field->get_entry_inputs() || empty( $field->inputs ) || ! is_array( $field->inputs ) ) {
				continue;
			}

			$parent_key = 'input_' . $field->id;

			if ( isset( $input_values[ $parent_key ] ) && is_array( $input_values[ $parent_key ] ) ) {
				continue;
			}

			$normalized = array_fill( 0, count( $field->inputs ), null );
			$has_values = false;

			foreach ( array_values( $field->inputs ) as $index => $input ) {
				$input_id = (string) rgar( $input, 'id' );
				$suffix   = strpos( $input_id, '.' ) !== false ? substr( strrchr( $input_id, '.' ), 1 ) : (string) ( $index + 1 );
				$keys     = array(
					'input_' . $field->id . '_' . $suffix,
					'input_' . $field->id . '.' . $suffix,
				);

				foreach ( $keys as $key ) {
					if ( ! array_key_exists( $key, $input_values ) ) {
						continue;
					}

					$normalized[ $index ] = $input_values[ $key ];
					unset( $input_values[ $key ] );
					$has_values = true;
					break;
				}
			}

			if ( $has_values ) {
				$input_values[ $parent_key ] = $normalized;
			}
		}

		return $input_values;
	}
}
