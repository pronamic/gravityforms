<?php

namespace Gravity_Forms\Gravity_Forms\Form_Display\State;

use GF_Field;
use GF_Field_Repeater;
use GFFormsModel;
use GFCommon;
use GFCache;

/**
 * Class State_Handler.
 *
 * Used when creating the value for the hidden state input and validating the state during form submission.
 *
 * @since 3.0
 */
class State_Handler {

	/**
	 * The dynamic population values.
	 *
	 * @since 3.0
	 *
	 * @var array
	 */
	private $field_values = array();

	/**
	 * The values that will be hashed to create the state input value.
	 *
	 * @since 3.0
	 *
	 * @var array
	 */
	private $state_values = array();

	/**
	 * The hashes parsed from the state input value.
	 *
	 * @since 3.0
	 *
	 * @var array
	 */
	private $parsed_hashes = array();

	/**
	 * The number of times each field or input has failed state validation.
	 *
	 * @since 3.0
	 *
	 * @var array
	 */
	private $invalid_counts = array();

	/**
	 * Resets the state handler properties to their initial empty values.
	 *
	 * @since 3.0
	 *
	 * @return void
	 */
	public function flush() {
		$this->field_values   = array();
		$this->state_values   = array();
		$this->parsed_hashes  = array();
		$this->invalid_counts = array();
		$this->set_global();
	}

	/**
	 * Sets or clears the global variable (for backwards compatibility).
	 *
	 * @since 3.0
	 *
	 * @param null|array $state_values Null or the state hashes.
	 *
	 * @return void
	 */
	private function set_global( $state_values = null ) {
		if ( ! is_array( $state_values ) && ! is_null( $state_values ) ) {
			$state_values = null;
		}

		global $_gf_state;
		$_gf_state = $state_values;
	}

	/**
	 * Returns the current page URL without the gf_token query parameter.
	 *
	 * @since 3.0
	 *
	 * @return string
	 */
	public function get_url() {
		static $url;
		if ( empty( $url ) ) {
			$url = remove_query_arg( 'gf_token', GFFormsModel::get_current_page_url() );
		}

		return $url;
	}

	// # FORM DISPLAY ---------------------------------------------------------------------------------------------------

	/**
	 * Creates and returns the value for the state input.
	 *
	 * @since 3.0
	 *
	 * @param array $form              The form the state is being generated for.
	 * @param array $field_values      The dynamic population values.
	 * @param array $additional_values Any additional non-field values to add to the state.
	 *
	 * @return string
	 */
	public function create( $form, $field_values, $additional_values ) {
		$form_id                        = absint( rgar( $form, 'id' ) );
		$this->field_values[ $form_id ] = $field_values;
		$this->state_values[ $form_id ] = array();

		$this->add_fields( $form_id, rgar( $form, 'fields' ) );
		$this->add_additional_values( $form_id, $additional_values );
		$this->add_hashes( $form_id, 'form_id', wp_hash( $form_id ) );
		$this->add_url( $form_id );
		$timestamp = time();
		$this->add_hashes( $form_id, 'state_timestamp', wp_hash( $timestamp ) );

		/**
		 * Allows additional values to be added to the state before it is encoded.
		 *
		 * @since 3.0
		 *
		 * @param State_Handler $state_handler The state handler instance.
		 * @param string|int[]  $keys          The keys of the state values that will be encoded.
		 * @param int           $form_id       The ID of the form the state is being generated for.
		 */
		gf_do_action( array( 'gform_state_pre_encode', $form_id ), $this, array_keys( $this->state_values[ $form_id ] ), $form_id );

		$hash     = json_encode( $this->state_values[ $form_id ] );
		$checksum = wp_hash( crc32( $hash ) );

		return base64_encode( json_encode( array( $hash, $checksum, $timestamp ) ) );
	}

	/**
	 * Adds the current page URL to the state_values property.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id The ID of the form the state is being generated for.
	 *
	 * @return void
	 */
	private function add_url( $form_id ) {
		$url = $this->get_url();
		if ( str_contains( $url, '?' ) ) {
			$values = array(
				wp_hash( $url ),
				wp_hash( strtok( $url, '?' ) ),
			);
			$this->add_hashes( $form_id, 'url', $values );
		} else {
			$this->add_hashes( $form_id, 'url', wp_hash( $url ) );
		}
	}

	/**
	 * Hashes and adds any additional non-field values to the state_values property.
	 *
	 * @since 3.0
	 *
	 * @param int   $form_id The ID of the form the state is being generated for.
	 * @param array $values  The additional values to add.
	 *
	 * @return void
	 */
	public function add_additional_values( $form_id, $values ) {
		if ( empty( $values ) || ! is_array( $values ) ) {
			return;
		}

		foreach ( $values as $key => $value ) {
			if ( is_null( $value ) ) {
				continue;
			} elseif ( is_array( $value ) ) {
				$this->add_hashes( $form_id, $key, array_map( 'wp_hash', $value ) );
			} else {
				$this->add_hashes( $form_id, $key, wp_hash( $value ) );
			}
		}
	}

	/**
	 * Triggers adding of the field values to the state_values property.
	 *
	 * @since 3.0
	 *
	 * @param int        $form_id The ID of the form the state is being generated for.
	 * @param GF_Field[] $fields  An array of form fields.
	 *
	 * @return void
	 */
	private function add_fields( $form_id, $fields ) {
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return;
		}

		foreach ( $fields as $field ) {
			if ( $field instanceof GF_Field_Repeater ) {
				$this->add_fields( $form_id, $field->fields );
				continue;
			}

			if ( ! $field->is_state_validation_supported() ) {
				continue;
			}

			$this->add_field( $form_id, $field );
		}
	}

	/**
	 * Gets the fixed values for the given field, hashes them, and adds them to the state_values property.
	 *
	 * @since 3.0
	 *
	 * @param int      $form_id The ID of the form the state is being generated for.
	 * @param GF_Field $field   The form field to process.
	 *
	 * @return void
	 */
	private function add_field( $form_id, $field ) {
		$dynamic_pop_value = GFFormsModel::get_field_value( $field, rgars( $this->field_values, $form_id, array() ), false );

		// State validation is skipped for dynamically populated fields, so we can abort early.
		if ( ! GFCommon::is_empty_array( $dynamic_pop_value ) ) {
			return;
		}

		$default_value = $field->get_value_default();
		$values        = $field->get_values_for_state_hash( $default_value );
		if ( empty( $values ) || ! is_array( $values ) ) {
			return;
		}

		foreach ( $values as $key => $value ) {
			if ( is_array( $value ) ) {
				$hashes = array();
				foreach ( $value as $val ) {
					$hashes[] = $this->hash_field_value( $val, $field );
				}
				$this->add_hashes( $form_id, $key, $hashes );
			} else {
				$this->add_hashes( $form_id, $key, $this->hash_field_value( $value, $field ) );
			}
		}
	}

	/**
	 * Trims and hashes the given field value.
	 *
	 * @since 3.0
	 *
	 * @param string|array $value The value to hash.
	 * @param GF_Field     $field The field the value is for.
	 *
	 * @return false|string
	 */
	private function hash_field_value( $value, $field ) {
		return wp_hash( GFFormsModel::maybe_trim_input( $value, $field->formId, $field ) );
	}

	/**
	 * Populates the state_values property with the given key and hashes.
	 *
	 * @since 3.0
	 *
	 * @param int             $form_id The ID of the form the state is being generated for.
	 * @param int|string      $key     The key to use when adding the hashes.
	 * @param string|string[] $hashes  The hash or an array of hashes to add.
	 *
	 * @return void
	 */
	public function add_hashes( $form_id, $key, $hashes ) {
		if ( empty( $key ) || empty( $hashes ) ) {
			return;
		}

		$this->state_values[ $form_id ][ $key ] = $hashes;
	}

	// # FORM SUBMISSION ------------------------------------------------------------------------------------------------

	/**
	 * Parses the state input value and sets the parsed_hashes property.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id The ID of the form that is being processed.
	 *
	 * @return void
	 */
	private function parse_state_input( $form_id ) {
		if ( isset( $this->parsed_hashes[ $form_id ] ) ) {
			$this->set_global( $this->parsed_hashes[ $form_id ] );

			return;
		}

		$this->set_global();
		$this->parsed_hashes[ $form_id ] = false;

		// Init the invalid counts property for the form.
		$this->get_invalid_counts( $form_id );

		$state = rgpost( "state_{$form_id}" );
		if ( empty( $state ) || ! is_string( $state ) ) {
			$this->increment_invalid_count( $form_id, 'state_input' );
			GFCommon::log_debug( __METHOD__ . '(): Invalid input value.' );

			return;
		}

		$decoded_state = json_decode( base64_decode( $state ), true );
		if ( ! $decoded_state || ! is_array( $decoded_state ) ) {
			$this->increment_invalid_count( $form_id, 'state_input' );
			GFCommon::log_debug( __METHOD__ . '(): Decoded state is not an array.' );

			return;
		}

		$count = count( $decoded_state );
		if ( 3 !== $count ) {
			$this->increment_invalid_count( $form_id, 'state_input' );
			GFCommon::log_debug( __METHOD__ . '(): Decoded state has wrong number of elements.' );

			return;
		}

		$checksum = wp_hash( crc32( $decoded_state[0] ) );
		if ( $checksum !== $decoded_state[1] ) {
			$this->increment_invalid_count( $form_id, 'state_input' );
			GFCommon::log_debug( __METHOD__ . '(): Checksum failed validation.' );

			return;
		}

		$state_values = json_decode( $decoded_state[0], true );
		if ( ! is_array( $state_values ) ) {
			$this->increment_invalid_count( $form_id, 'state_input' );
			GFCommon::log_debug( __METHOD__ . '(): Invalid state values.' );

			return;
		}

		// Validate the state belongs to the specified form.
		if ( empty( $state_values['form_id'] ) || ! $this->value_matches_state( $form_id, $state_values['form_id'] ) ) {
			$this->increment_invalid_count( $form_id, 'state_input' );
			GFCommon::log_debug( __METHOD__ . '(): Invalid form_id.' );

			return;
		}

		if ( $this->is_stale( rgar( $decoded_state, 2 ), rgar( $state_values, 'state_timestamp' ) ) ) {
			$this->increment_invalid_count( $form_id, 'state_input' );
			GFCommon::log_debug( __METHOD__ . '(): Invalid timestamp.' );

			return;
		}

		$this->parsed_hashes[ $form_id ] = $state_values;
		$this->set_global( $state_values );
	}

	/**
	 * Determines if the state is too old.
	 *
	 * @since 3.0
	 *
	 * @param int    $timestamp The state timestamp.
	 * @param string $hash      The hashed timestamp from the state values.
	 *
	 * @return bool
	 */
	private function is_stale( $timestamp, $hash ) {
		return (
			empty( $timestamp )
			|| ! is_numeric( $timestamp )
			|| empty( $hash )
			|| ! is_string( $hash )
			|| ! $this->value_matches_state( $timestamp, $hash )
			|| (int) $timestamp <= ( time() - ( DAY_IN_SECONDS * 2 ) )
		);
	}

	/**
	 * Determines if the state input is valid.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id The ID of the form that is being processed.
	 *
	 * @return bool
	 */
	public function is_valid_state_input( $form_id ) {
		$this->parse_state_input( $form_id );

		return is_array( $this->parsed_hashes[ $form_id ] );
	}

	/**
	 * Determines if the submitted values match the state generated on form displayed.
	 *
	 * @since 3.0
	 *
	 * @param GF_Field $field  The field being validated.
	 * @param array    $values The input values to be validated.
	 *
	 * @return bool
	 */
	public function is_valid_field( $field, $values ) {
		$form_id = $field->formId;

		if ( ! $this->is_valid_state_input( $form_id ) ) {
			return false;
		}

		foreach ( $values as $key => $value ) {
			// Skip if key is not in the parsed state (e.g., singleproduct quantity input).
			if ( ! isset( $this->parsed_hashes[ $form_id ][ $key ] ) ) {
				continue;
			}

			if ( $field->skip_state_validation_if_blank( $key ) && GFCommon::is_empty_array( $value ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				foreach ( $value as $val ) {
					if ( ! $this->is_valid_field_value( $form_id, $key, $val ) ) {
						$this->increment_invalid_count( $form_id, $key );

						return false;
					}
				}
			} elseif ( ! $this->is_valid_field_value( $form_id, $key, $value ) ) {
				$this->increment_invalid_count( $form_id, $key );

				return false;
			}
		}

		return true;
	}

	/**
	 * Determines if the hash of the given value or sanitized value matches the hash parsed from the state input value.
	 *
	 * @since 3.0
	 *
	 * @param int              $form_id The ID of the form being validated.
	 * @param int|string       $key     The field or input ID.
	 * @param string|int|float $value   The value to be validated.
	 *
	 * @return bool
	 */
	private function is_valid_field_value( $form_id, $key, $value ) {
		$state = $this->parsed_hashes[ $form_id ][ $key ];
		if ( $this->value_matches_state( $value, $state ) ) {
			return true;
		}

		$sanitized_value = wp_kses( $value, wp_kses_allowed_html( 'post' ) );

		return $this->value_matches_state( $sanitized_value, $state );
	}

	/**
	 * Determines if the hash of the given value matches the hash parsed from the state input value.
	 *
	 * @since 3.0
	 *
	 * @param string|int|float $value The value to be validated.
	 * @param string|string[]  $state The hash or hashes the value is to be compared to.
	 *
	 * @return bool
	 */
	private function value_matches_state( $value, $state ) {
		$hash           = wp_hash( $value );
		$is_state_array = is_array( $state );

		return ( $is_state_array && in_array( $hash, $state, true ) ) || ( ! $is_state_array && $hash === $state );
	}

	/**
	 * Determines if the hash of the given value matches the hash parsed from the state input value.
	 *
	 * @since 3.0
	 *
	 * @param int    $form_id The ID of the form being validated.
	 * @param string $key     The key used to add the hash on form display.
	 * @param string $value   The value to be validated.
	 *
	 * @return bool
	 */
	public function is_valid_additional_value( $form_id, $key, $value ) {
		if ( ! $this->is_valid_state_input( $form_id ) ) {
			return false;
		}

		if ( ! isset( $this->parsed_hashes[ $form_id ][ $key ] ) ) {
			return true;
		}

		return $this->value_matches_state( $value, $this->parsed_hashes[ $form_id ][ $key ] );
	}

	/**
	 * Determines if the hash of the current page URL matches the hash parsed from the state input value.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id The ID of the form being validated.
	 *
	 * @return bool
	 */
	public function is_valid_url( $form_id ) {
		if ( ! $this->is_valid_state_input( $form_id ) ) {
			return false;
		}

		$url = $this->get_url();

		if ( $this->is_valid_additional_value( $form_id, 'url', $url ) ) {
			return true;
		} elseif ( str_contains( $url, '?' ) && $this->is_valid_additional_value( $form_id, 'url', strtok( $url, '?' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the cache key to be used for the invalid counts.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return string
	 */
	private function get_invalid_counts_cache_key( $form_id ) {
		return 'state_invalid_counts_' . GFFormsModel::get_form_unique_id( $form_id ) . '_' . wp_hash( (string) GFFormsModel::get_ip() );
	}

	/**
	 * Returns the counts for the specified form, setting the invalid_counts property if needed.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return array
	 */
	public function get_invalid_counts( $form_id ) {
		if ( isset( $this->invalid_counts[ $form_id ] ) ) {
			return $this->invalid_counts[ $form_id ];
		}

		$cache_key = $this->get_invalid_counts_cache_key( $form_id );
		$counts    = GFCache::get( $cache_key );
		if ( is_array( $counts ) ) {
			GFCommon::log_debug( __METHOD__ . "(): Cached counts retrieved for form #{$form_id} using key {$cache_key}." );
			foreach ( $counts as $key => $count ) {
				$clean = absint( $count );
				if ( $clean > 0 ) {
					$counts[ $key ] = $clean;
				} else {
					unset( $counts[ $key ] );
				}
			}
		} else {
			$counts = array();
		}

		$this->invalid_counts[ $form_id ] = $counts;

		return $counts;
	}

	/**
	 * Increments the invalid count for the given key.
	 *
	 * @since 3.0
	 *
	 * @param int    $form_id The form ID.
	 * @param string $key     The key (input name, field ID, or input ID) to increment.
	 *
	 * @return void
	 */
	private function increment_invalid_count( $form_id, $key ) {
		$this->invalid_counts[ $form_id ][ $key ] = ( $this->invalid_counts[ $form_id ][ $key ] ?? 0 ) + 1;
	}

	/**
	 * Caches the counts for the specified form.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return void
	 */
	public function cache_invalid_counts( $form_id ) {
		if ( empty( $this->invalid_counts[ $form_id ] ) ) {
			return;
		}

		$key = $this->get_invalid_counts_cache_key( $form_id );
		GFCommon::log_debug( __METHOD__ . "(): Caching counts for form #{$form_id} using key {$key}." );
		GFCache::set( $key, $this->invalid_counts[ $form_id ], true, DAY_IN_SECONDS );
	}

	/**
	 * Clears the cached counts for the specified form.
	 *
	 * @since 3.0
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return void
	 */
	public function clear_cached_invalid_counts( $form_id ) {
		$key = $this->get_invalid_counts_cache_key( $form_id );
		GFCommon::log_debug( __METHOD__ . "(): Clearing cached counts for form #{$form_id} using key {$key}." );
		GFCache::delete( $key );
	}

}
