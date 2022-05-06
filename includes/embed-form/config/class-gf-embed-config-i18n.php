<?php

namespace Gravity_Forms\Gravity_Forms\Embed_Form\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * Config items for the Embed Form I18N
 *
 * @since 2.6
 */
class GF_Embed_Config_I18N extends GF_Config {

	protected $name               = 'gform_admin_config';
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.6.2
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \GFCommon::is_form_editor();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'embed_form' => array(
					'i18n' => array(
						'title'                         => esc_html__( 'Embed Form', 'gravityforms' ),
						'id'                            => esc_html__( 'Form ID: %s', 'gravityforms' ),
						'add_title'                     => esc_html__( 'Add to Existing Content', 'gravityforms' ),
						'add_post_type_choice_label'    => esc_html__( '%1$sAdd to Existing Content:%2$s %3$s', 'gravityforms' ),
						'add_dropdown_placeholder'      => esc_html__( 'Select a %s', 'gravityforms' ),
						'add_trigger_aria_text'         => esc_html__( 'Select a post', 'gravityforms' ),
						'add_search_aria_text'          => esc_html__( 'Search all %ss', 'gravityforms' ),
						'add_button_label'              => esc_html__( 'Insert Form', 'gravityforms' ),
						'create_title'                  => esc_html__( 'Create New', 'gravityforms' ),
						'create_post_type_choice_label' => esc_html__( '%1$sCreate New:%2$s %3$s', 'gravityforms' ),
						'create_placeholder'            => esc_html__( 'Enter %s Name', 'gravityforms' ),
						'create_button_label'           => esc_html__( 'Create', 'gravityforms' ),
						'dialog_title'                  => esc_html__( 'Unsaved Changes', 'gravityforms' ),
						'dialog_content'                => esc_html__( 'Oops! You have unsaved changes in the form, before you can continue with embedding it please save your changes.', 'gravityforms' ),
						'dialog_confirm_text'            => esc_html__( 'Save Changes', 'gravityforms' ),
						'dialog_confirm_saving'          => esc_html__( 'Saving', 'gravityforms' ),
						'dialog_cancel_text'            => esc_html__( 'Cancel', 'gravityforms' ),
						'dialog_close_title'            => esc_html__( 'Close this dialog and return to form editor.', 'gravityforms' ),
						'shortcode_title'               => esc_html__( 'Not Using the Block Editor?', 'gravityforms' ),
						'shortcode_description'         => esc_html__( 'Copy and paste the shortcode within your page builder.', 'gravityforms' ),
						'shortcode_button_label'        => esc_html__( 'Copy Shortcode', 'gravityforms' ),
						'shortcode_button_copied'       => esc_html__( 'Copied', 'gravityforms' ),
						'shortcode_helper'              => esc_html__( '%1$sLearn more%2$s about the shortcode.', 'gravityforms' ),
					),
				),
			),
		);
	}
}
