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
				'hasImageChoices'     => GFFormDisplay::has_image_choices( $form ),
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
			'block_editor' => array(
				'gravityforms/form' => array(
					'data' => array(
						'attributes'     => $attributes,
						'adminURL'   	 => admin_url( 'admin.php' ),
						'forms'      	 => $this->get_forms(),
						'preview'        => GFCommon::get_base_url() . '/images/gf_block_preview.svg',
						'orbitalDefault' => $orbital_default,
						'block_docs_url' => 'https://docs.gravityforms.com/gravity-forms-gutenberg-block/',
						'styles'     	 => array(
							'defaults' => \GFForms::get_service_container()->get( \Gravity_Forms\Gravity_Forms\Form_Display\GF_Form_Display_Service_Provider::BLOCK_STYLES_DEFAULTS ),
						),
					),
					'i18n' => array(
						'accent'                                    => esc_html__( 'Accent', 'gravityforms' ),
						'advanced'                                  => esc_html__( 'Advanced', 'gravityforms' ),
						'ajax'                                      => esc_html__( 'AJAX', 'gravityforms' ),
						'appearance'                                => esc_html__( 'Appearance', 'gravityforms' ),
						'background'                                => esc_html__( 'Background', 'gravityforms' ),
						'border'                                    => esc_html__( 'Border', 'gravityforms' ),
						'border_radius'                             => esc_html__( 'Border Radius', 'gravityforms' ),
						'button_styles'                             => esc_html__( 'Button Styles', 'gravityforms' ),
						'cancel'                                    => esc_html__( 'Cancel', 'gravityforms' ),
						'card'                                      => esc_html__( 'Card', 'gravityforms' ),
						'circle'                                    => esc_html__( 'Circle', 'gravityforms' ),
						'close'                                     => esc_html__( 'Close', 'gravityforms' ),
						'colors'                                    => esc_html__( 'Colors', 'gravityforms' ),
						'copy_and_paste_not_available'              => esc_html__( 'Copy / Paste Not Available', 'gravityforms' ),
						'copy_and_paste_requires_secure_connection' => esc_html__( 'Copy and paste functionality requires a secure connection. Reload this page using an HTTPS URL and try again.', 'gravityforms' ),
						'copy_form_styles'                          => esc_html__( 'Copy Form Styles', 'gravityforms' ),
						'custom_colors'                             => esc_html__( 'Custom Colors', 'gravityforms' ),
						'default_colors'                            => esc_html__( 'Default Colors', 'gravityforms' ),
						'description_styles'                        => esc_html__( 'Description Styles', 'gravityforms' ),
						'edit_form'                                 => esc_html__( 'Edit Form', 'gravityforms' ),
						'field_values'                              => esc_html__( 'Field Values', 'gravityforms' ),
						'font_size'                                 => esc_html__( 'Font Size', 'gravityforms' ),
						'form'                                      => esc_html__( 'Form', 'gravityforms' ),
						'form_id'                                   => esc_html__( 'Form ID: %s', 'gravityforms' ),
						'form_settings'                             => esc_html__( 'Form Settings', 'gravityforms' ),
						'form_styles'                               => esc_html__( 'Form Styles', 'gravityforms' ),
						'form_theme'                                => esc_html__( 'Form Theme', 'gravityforms' ),
						'form_style_options_not_available'          => esc_html__( 'Form style options are not available for forms that use %1$slegacy mode%2$s.', 'gravityforms' ),
						'gravity_forms'                             => esc_html__( 'Gravity Forms', 'gravityforms' ),
						'gravity_forms_25_theme'                    => esc_html__( 'Gravity Forms 2.5 Theme', 'gravityforms' ),
						'image_choice_styles'                       => esc_html__( 'Image Choice Styles', 'gravityforms' ),
						'inherit_from_default'                      => esc_html__( 'Inherit from default (%s)', 'gravityforms' ),
						'input_styles'                              => esc_html__( 'Input Styles', 'gravityforms' ),
						'insert_gform_block_title'                  => esc_html__( 'Add Block To Page', 'gravityforms' ),
						'insert_gform_block_content'                => esc_html__( 'Click or drag the Gravity Forms Block into the page to insert the form you selected. %1$sLearn More.%2$s', 'gravityforms' ),
						'in_pixels'                                 => esc_html__( 'In pixels.', 'gravityforms' ),
						'invalid_form_styles'                       => esc_html__( 'Invalid Form Styles', 'gravityforms' ),
						'learn_more_orbital'                        => esc_html__( 'Learn more about configuring your form to use Orbital.', 'gravityforms' ),
						'label_styles'                              => esc_html__( 'Label Styles', 'gravityforms' ),
						'large'                                     => esc_html__( 'Large', 'gravityforms' ),
						'medium'                                    => esc_html__( 'Medium', 'gravityforms' ),
						'no_card'                                   => esc_html__( 'No Card', 'gravityforms' ),
						'ok'                                        => esc_html__( 'OK', 'gravityforms' ),
						'orbital_theme'                             => esc_html__( 'Orbital Theme', 'gravityforms' ),
						'paste_form_styles'                         => esc_html__( 'Paste Form Styles', 'gravityforms' ),
						'paste_not_available'                       => esc_html__( 'Paste Not Available', 'gravityforms' ),
						'please_ensure_correct_format'              => esc_html__( 'Please ensure the form styles you are trying to paste are in the correct format.', 'gravityforms' ),
						'preview'                                   => esc_html__( 'Preview', 'gravityforms' ),
						'reset_defaults'                            => esc_html__( 'Reset Defaults', 'gravityforms' ),
						'restore_defaults'                          => esc_html__( 'Restore Defaults', 'gravityforms' ),
						'restore_default_styles'                    => esc_html__( 'Restore Default Styles', 'gravityforms' ),
						'select_a_form'                             => esc_html__( 'Select a Form', 'gravityforms' ),
						'select_and_display_form'                   => esc_html__( 'Select and display one of your forms.', 'gravityforms' ),
						'show_form_description'                     => esc_html__( 'Show Form Description', 'gravityforms' ),
						'show_form_title'                           => esc_html__( 'Show Form Title', 'gravityforms' ),
						'size'                                      => esc_html__( 'Size', 'gravityforms' ),
						'small'                                     => esc_html__( 'Small', 'gravityforms' ),
						'style'                                     => esc_html__( 'Style', 'gravityforms' ),
						'square'                                    => esc_html__( 'Square', 'gravityforms' ),
						'tabindex'                                  => esc_html__( 'Tabindex', 'gravityforms' ),
						'text'                                      => esc_html__( 'Text', 'gravityforms' ),
						'theme_colors'                              => esc_html__( 'Theme Colors', 'gravityforms' ),
						'the_accent_color_is_used'                  => esc_html__( 'The accent color is used for aspects such as checkmarks and dropdown choices.', 'gravityforms' ),
						'the_background_color_is_used'              => esc_html__( 'The background color is used for various form elements, such as buttons and progress bars.', 'gravityforms' ),
						'the_selected_form_deleted'                 => esc_html__( 'The selected form has been deleted or trashed. Please select a new form.', 'gravityforms' ),
						'this_will_restore_defaults'                => esc_html__( 'This will restore your form styles back to their default values and cannot be undone. Are you sure you want to continue?', 'gravityforms' ),
						'you_must_have_one_form'                    => esc_html__( 'You must have at least one form to use the block.', 'gravityforms' ),
						'your_browser_no_permission_to_paste'       => __( 'Your browser does not have permission to paste from the clipboard. <p>Please navigate to <strong>about:config</strong> and change the preference <strong>dom.events.asyncClipboard.readText</strong> to <strong>true</strong>.', 'gravityforms' ),
					),
				),

			)
		);
	}
}
