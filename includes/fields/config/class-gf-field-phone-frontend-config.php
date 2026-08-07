<?php

namespace Gravity_Forms\Gravity_Forms\Fields\Config;

use GFAPI;
use GF_Field_Phone;
use GFForms;
use Gravity_Forms\Gravity_Forms\Config\GF_Config;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Form specific config for the Phone field's international mode frontend.
 */
class GF_Field_Phone_Frontend_Config extends GF_Config {

	protected $name               = 'gform_theme_config';
	protected $script_to_localize = 'gform_gravityforms_theme';

	/**
	 * Check if we're on the admin entry edit page.
	 *
	 * @return bool
	 */
	private function is_admin_entry_edit() {
		return is_admin() && GFForms::get_page() === 'entry_detail_edit';
	}

	/**
	 * Get the script to localize based on context.
	 *
	 * @return string
	 */
	public function script_to_localize() {
		if ( $this->is_admin_entry_edit() ) {
			return 'gform_gravityforms_admin_vendors';
		}
		return $this->script_to_localize;
	}

	/**
	 * Determines if the config data should be enqueued.
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		if ( $this->is_admin_entry_edit() ) {
			return $this->form_has_international_phone( rgget( 'id' ) );
		}

		if ( empty( $this->args['form_ids'] ) ) {
			return false;
		}

		foreach ( $this->args['form_ids'] as $form_id ) {
			if ( $this->form_has_international_phone( $form_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a form has an international phone field.
	 *
	 * @param int $form_id The form ID.
	 *
	 * @return bool
	 */
	private function form_has_international_phone( $form_id ) {
		if ( empty( $form_id ) ) {
			return false;
		}

		$form = GFAPI::get_form( $form_id );
		if ( ! $form ) {
			return false;
		}

		foreach ( $form['fields'] as $field ) {
			if ( $field instanceof GF_Field_Phone && $field->phoneFormat === 'formatted' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the international phone field configuration.
	 *
	 * @since: next
	 *
	 * @return array The configuration array for international phone field.
	 */
	public static function get_international_phone_config() {
		return array(
			'strings'      => array(
				'inputPlaceholder'           => esc_html__( 'Enter phone number', 'gravityforms' ),
				'selectCountryAriaLabel'     => esc_html__( 'Select country', 'gravityforms' ),
				'searchCountriesPlaceholder' => esc_html__( 'Search countries', 'gravityforms' ),
				'searchCountriesAriaLabel'   => esc_html__( 'Search for a country', 'gravityforms' ),
				'countryListAriaLabel'       => esc_html__( 'Country list', 'gravityforms' ),
				'noCountriesFound'           => esc_html__( 'No countries found', 'gravityforms' ),
				'oneCountryFound'            => esc_html__( '1 country found', 'gravityforms' ),
				'multipleCountriesFound'     => esc_html__( 'countries found', 'gravityforms' ),
			),
			'siteLanguage' => substr( get_locale(), 0, 2 ),
		);
	}

	/**
	 * Config data.
	 *
	 * @return array
	 */
	public function data() {
		if ( ! $this->should_enqueue() ) {
			return array();
		}

		$config_data = array();

		if ( $this->is_admin_entry_edit() ) {
			$form_id = rgget( 'id' );
			$config_data[ $form_id ] = self::get_international_phone_config();
		} else {
			foreach ( $this->args['form_ids'] as $form_id ) {
				$config_data[ $form_id ] = self::get_international_phone_config();
			}
		}

		return array(
			'fields' => array(
				'phone' => array(
					'international' => $config_data,
				),
			),
		);
	}

	/**
	 * Enable ajax loading for the "gform_theme_config/fields/phone/international" config path.
	 *
	 * @param string $config_path The full path to the config item.
	 * @param array  $args        The args used to load the config data.
	 *
	 * @return bool
	 */
	public function enable_ajax( $config_path, $args ) {
		return str_starts_with( $config_path, 'gform_theme_config/fields/phone/international' );
	}
}
