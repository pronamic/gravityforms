<?php

namespace Gravity_Forms\Gravity_Forms\Fields\Config;

use GFAPI;
use GF_Field_Address;
use Gravity_Forms\Gravity_Forms\Config\GF_Config;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Form specific config for the Address field's copy values functionality.
 *
 * @since 3.1.3
 */
class GF_Field_Address_Frontend_Config extends GF_Config {

	protected $name               = 'gform_theme_config';
	protected $script_to_localize = 'gform_gravityforms_theme';

	/**
	 * Determines if the config data should be enqueued.
	 *
	 * @since 3.1.3
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		if ( empty( $this->args['form_ids'] ) ) {
			return false;
		}

		foreach ( $this->args['form_ids'] as $form_id ) {
			if ( $this->form_has_copy_values_address( $form_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if a form has an Address field with copy values enabled.
	 *
	 * @since 3.1.3
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return bool
	 */
	private function form_has_copy_values_address( $form_id ) {
		if ( empty( $form_id ) ) {
			return false;
		}

		$form = GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field instanceof GF_Field_Address && $field->enableCopyValuesOption ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the Address field copy values configuration.
	 *
	 * @since 3.1.3
	 *
	 * @return array
	 */
	public static function get_copy_values_config() {
		return array(
			'strings' => array(
				/* translators: This message is announced by screen readers when a user sets an address to be the same as another address. 1: Source field label. 2: Destination field label. */
				'copyValuesActivated'   => esc_html__( 'The values from %1$s have been copied to %2$s and the inputs in %2$s are hidden.', 'gravityforms' ),
				/* translators: This message is announced by screen readers when a user says an address is not the same as another address. 1: Destination field label. */
				'copyValuesDeactivated' => esc_html__( 'The values for %1$s have been removed.', 'gravityforms' ),
			),
		);
	}

	/**
	 * Config data.
	 *
	 * @since 3.1.3
	 *
	 * @return array
	 */
	public function data() {
		if ( ! $this->should_enqueue() ) {
			return array();
		}

		return array(
			'fields' => array(
				'address' => self::get_copy_values_config(),
			),
		);
	}

	/**
	 * Enables AJAX loading for the Address field config path.
	 *
	 * @since 3.1.3
	 *
	 * @param string $config_path The full path to the config item.
	 * @param array  $args        The args used to load the config data.
	 *
	 * @return bool
	 */
	public function enable_ajax( $config_path, $args ) {
		return str_starts_with( $config_path, 'gform_theme_config/fields/address' );
	}
}
