<?php

namespace Gravity_Forms\Gravity_Forms\Setup_Wizard\Config;

use Gravity_Forms\Gravity_Forms\Config\GF_Config;

/**
 * Config items for the Installation Wizard I18N
 *
 * @since 2.7
 */
class GF_Setup_Wizard_Config_I18N extends GF_Config {

	/**
	 * Script handle.
	 *
	 * @var string
	 */
	protected $name = 'gform_admin_config';

	/**
	 * Handle of script to be localized.
	 *
	 * @var string
	 */
	protected $script_to_localize = 'gform_gravityforms_admin_vendors';

	/**
	 * Determine if the config should enqueue its data.
	 *
	 * @since 2.7
	 *
	 * @return bool
	 */
	public function should_enqueue() {
		return \GFForms::is_gravity_page();
	}

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		return array(
			'components' => array(
				'setup_wizard' => array(
					'i18n' => array(
						// Buttons.
						'next'                  => __( 'Next', 'gravityforms' ),
						'previous'              => __( 'Previous', 'gravityforms' ),
						'close_button'          => __( 'Close', 'gravityforms' ),
						'invalid_key'           => __( 'Invalid License Key', 'gravityforms' ),
						'redirect_prompt'       => __( 'Back To Dashboard', 'gravityforms' ),
						'toggle_fullscreen'     => __( 'Toggle Fullscreen', 'gravityforms' ),

						// Screen 01.
						'welcome_title'         => __( 'Welcome to Gravity Forms', 'gravityforms' ),
						'welcome_copy'          => __( 'Thank you for choosing Gravity Forms. We know you’re going to love our form builder and all it has to offer!', 'gravityforms' ),
						'most_accessible'       => __( 'Create surveys and quizzes', 'gravityforms' ),
						'column_layouts'        => __( 'Accept online payments', 'gravityforms' ),
						'take_payments'         => __( 'Build custom business solutions', 'gravityforms' ),
						'enter_license'         => __( 'Enter License Key', 'gravityforms' ),
						'enter_license_plhdr'   => __( 'Paste your license key here', 'gravityforms' ),
						'license_instructions'  => __( 'Enter your license key below to enable Gravity Forms.', 'gravityforms' ),
						'activate_license'      => __( 'Activate License', 'gravityforms' ),
						'key_validated'         => __( 'License Key Validated', 'gravityforms' ),
						'check_license'         => __( 'Checking License', 'gravityforms' ),
						'email_message_title'   => __( 'Get 20% Off Gravity Forms!', 'gravityforms' ),
						'email_message'         => __( 'To continue installation enter your email below and get 20% off any new license.', 'gravityforms' ),
						'email_message_plhldr'  => __( 'Email address', 'gravityforms' ),
						'email_message_submit'  => __( 'Get the Discount', 'gravityforms' ),
						'email_message_footer'  => __( 'I agree to the handling and storage of my data and to receive marketing communications from Gravity Forms.', 'gravityforms' ),

						// Screen 02.
						'set_up_title'          => __( "Let's get you set up!", 'gravityforms' ),
						'set_up_copy'           => __( 'Configure Gravity Forms to work in the way that you want.', 'gravityforms' ),
						'for_client'            => __( 'Hide license information', 'gravityforms' ),
						'hide_license'          => __( 'If you\'re installing Gravity Forms for a client, enable this setting to hide the license information.', 'gravityforms' ),
						'enable_updates'        => __( 'Enable automatic updates', 'gravityforms' ),
						'enable_updates_tag'    => __( 'Recommended', 'gravityforms' ),
						'enable_updates_locked' => __( 'Feature Disabled', 'gravityforms' ),
						'updates_recommended'   => __( 'We recommend you enable this feature to ensure Gravity Forms runs smoothly.', 'gravityforms' ),
						'which_currency'        => __( 'Select a Currency', 'gravityforms' ),

						// Screen 03.
						'personalize_title'     => __( 'Personalize your Gravity Forms experience', 'gravityforms' ),
						'personalize_copy'      => __( 'Tell us about your site and how you’d like to use Gravity Forms.', 'gravityforms' ),
						'describe_organization' => __( 'How would you best describe your website?', 'gravityforms' ),
						'form_type'             => __( 'What types of forms do you want to create?', 'gravityforms' ),
						'services_connect'      => __( 'Do you want to integrate your forms with any of these services?', 'gravityforms' ),
						'other_label'           => __( 'Other', 'gravityforms' ),
						'other_placeholder'     => __( 'Description', 'gravityforms' ),

						// Screen 04.
						'help_improve_title'    => __( 'Help Make Gravity Forms Better!', 'gravityforms' ),
						// translators: placeholders are markup to create a link.
						'help_improve_copy'     => sprintf( __( 'We love improving the form building experience for everyone in our community. By enabling data collection, you can help us learn more about how our customers use Gravity Forms. %1$sLearn more...%2$s', 'gravityforms' ), '<a target="_blank" href="https://docs.gravityforms.com/about-additional-data-collection/">', '</a>' ),
						'no_thanks_button'      => __( 'No, Thanks.' ),
						'yes_button'            => __( 'Yes, Count Me In.' ),

						// Screen 05.
						'complete_title'        => __( 'Ready to Create Your First Form?', 'gravityforms' ),
						'complete_message'      => __( 'Watch the video below to help you get started with Gravity Forms, or jump straight in and begin your form building journey!', 'gravityforms' ),
						'create_form_button'    => __( 'Create Your First Form' ),
					),
				),
			),
		);
	}
}
