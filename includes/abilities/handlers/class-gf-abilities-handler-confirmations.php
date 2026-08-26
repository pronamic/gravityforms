<?php

namespace Gravity_Forms\Gravity_Forms\Abilities;

/**
 * Handles confirmation ability callbacks.
 *
 * Confirmations are keyed by ID on the form. Writes persist through
 * GFFormsModel::save_form_confirmations() — a targeted save that does not
 * round-trip the whole form (unlike editing confirmations through forms-update),
 * so a targeted edit can never drop fields, and delete is supported.
 *
 * @since 3.1.0
 */
class GF_Abilities_Handler_Confirmations {

	/**
	 * Load a form and its confirmations array.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed $form_id The form ID.
	 *
	 * @return array|\WP_Error array( $form, $confirmations ) or a WP_Error.
	 */
	private static function load( $form_id ) {
		$form = \GFAPI::get_form( absint( $form_id ) );

		if ( false === $form ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Form not found.', 'gravityforms' ) );
		}

		if ( is_wp_error( $form ) ) {
			return $form;
		}

		$confirmations = rgar( $form, 'confirmations', array() );

		return array( $form, is_array( $confirmations ) ? $confirmations : array() );
	}

	/**
	 * List a form's confirmations.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function list_confirmations( $input ) {
		$loaded = self::load( rgar( $input, 'form_id' ) );

		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		list( , $confirmations ) = $loaded;

		return array_values( $confirmations );
	}

	/**
	 * Create a confirmation on a form.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function create_confirmation( $input ) {
		$loaded = self::load( rgar( $input, 'form_id' ) );

		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		list( $form, $confirmations ) = $loaded;

		$settings       = isset( $input['confirmation'] ) && is_array( $input['confirmation'] ) ? $input['confirmation'] : array();
		$settings['id'] = uniqid();

		unset( $settings['isDefault'] );

		$error = self::validate( $settings );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		$settings                         = self::sanitize( $settings );
		$confirmations[ $settings['id'] ] = $settings;

		\GFFormsModel::save_form_confirmations( absint( $form['id'] ), $confirmations );

		return array(
			'success'         => true,
			'form_id'         => absint( $form['id'] ),
			'confirmation_id' => $settings['id'],
			'confirmation'    => $settings,
		);
	}

	/**
	 * Update one confirmation (partial merge by ID).
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function update_confirmation( $input ) {
		$loaded = self::load( rgar( $input, 'form_id' ) );

		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		list( $form, $confirmations ) = $loaded;

		$id = (string) rgar( $input, 'confirmation_id' );

		if ( ! isset( $confirmations[ $id ] ) ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Confirmation not found on this form.', 'gravityforms' ) );
		}

		$settings = isset( $input['confirmation'] ) && is_array( $input['confirmation'] ) ? $input['confirmation'] : array();
		unset( $settings['id'] );

		$merged       = array_merge( $confirmations[ $id ], $settings );
		$merged['id'] = $id;

		if ( array_key_exists( 'isDefault', $confirmations[ $id ] ) ) {
			$merged['isDefault'] = $confirmations[ $id ]['isDefault'];
		} else {
			unset( $merged['isDefault'] );
		}

		$error = self::validate( $merged );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		$merged               = self::sanitize( $merged );
		$confirmations[ $id ] = $merged;

		\GFFormsModel::save_form_confirmations( absint( $form['id'] ), $confirmations );

		return array(
			'success'         => true,
			'form_id'         => absint( $form['id'] ),
			'confirmation_id' => $id,
			'confirmation'    => $merged,
		);
	}

	/**
	 * Delete one confirmation.
	 *
	 * @since 3.1.0
	 *
	 * @param array $input The ability input.
	 *
	 * @return array|\WP_Error
	 */
	public static function delete_confirmation( $input ) {
		$loaded = self::load( rgar( $input, 'form_id' ) );

		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		list( $form, $confirmations ) = $loaded;

		$id = (string) rgar( $input, 'confirmation_id' );

		if ( ! isset( $confirmations[ $id ] ) ) {
			return new \WP_Error( 'gf_ability_not_found', __( 'Confirmation not found on this form.', 'gravityforms' ) );
		}

		// A form must always keep its default confirmation — GF falls back to it
		// when no conditional confirmation matches. Edit it instead of deleting.
		if ( ! empty( $confirmations[ $id ]['isDefault'] ) ) {
			return new \WP_Error( 'gf_ability_confirmation_default', __( 'The default confirmation cannot be deleted; edit it instead.', 'gravityforms' ) );
		}

		unset( $confirmations[ $id ] );

		\GFFormsModel::save_form_confirmations( absint( $form['id'] ), $confirmations );

		return array(
			'success'                 => true,
			'form_id'                 => absint( $form['id'] ),
			'deleted_confirmation_id' => $id,
		);
	}

	/**
	 * Apply the unfiltered_html kses gate to a confirmation's message.
	 *
	 * This mirrors the admin confirmation save, which the abilities write path skips. It is a no-op
	 * for users who hold unfiltered_html.
	 *
	 * @since 3.1.0
	 *
	 * @param array $confirmation The confirmation settings.
	 *
	 * @return array
	 */
	public static function sanitize( $confirmation ) {
		if ( isset( $confirmation['name'] ) && is_string( $confirmation['name'] ) ) {
			$confirmation['name'] = sanitize_text_field( $confirmation['name'] );
		}

		if ( isset( $confirmation['message'] ) && is_string( $confirmation['message'] ) ) {
			$confirmation['message'] = \GFCommon::maybe_wp_kses( $confirmation['message'] );
		}

		if ( 'redirect' === rgar( $confirmation, 'type' ) && isset( $confirmation['url'] ) && is_string( $confirmation['url'] ) ) {
			$url = $confirmation['url'];
			if ( '' !== $url && ! \GFCommon::has_merge_tag( $url ) && ! \GFCommon::is_valid_url( $url ) ) {
				$confirmation['url'] = '';
			}
		}

		return $confirmation;
	}

	/**
	 * Validate a confirmation's required, type-specific fields.
	 *
	 * @since 3.1.0
	 *
	 * @param array $confirmation The confirmation to validate.
	 *
	 * @return true|\WP_Error
	 */
	private static function validate( $confirmation ) {
		if ( '' === (string) rgar( $confirmation, 'name' ) ) {
			return new \WP_Error( 'gf_ability_invalid_confirmation', __( 'A confirmation requires a name.', 'gravityforms' ) );
		}

		$type  = rgar( $confirmation, 'type' );
		$valid = array( 'message', 'page', 'redirect' );

		if ( ! in_array( $type, $valid, true ) ) {
			return new \WP_Error(
				'gf_ability_invalid_confirmation',
				sprintf(
					/* translators: %s: the list of valid confirmation types */
					__( 'Confirmation "type" must be one of: %s.', 'gravityforms' ),
					implode( ', ', $valid )
				)
			);
		}

		if ( 'message' === $type && '' === (string) rgar( $confirmation, 'message' ) ) {
			return new \WP_Error( 'gf_ability_invalid_confirmation', __( 'A message confirmation requires a message.', 'gravityforms' ) );
		}

		if ( 'page' === $type && '' === (string) rgar( $confirmation, 'pageId' ) ) {
			return new \WP_Error( 'gf_ability_invalid_confirmation', __( 'A page confirmation requires a pageId.', 'gravityforms' ) );
		}

		if ( 'redirect' === $type ) {
			$url = (string) rgar( $confirmation, 'url' );

			if ( '' === $url ) {
				return new \WP_Error( 'gf_ability_invalid_confirmation', __( 'A redirect confirmation requires a url.', 'gravityforms' ) );
			}

			if ( ! \GFCommon::has_merge_tag( $url ) && ! \GFCommon::is_valid_url( $url ) ) {
				return new \WP_Error( 'gf_ability_invalid_confirmation', __( 'The redirect url must be a valid http(s) URL or a merge tag.', 'gravityforms' ) );
			}
		}

		return true;
	}
}
