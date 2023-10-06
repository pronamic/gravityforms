<?php

namespace Gravity_Forms\Gravity_Forms\Blocks\Config;

use GFSettings;
use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Config_Data_Parser;
use \GFCommon;
use \GFAPI;
use \GFFormDisplay;

/**
 * Config items for Blocks.
 *
 * @since
 */
class GF_Blocks_Config extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';
	protected $attributes         = array();

	public function __construct( GF_Config_Data_Parser $parser, array $attributes ) {
		parent::__construct( $parser );
		$this->attributes = $attributes;
	}

	public function should_enqueue() {
		return GFCommon::is_block_editor_page();
	}

	/**
	 * Get list of forms for Block control.
	 *
	 * @since 2.4.10
	 *
	 * @return array
	 */
	public function get_forms() {

		// Initialize forms array.
		$forms = array();

		// Load GFFormDisplay class.
		if ( ! class_exists( 'GFFormDisplay' ) ) {
			require_once GFCommon::get_base_path() . '/form_display.php';
		}

		// Get form objects.
		$form_objects = GFAPI::get_forms( true, false, 'title', 'ASC' );

		// Loop through forms, add conditional logic check.
		foreach ( $form_objects as $form ) {
			$forms[] = array(
				'id'                  => $form['id'],
				'title'               => $form['title'],
				'hasConditionalLogic' => GFFormDisplay::has_conditional_logic( $form ),
				'isLegacyMarkup'      => GFCommon::is_legacy_markup_enabled( $form ),
			);
		}

		/**
		 * Modify the list of available forms displayed in the Form block.
		 *
		 * @since 2.4.23
		 *
		 * @param array $forms A collection of active forms on site.
		 */
		return apply_filters( 'gform_block_form_forms', $forms );

	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$attributes = apply_filters( 'gform_form_block_attributes', $this->attributes );

		$orbital_default = GFSettings::is_orbital_default();

		return array(
			'gravityforms/form' => array(
				'data' => array(
					'attributes'     => $attributes,
					'adminURL'   	 => admin_url( 'admin.php' ),
					'forms'      	 => $this->get_forms(),
					'preview'        => GFCommon::get_base_url() . '/images/gf_block_preview.svg',
					'orbitalDefault' => $orbital_default,
					'styles'     	 => array(
						'defaults' => \GFForms::get_service_container()->get( \Gravity_Forms\Gravity_Forms\Form_Display\GF_Form_Display_Service_Provider::BLOCK_STYLES_DEFAULTS ),
					),
				),
			),
		);
	}
}
