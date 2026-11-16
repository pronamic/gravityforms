<?php

namespace Gravity_Forms\Gravity_Forms\Fields\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use GFForms;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Frontend config for the Repeater field.
 */
class GF_Field_Repeater_Frontend_Config extends GF_Config {

	protected $name = 'gform_theme_config';

	/**
	 * Get the script to localize based on context.
	 *
	 * @return string
	 */
	public function script_to_localize() {
		return GFForms::get_page() === 'entry_detail_edit' ? 'gform_gravityforms_admin' : 'gform_gravityforms_theme';
	}
	/**
	 * Config data.
	 *
	 * @return array
	 */
	public function data() {
		return array(
			'fields' => array(
				'repeater' => array(
					'strings' => array(
						'item_added'     => esc_html__( 'added.', 'gravityforms' ),
						'item_removed'   => esc_html__( 'removed.', 'gravityforms' ),
						/* Translators: this will complete the sentence "Repeater item {number} removed and above renumbered." */
						'renumbered'     => esc_html__( 'and above renumbered.', 'gravityforms' ),
						'max_items'      => esc_html__( 'You have reached the maximum number of repeated items.', 'gravityforms' ),
						/* Translators: Do not translate text inside curly braces.  {label} will be replaced with the field label and {number} will be replaced with the item number. */
						'confirm_delete' => esc_html__( 'Are you sure you want to remove {label} {number}?', 'gravityforms' ),
					),
				),
			),
		);
	}
}

