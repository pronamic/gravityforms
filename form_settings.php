<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

use Gravity_Forms\Gravity_Forms\Settings\Settings;

/**
 * Class GFFormSettings
 *
 * Handles the form settings page.
 *
 * @since Unknown
 */
class GFFormSettings {

	/**
	 * Stores the current instance of the Settings renderer.
	 *
	 * @since 2.5
	 *
	 * @var false|Gravity_Forms\Gravity_Forms\Settings\Settings
	 */
	private static $_settings_renderer = false;

	/**
	 * Determines which form settings page to display.
	 *
	 * @since  Unknown
	 *
	 * @return void
	 */
	public static function form_settings_page() {

		$subview = rgget( 'subview' ) ? rgget( 'subview' ) : 'settings';

		switch ( $subview ) {
			case 'settings':
				self::form_settings_ui();
				break;
			case 'confirmation':
				require_once( 'includes/class-confirmation.php' );
				self::page_header( __( 'Confirmations', 'gravityforms' ) );
				GF_Confirmation::render_page();
				self::page_footer();
				break;
			case 'notification':
				self::notification_page();
				break;
			case 'personal-data':
				self::personal_data_page();
				break;
			default:
                /**
                 * Fires when the settings page view is determined
                 *
                 * Used to add additional pages to the form settings
                 *
                 * @since Unknown
                 *
                 * @param string $subview Used to complete the action name, allowing an additional subview to be detected
                 */
				do_action( "gform_form_settings_page_{$subview}" );
		}

	}

	/**
	 * Displays the form settings UI.
	 *
	 * @since  Unknown
	 *
	 * @return void
	 */
	public static function form_settings_ui() {
		$form_id = rgget( 'id' );

		// Form ID isn't valid (it's either deleted or just not an existing form ID)
		if ( ! GFAPI::form_id_exists( $form_id ) ) {
			GFCommon::log_error( __METHOD__ . '(): Invalid Form ID: ' . $form_id );
			wp_die( 'Invalid Form ID' );
		}

		self::page_header();

		if ( ! self::get_settings_renderer() ) {
			self::initialize_settings_renderer();
		}

		self::get_settings_renderer()->render();

		self::page_footer();
	}

	/**
	 * Prepare form settings fields.
	 *
	 * @since 2.5
	 * @since 2.9.8 Updated honeypotAction default to spam.
	 *
	 * @param array $form Form being edited.
	 *
	 * @return array
	 */
	public static function form_settings_fields( $form ) {

		// Handles the deprecation notice for the confirmation ready classes in the CSS class field of form settings.
		$deprecated_confirmation_classes_field_notice = function( $value, $field ) use ( $form ) {
			if ( GFCommon::is_legacy_markup_enabled_og( $form ) ){
				return false;
			}

			$deprecated_confirmation_classes = [
				'gf_confirmation_simple_yellow',
				'gf_confirmation_simple_gray',
				'gf_confirmation_yellow_gradient',
				'gf_confirmation_green_gradient',
			];

			if ( in_array( $value, $deprecated_confirmation_classes ) ) {
				return '<div id="gfield-warning-deprecated" class="gform-alert gform-alert--notice gform-alert--inline" role="alert" style="margin-block-start: 1rem;">
					<span class="gform-alert__icon gform-icon gform-icon--circle-notice-fine" aria-hidden="true"></span>
					<div class="gform-alert__message-wrap">
						<p class="gform-alert__message">' . esc_html__( 'This form uses the "' . $value . '" Ready Class, which will be removed in Gravity Forms 3.1. You can use a CSS code snippet instead.', 'gravityforms' ) .
					   ' <a href="https://docs.gravityforms.com/migrating-your-forms-from-ready-classes/" target="_blank" title="' .
					   esc_attr__( 'Deprecation of Ready Classes in Gravity Forms 3.1', 'gravityforms' ) . '">' .
					   esc_html__( 'Learn more', 'gravityforms' ) .
					   '<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'gravityforms' ) . '</span>&nbsp;' .
					   '<span class="gform-icon gform-icon--external-link"></span></a></p>
					</div>
				</div>';
			}
			return '';
		};

		$fields = array(
			'form_basics' => array(
				'title'  => esc_html__( 'Form Basics', 'gravityforms' ),
				'fields' => array(
					array(
						'name'                => 'title',
						'type'                => 'text',
						'label'               => esc_html__( 'Form Title', 'gravityforms' ),
						'tooltip'             => gform_tooltip( 'form_title', '', true ),
						'required'            => true,
						'validation_callback' => function( $field, $value ) use ( $form ) {

							// If value is empty, set error.
							if ( rgblank( $value ) ) {
								$field->set_error( rgobj( $field, 'error_message' ) );
								return;
							}

							// Get forms.
							$forms = GFFormsModel::get_forms();

							// Loop through forms, look for duplicate title.
							foreach ( $forms as $f ) {

								// If form does not have a duplicate title, skip.
								if ( strtolower( $f->title ) !== strtolower( $value ) ) {
									continue;
								}

								// If form ID matches, skip.
								if ( (int) $form['id'] === (int) $f->id ) {
									continue;
								}

								// Set field error.
								$field->set_error( esc_html__( 'The form title you have entered has already been used. Please enter a unique form title.', 'gravityforms' ) );

								return;

							}

							$field->do_validation( $value );
						},
					),
					array(
						'name'       => 'description',
						'type'       => 'textarea',
						'label'      => esc_html__( 'Form Description', 'gravityforms' ),
						'tooltip'    => gform_tooltip( 'form_description', '', true ),
						'allow_html' => true,
					),
				),
			),
			'form_layout' => array(
				'title'  => esc_html__( 'Form Layout', 'gravityforms' ),
				'fields' => array(
					array(
						'name'          => 'labelPlacement',
						'type'          => 'select',
						'label'         => esc_html__( 'Label Placement', 'gravityforms' ),
						'default_value' => 'top_label',
						'tooltip'       => gform_tooltip( 'form_label_placement', '', true ),
						'choices'       => array(
							array(
								'label' => __( 'Top aligned', 'gravityforms' ),
								'value' => 'top_label',
							),
							array(
								'label' => __( 'Left aligned', 'gravityforms' ),
								'value' => 'left_label',
							),
							array(
								'label' => __( 'Right aligned', 'gravityforms' ),
								'value' => 'right_label',
							),
						),
					),
					array(
						'name'          => 'descriptionPlacement',
						'type'          => 'select',
						'label'         => esc_html__( 'Description Placement', 'gravityforms' ),
						'default_value' => 'below',
						'tooltip'       => gform_tooltip( 'form_description_placement', '', true ),
						'dependency'    => array(
							'live'   => true,
							'fields' => array(
								array(
									'field'  => 'labelPlacement',
									'values' => array( 'top_label' ),
								),
							),
						),
						'choices'       => array(
							array(
								'label' => __( 'Below inputs', 'gravityforms' ),
								'value' => 'below',
							),
							array(
								'label' => __( 'Above inputs', 'gravityforms' ),
								'value' => 'above',
							),
						),
					),
					array(
						'name'          => 'validationPlacement',
						'type'          => 'select',
						'label'         => esc_html__( 'Validation Message Placement', 'gravityforms' ),
						'default_value' => 'below',
						'tooltip'       => gform_tooltip( 'form_validation_placement', '', true ),
						'choices'       => array(
							array(
								'label' => __( 'Below inputs', 'gravityforms' ),
								'value' => 'below',
							),
							array(
								'label' => __( 'Above inputs', 'gravityforms' ),
								'value' => 'above',
							),
						),
					),
					array(
						'name'    => 'subLabelPlacement',
						'type'    => 'select',
						'label'   => esc_html__( 'Sub-Label Placement', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'form_sub_label_placement', '', true ),
						'choices' => array(
							array(
								'label' => __( 'Below inputs', 'gravityforms' ),
								'value' => 'below',
							),
							array(
								'label' => __( 'Above inputs', 'gravityforms' ),
								'value' => 'above',
							),
						),
					),
					array(
						'name'          => 'validationSummary',
						'type'          => 'toggle',
						'label'         => esc_html__( 'Validation Summary', 'gravityforms' ),
						'default_value' => false,
						'tooltip'       => gform_tooltip( 'validation_summary', '', true ),
					),
					array(
						'name'          => 'requiredIndicator',
						'label'         => esc_html__( 'Required Field Indicator', 'gravityforms' ),
						'type'          => 'radio',
						'default_value' => ( GFCommon::is_legacy_markup_enabled( $form ) ) ? 'asterisk' : 'text',
						'horizontal'    => true,
						'tooltip'       => gform_tooltip( 'form_required_indicator', '', true ),
						'choices'       => array(
							array(
								'label' => esc_html__( 'Text: (Required)', 'gravityforms' ),
								'value' => 'text',
							),
							array(
								'label' => esc_html__( 'Asterisk: *', 'gravityforms' ),
								'value' => 'asterisk',
							),
							array(
								'label' => esc_html__( 'Custom:', 'gravityforms' ),
								'value' => 'custom',
							),
						),
					),
					array(
						'name'          => 'customRequiredIndicator',
						'type'          => 'text',
						'label'         => esc_html__( 'Custom Required Indicator', 'gravityforms' ),
						'default_value' => esc_html__( '(Required)', 'gravityforms' ),
						'dependency'    => array(
							'live'      => true,
							'fields'    => array(
								array(
									'field'  => 'requiredIndicator',
									'values' => array( 'custom' ),
								),
							),
						),
					),
					array(
						'name'        => 'cssClass',
						'type'        => 'text',
						'after_input' => $deprecated_confirmation_classes_field_notice,
						'label'       => esc_html__( 'CSS Class Name', 'gravityforms' ),
						'tooltip'     => gform_tooltip( 'form_css_class', '', true ),
					),
				),
			),
			'form_button' => array(
				'title'  => esc_html__( 'Form Button', 'gravityforms' ),
				'fields' => array(
					array(
						'name' => 'deprecated',
						'type' => 'html',
						'html' => esc_html__( 'Form button settings are now located in the form editor! To edit the button settings, go to the form editor and click on the submit button.', 'gravityforms' ),
					),
				),
			),
			'save_and_continue' => array(
				'title'  => esc_html__( 'Save and Continue', 'gravityforms' ),
				'fields' => array(
					array(
						'name'  => 'saveEnabled',
						'type'  => 'toggle',
						'label' => __( 'Enable Save and Continue', 'gravityforms' ),
					),
					array(
						'name'          => 'saveButtonText',
						'type'          => 'text',
						'label'         => esc_html__( 'Link Text', 'gravityforms' ),
						'default_value' => __( 'Save & Continue', 'gravityforms' ),
						'dependency'    => array(
							'live'   => true,
							'fields' => array(
								array(
									'field' => 'saveEnabled',
								),
							),
						),
						'description'   => sprintf(
							'<div class="alert warning"><p>%s</p><p>%s</p></div>',
							esc_html( 'This feature stores potentially private and sensitive data on this server and protects it with a unique link which is displayed to the user on the page in plain, unencrypted text. The link is similar to a password so it\'s strongly advisable to ensure that the page enforces a secure connection (HTTPS) before activating this setting.', 'gravityforms' ),
							esc_html( 'When this setting is activated two confirmations and one notification are automatically generated and can be modified in their respective editors. When this setting is deactivated the confirmations and the notification will be deleted automatically and any modifications will be lost.', 'gravityforms' )
						),
					),
				),
			),
			'restrictions' => array(
				'title'  => esc_html__( 'Restrictions', 'gravityforms' ),
				'fields' => array(
					array(
						'name'    => 'limitEntries',
						'type'    => 'checkbox',
						'label'   => __( 'Limit number of entries', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'form_limit_entries', '', true ),
						'choices' => array(
							array(
								'name'  => 'limitEntries',
								'label' => __( 'Enable entry limit', 'gravityforms' ),
							),
						),
						'fields'  => array(
							array(
								'name'       => 'limitEntriesNumber',
								'type'       => 'text_and_select',
								'label'      => __( 'Number of Entries', 'gravityforms' ),
								'dependency' => array(
									'live'   => true,
									'fields' => array(
										array(
											'field' => 'limitEntries',
										),
									),
								),
								'inputs'     => array(
									'text'   => array(
										'name'       => 'limitEntriesCount',
										'input_type' => 'number',
									),
									'select' => array(
										'name'    => 'limitEntriesPeriod',
										'choices' => array(
											array(
												'label' => __( 'total entries', 'gravityforms' ),
												'value' => '',
											),
											array(
												'label' => __( 'per day', 'gravityforms' ),
												'value' => 'day',
											),
											array(
												'label' => __( 'per week', 'gravityforms' ),
												'value' => 'week',
											),
											array(
												'label' => __( 'per month', 'gravityforms' ),
												'value' => 'month',
											),
											array(
												'label' => __( 'per year', 'gravityforms' ),
												'value' => 'year',
											),
										),
									),
								),
							),
							array(
								'name'       => 'limitEntriesMessage',
								'type'       => 'textarea',
								'label'      => esc_html__( 'Entry Limit Reached Message', 'gravityforms' ),
								'allow_html' => true,
								'dependency' => array(
									'live'   => true,
									'fields' => array(
										array(
											'field' => 'limitEntries',
										),
									),
								),
							),
						),
					),
					array(
						'name'    => 'scheduleForm',
						'type'    => 'checkbox',
						'label'   => __( 'Schedule Form', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'form_schedule_form', '', true ),
						'choices' => array(
							array(
								'name'  => 'scheduleForm',
								'label' => __( 'Schedule Form', 'gravityforms' ),
							),
						),
						'fields'  => array(
							array(
								'name'       => 'scheduleStart',
								'type'       => 'date_time',
								'label'      => esc_html__( 'Schedule Start Date/Time', 'gravityforms' ),
								'dependency' => array(
									'live'   => true,
									'fields' => array(
										array(
											'field' => 'scheduleForm',
										),
									),
								),
							),
							array(
								'name'       => 'scheduleEnd',
								'type'       => 'date_time',
								'label'      => esc_html__( 'Schedule Form End Date/Time', 'gravityforms' ),
								'dependency' => array(
									'live'   => true,
									'fields' => array(
										array(
											'field' => 'scheduleForm',
										),
									),
								),
							),
							array(
								'name'       => 'schedulePendingMessage',
								'type'       => 'textarea',
								'label'      => esc_html__( 'Form Pending Message', 'gravityforms' ),
								'allow_html' => true,
								'dependency' => array(
									'live'   => true,
									'fields' => array(
										array(
											'field' => 'scheduleForm',
										),
									),
								),
							),
							array(
								'name'       => 'scheduleMessage',
								'type'       => 'textarea',
								'label'      => esc_html__( 'Form Expired Message', 'gravityforms' ),
								'allow_html' => true,
								'dependency' => array(
									'live'   => true,
									'fields' => array(
										array(
											'field' => 'scheduleForm',
										),
									),
								),
							),

						),
					),
					array(
						'name'    => 'requireLogin',
						'type'    => 'checkbox',
						'label'   => __( 'Require user to be logged in', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'form_require_login', '', true ),
						'choices' => array(
							array(
								'name'  => 'requireLogin',
								'label' => __( 'Require user to be logged in', 'gravityforms' ),
							),
						),
						'fields'  => array(
							array(
								'name'       => 'requireLoginMessage',
								'type'       => 'textarea',
								'label'      => esc_html__( 'Require Login Message', 'gravityforms' ),
								'tooltip'    => gform_tooltip( 'form_require_login_message', '', true ),
								'allow_html' => true,
								'dependency' => array(
									'live'   => true,
									'fields' => array(
										array(
											'field' => 'requireLogin',
										),
									),
								),
							),
						),
					),
				),
			),
			'form_options' => array(
				'title'  => esc_html__( 'Form Options', 'gravityforms' ),
				'fields' => array(
					array(
						'name'    => 'enableHoneypot',
						'type'    => 'toggle',
						'label'   => esc_html__( 'Anti-spam honeypot', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'form_honeypot', '', true ),
					),
					array(
						'name'          => 'honeypotAction',
						'type'          => 'radio',
						'default_value' => 'spam',
						'horizontal'    => true,
						'label'         => esc_html__( 'If the honeypot flags a submission as spam:', 'gravityforms' ),
						'dependency'    => array(
							'live'   => true,
							'fields' => array(
								array(
									'field' => 'enableHoneypot',
								),
							),
						),
						'choices'       => array(
							array(
								'label' => esc_html__( 'Do not create an entry', 'gravityforms' ),
								'value' => 'abort',
							),
							array(
								'label' => esc_html__( 'Create an entry and mark it as spam', 'gravityforms' ),
								'value' => 'spam',
							),
						),
					),
					array(
						'name'    => 'enableAnimation',
						'type'    => 'toggle',
						'label'   => __( 'Animated transitions', 'gravityforms' ),
						'tooltip' => gform_tooltip( 'form_animation', '', true ),
					),
				),
			),
		);

		if ( self::show_legacy_markup_setting() ) {
			$fields['form_options']['fields'][] = array(
				'name'          => 'markupVersion',
				'type'          => 'toggle',
				'label'         => __( 'Enable legacy markup', 'gravityforms' ),
				'description'   => self::legacy_markup_warning(),
				'default_value' => rgar( $form, 'markupVersion' ) ? $form['markupVersion'] : 1,
				'tooltip'       => gform_tooltip( 'form_legacy_markup', '', true ),
			);
		}

		/**
		 * Filters the form settings before they are displayed.
		 *
		 * @deprecated
		 * @remove-in 3.0
		 * @since 1.7
		 *
		 * @param array $form_settings The form settings.
		 * @param array $form          The Form Object.
		 */

		if ( has_filter( 'gform_form_settings' ) ) {
			trigger_error( 'gform_form_settings is deprecated and will be removed in version 3.0.', E_USER_DEPRECATED );
		}
		$legacy_settings = apply_filters( 'gform_form_settings', array(), $form );

		// If legacy settings exist, add to fields.
		if ( ! empty( $legacy_settings ) ) {

			// Add section.
			$fields['legacy_settings'] = array(
				'title'  => esc_html__( 'Legacy Settings', 'gravityforms' ),
				'fields' => array(
					array(
						'name' => 'legacy',
						'type' => 'html',
						'html' => function() {
							$form_id         = rgget( 'id' );
							$form            = GFFormsModel::get_form_meta( $form_id );
							$legacy_settings = apply_filters( 'gform_form_settings', array(), $form );
							$html            = '<table class="gforms_form_settings" cellspacing="0" cellpadding="0" width="100%">';
							foreach ( $legacy_settings as $title => $legacy_fields ) {
								$html .= sprintf( '<tr><td colspan="2"><h4 class="gf_settings_subgroup_title">%s</h4></td>', esc_html( $title ) );
								if ( is_array( $legacy_fields ) ) {
									foreach ( $legacy_fields as $field ) {
										$html .= $field;
									}
								}
							}
							$html .= '</table>';

							return $html;
						},
					),
				),
			);

		}

		/**
		 * Filters the form settings fields before they are displayed.
		 *
		 * @since 2.5
		 *
		 * @param array $fields Form settings fields.
		 * @param array $form   Form Object.
		 */
		$fields = gf_apply_filters( array( 'gform_form_settings_fields', rgar( $form, 'id' ) ), $fields, $form );

		return $fields;

	}

	/**
	 * Determine whether to show the legacy markup setting.
	 *
	 * @since 2.7.15
	 *
	 * @return bool
	 */
	public static function show_legacy_markup_setting() {
		$show_legacy_setting = true;

		if ( version_compare( get_option( 'rg_form_original_version', '1.0' ), '2.7.14.2', '>=' ) && ! self::legacy_is_in_use() ) {
			$show_legacy_setting = false;
		}
		// if this is a new install, and if there are no forms with legacy markup enabled, do not show the legacy markup setting
		return apply_filters( 'gform_show_legacy_markup_setting', $show_legacy_setting );
	}

	/**
	 * Check whether any forms on this site use legacy markup.
	 *
	 * @since 2.7.15
	 *
	 * @return bool
	 */
	public static function legacy_is_in_use() {
		$legacy_is_in_use = GFCache::get( 'legacy_is_in_use', $found_in_cache );

		if ( ! $found_in_cache ) {
			$legacy_is_in_use = GFFormsModel::has_legacy_markup();

			GFCache::set( 'legacy_is_in_use', $legacy_is_in_use, true,  DAY_IN_SECONDS );
		}

		return $legacy_is_in_use;
	}

	/**
	 * Get the warning for the legacy markup field.
	 *
	 * @since 2.7.15
	 *
	 * @return string
	 */
	public static function legacy_markup_warning() {
		return '<div class="gform-alert" data-js="gform-alert" role="status">
		    <span
		        class="gform-alert__icon gform-icon gform-icon--campaign"
		        aria-hidden="true"
		    ></span>
		    <div class="gform-alert__message-wrap">
		        <p class="gform-alert__message">' . esc_html__( 'Legacy markup is incompatible with many new features, including the Orbital Theme.', 'gravityforms' ) . '</p>
		        <p class="gform-alert__message">' . esc_html__( 'Legacy markup will be removed in Gravity Forms 3.1.0, and then all forms will use modern markup.  We recommend using modern markup on all forms.', 'gravityforms' ) . '</p>
			    <a
		            class="gform-alert__cta gform-button gform-button--white gform-button--size-xs"
			        href="https://docs.gravityforms.com/about-legacy-markup"
			        target="_blank"
			    >'
			        . esc_html__( 'Learn More', 'gravityforms' ) .
			   		'<span class="screen-reader-text">' . esc_html__('about form legacy markup', 'gravityforms') . '</span>
					<span class="screen-reader-text">' . esc_html__('(opens in a new tab)', 'gravityforms') . '</span>&nbsp;
					<span class="gform-icon gform-icon--external-link"></span>
				</a>
		    </div>
		</div>';
	}

	/**
	 * Displays a warning if confirmation deprecated CSS Ready Classes are used in the form settings.
	 *
	 * This method checks if the form uses any deprecated CSS Ready Classes and displays
	 * a warning message. It also ensures the warning is not shown if the user has dismissed it.
	 *
	 * @since 2.9.15
	 *
	 * @param array $form The form object being checked for deprecated classes.
	 *
	 * @return string|false The HTML for the warning message or false if no warning is needed.
	 */
	public static function deprecated_classes_warning( $form ) {
		if ( GFCommon::is_legacy_markup_enabled_og( $form ) ){
			return false;
		}

		$deprecated_confirmation_classes = [
			'gf_confirmation_simple_yellow',
			'gf_confirmation_simple_gray',
			'gf_confirmation_yellow_gradient',
			'gf_confirmation_green_gradient',
		];

		if ( isset( $form['cssClass'] ) ) {
			$field_classes = explode( ' ', $form['cssClass'] );
			foreach ( $field_classes as $class ) {
				if ( in_array( $class, $deprecated_confirmation_classes ) ) {
					return '<div class="gform-alert" data-js="gform-alert" style="grid-column: 1/-1;">
						<span class="gform-alert__icon gform-icon gform-icon--campaign" aria-hidden="true"></span>
						<div class="gform-alert__message-wrap">
							<p class="gform-alert__message">' . esc_html__( 'This form uses a deprecated CSS Ready Class, which will be removed in Gravity Forms 3.1.', 'gravityforms' ) . '</p>
							<a class="gform-alert__cta gform-button gform-button--white gform-button--size-xs" href="https://docs.gravityforms.com/migrating-your-forms-from-ready-classes/" target="_blank">'
						   	. esc_html__( 'Learn More', 'gravityforms' ) .
						   	'<span class="screen-reader-text">' . esc_html__('about deprecated ready classes', 'gravityforms') . '</span>
							<span class="screen-reader-text">' . esc_html__('(opens in a new tab)', 'gravityforms') . '</span>&nbsp;
							<span class="gform-icon gform-icon--external-link"></span>
							</a>
						</div>
					</div>';

				}
			}
		}
		return '';
	}


	// # SETTINGS RENDERER ---------------------------------------------------------------------------------------------

	/**
	 * Initialize Plugin Settings fields renderer.
	 *
	 * @since 2.5
	 * @since 2.9.8 Updated honeypotAction default to spam.
	 */
	public static function initialize_settings_renderer() {

		require_once( GFCommon::get_base_path() . '/form_detail.php' );

		$form_id = rgget( 'id' );
		$form    = GFCommon::gform_admin_pre_render( GFFormsModel::get_form_meta( $form_id ) );

		// Initialize new settings renderer.
		$renderer = new Settings(
			array(
				'fields'         => array_values( self::form_settings_fields( $form ) ),
				'initial_values' => self::get_initial_values( $form ),
				'save_callback'  => function( $values ) use ( &$form, $form_id ) {

					// Set form version.
					$form['version'] = GFForms::$version;

					// Save custom settings fields to the form object if they don't already exist there.
					$form = self::save_changed_form_settings_fields( $form, $values );

					// Form Basics
					$form['title']       = rgar( $values, 'title' );
					$form['description'] = rgar( $values, 'description' );

					// Form Layout
					$form['labelPlacement']          = GFCommon::whitelist( rgar( $values, 'labelPlacement' ), array( 'top_label', 'left_label', 'right_label' ) );
					$form['descriptionPlacement']    = GFCommon::whitelist( rgar( $values, 'descriptionPlacement' ), array( 'below', 'above' ) );
					$form['validationPlacement']     = GFCommon::whitelist( rgar( $values, 'validationPlacement' ), array( 'below', 'above' ) );
					$form['subLabelPlacement']       = GFCommon::whitelist( rgar( $values, 'subLabelPlacement' ), array( 'below', 'above' ) );
					$form['validationSummary']       = rgar( $values, 'validationSummary', false );
					$form['requiredIndicator']       = GFCommon::whitelist( rgar( $values, 'requiredIndicator' ), array( 'text', 'asterisk', 'custom' ) );
					$form['customRequiredIndicator'] = rgar( $values, 'customRequiredIndicator' );
					$form['cssClass']                = rgar( $values, 'cssClass' );

					// Save and Continue
					$form['save']['enabled']        = (bool) rgar( $values, 'saveEnabled' );
					$form['save']['button']['type'] = 'link';
					$form['save']['button']['text'] = rgar( $values, 'saveButtonText' );

					// Limit Entries
					$form['limitEntries']        = (bool) rgar( $values, 'limitEntries' );
					$form['limitEntriesCount']   = absint( rgar( $values, 'limitEntriesCount' ) );
					$form['limitEntriesPeriod']  = rgar( $values, 'limitEntriesPeriod' ) ? GFCommon::whitelist( $values['limitEntriesPeriod'], array( '', 'day', 'week', 'month', 'year' ) ) : '';
					$form['limitEntriesMessage'] = rgar( $values, 'limitEntriesMessage' );

					// Require Login
					$form['requireLogin']        = (bool) rgar( $values, 'requireLogin' );
					$form['requireLoginMessage'] = rgar( $values, 'requireLoginMessage' );

					// Scheduling
					$form['scheduleForm']           = rgar( $values, 'scheduleForm' );
					$form['scheduleStart']          = rgars( $values, 'scheduleStart/date' );
					$form['scheduleStartHour']      = rgars( $values, 'scheduleStart/hour' );
					$form['scheduleStartMinute']    = rgars( $values, 'scheduleStart/minute' );
					$form['scheduleStartAmpm']      = rgars( $values, 'scheduleStart/ampm' );
					$form['scheduleEnd']            = rgars( $values, 'scheduleEnd/date' );
					$form['scheduleEndHour']        = rgars( $values, 'scheduleEnd/hour' );
					$form['scheduleEndMinute']      = rgars( $values, 'scheduleEnd/minute' );
					$form['scheduleEndAmpm']        = rgars( $values, 'scheduleEnd/ampm' );
					$form['schedulePendingMessage'] = rgar( $values, 'schedulePendingMessage' );
					$form['scheduleMessage']        = rgar( $values, 'scheduleMessage' );

					// Form Options
					$form['enableHoneypot']  = (bool) rgar( $values, 'enableHoneypot' );
					$form['honeypotAction']  = GFCommon::whitelist( rgar( $values, 'honeypotAction' ), array( 'spam', 'abort' ) );
					$form['enableAnimation'] = (bool) rgar( $values, 'enableAnimation' );
					$form['markupVersion']   = rgar( $values, 'markupVersion' ) ? 1 : 2;

					// Enable/Disable Save & Continue.
					if ( $form['save']['enabled'] ) {
						$form = GFFormSettings::activate_save( $form );
					} else {
						$form = GFFormSettings::deactivate_save( $form );
					}

					/**
					 * Filters the updated form settings before being saved.
					 *
					 * @since 1.7
					 *
					 * @param array $form The form settings.
					 */
					$form = apply_filters( 'gform_pre_form_settings_save', $form );

					// Save form.
					GFFormDetail::save_form_info( $form_id, addslashes( json_encode( $form ) ) );

				},
				'before_fields' => function() use ( &$form ) {

					// Ensure form is not empty and display form settings warning accordingly.
					$notice = self::deprecated_classes_warning( $form );
					if ( ! empty( $notice ) ) {
						echo $notice;
					}

					?>

					<script type="text/javascript">

						<?php GFCommon::gf_global(); ?>

						var form = <?php echo json_encode( $form ); ?>;
						var fieldSettings = [];

						jQuery( document ).ready( function() {
							ToggleConditionalLogic( true, 'form_button' );
							jQuery( document ).trigger( 'gform_load_form_settings', [ form ] );
						} );

						function SetButtonConditionalLogic(isChecked) {
							form.button.conditionalLogic = isChecked ? new ConditionalLogic() : null;
						}

						<?php GFFormSettings::output_field_scripts() ?>

					</script>
					<?php

				}
			)
		);

		self::set_settings_renderer( $renderer );

		// Process save callback.
		if ( self::get_settings_renderer()->is_save_postback() ) {
			self::get_settings_renderer()->process_postback();
		}

	}

	/**
	 * Gets the current or default values of settings fields.
	 *
	 * Loop through all of the current settings and add in default values to pre-populate the settings fields.
	 *
	 * @since 2.5
	 *
	 * @param $form
	 *
	 * @return array
	 */
	private static function get_initial_values( $form ) {
		$initial_values = array();

		if ( empty( $form ) ) {
			return $initial_values;
		}

		// Get all of the current values.
		foreach ( $form as $key => $value ) {
			if ( in_array( $key, array( 'fields', 'notifications', 'confirmations' ) ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				foreach ( $value as $sub_key => $sub_value ) {
					if ( is_array( $sub_value ) ) {
						foreach ( $sub_value as $third_key => $third_value ) {
							$initial_values[ $key . ucfirst( $sub_key ) . ucfirst( $third_key ) ] = $third_value;

						}
					} else {
						$initial_values[ $key . ucfirst( $sub_key ) ] = $sub_value;

					}
				}
			}
			$initial_values[ $key ] = $value;
		}

		// Start and end times are formatted differently than other fields.
		$initial_values['scheduleStart'] = array(
			'date'   => rgar( $form, 'scheduleStart' ),
			'hour'   => rgar( $form, 'scheduleStartHour' ),
			'minute' => rgar( $form, 'scheduleStartMinute' ),
			'ampm'   => rgar( $form, 'scheduleStartAmpm' ),
		);

		$initial_values['scheduleEnd'] = array(
			'date'   => rgar( $form, 'scheduleEnd' ),
			'hour'   => rgar( $form, 'scheduleEndHour' ),
			'minute' => rgar( $form, 'scheduleEndMinute' ),
			'ampm'   => rgar( $form, 'scheduleEndAmpm' ),
		);

		// Conditional logic fields need different keys.
		$initial_values['form_button_conditional_logic']        = isset( $form['button']['conditionalLogic'] ) && ! empty( $form['button']['conditionalLogic'] );
		$initial_values['form_button_conditional_logic_object'] = rgars( $form, 'button/conditionalLogic' );

		/**
		 * Filter the initial values that will be populated into the form settings.
		 *
		 * @since 2.5
		 *
		 * @param array $initial_values An associative array of setting names and their initial values.
		 * @param array $form           The current form.
		 */
		return apply_filters( 'gform_form_settings_initial_values', $initial_values, $form );
	}

	/**
	 * Gets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @return false|Gravity_Forms\Gravity_Forms\Settings\Settings
	 */
	private static function get_settings_renderer() {

		return self::$_settings_renderer;

	}

	/**
	 * Sets the current instance of Settings handling settings rendering.
	 *
	 * @since 2.5
	 *
	 * @param Gravity_Forms\Gravity_Forms\Settings\Settings $renderer Settings renderer.
	 *
	 * @return bool|WP_Error
	 */
	private static function set_settings_renderer( $renderer ) {

		// Ensure renderer is an instance of Settings
		if ( ! is_a( $renderer, 'Gravity_Forms\Gravity_Forms\Settings\Settings' ) ) {
			return new WP_Error( 'Renderer must be an instance of Gravity_Forms\Gravity_Forms\Settings\Settings.' );
		}

		self::$_settings_renderer = $renderer;

		return true;

	}





	// # NOTIFICATIONS -------------------------------------------------------------------------------------------------

	/**
	 * Runs the notification page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormSettings::form_settings_page()
	 * @uses    GFNotification::notification_page()
	 *
	 * @return void
	 */
	public static function notification_page() {
		require_once( 'notification.php' );

		// Page header loaded in below function because admin messages were not yet available to the header to display.
		GFNotification::notification_page();

	}

	/**
	 * Renders the Personal Data page.
	 *
	 * @since  2.4
	 */
	public static function personal_data_page() {

		self::page_header( __( 'Personal Data', 'gravityforms' ) );

		require_once( 'includes/class-personal-data.php' );

		$form_id = absint( rgget( 'id' ) );

		GF_Personal_Data::form_settings( $form_id );

		self::page_footer();

	}

	/**
	 * Displays the form settings page header.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormSettings::confirmations_edit_page()
	 * @used-by GFFormSettings::confirmations_list_page()
	 * @used-by GFFormSettings::form_settings_ui()
	 * @used-by GFNotification::notification_edit_page()
	 * @used-by GFNotification::notification_list_page()
	 * @used-by GFAddOn::form_settings_page()
	 * @uses    SCRIPT_DEBUG
	 * @uses    GFFormsModel::get_form_meta()
	 * @uses    GFFormSettings::get_tabs()
	 * @uses    GFCommon::display_dismissible_message()
	 * @uses    GFCommon::display_admin_message()
	 * @uses    GFForms::top_toolbar()
	 * @uses    GFCommon::get_browser_class()
	 *
	 * @param string $title The title to display as the page header. Defaults to empty string.
	 *
	 * @return void
	 */
	public static function page_header( $title = '' ) {

		// Print admin styles.
		wp_print_styles( array( 'jquery-ui-styles', 'gform_admin', 'gform_settings', 'wp-pointer' ) );

		$form         = GFFormsModel::get_form_meta( rgget( 'id' ) );
		$current_tab  = rgempty( 'subview', $_GET ) ? 'settings' : rgget( 'subview' );
		$setting_tabs = GFFormSettings::get_tabs( $form['id'] );

		// If theme_layer is set in $_GET, we're on a theme layer and should use it as the current tab slug
		if ( ! rgempty( 'theme_layer', $_GET ) ) {
			$current_tab = rgget( 'theme_layer' );
		}

		// Kind of boring having to pass the title, optionally get it from the settings tab
		if ( ! $title ) {
			foreach ( $setting_tabs as $tab ) {
				if ( $tab['name'] == $current_tab ) {
					$title = $tab['label'];
				}
			}
		}

		?>

		<div class="wrap gforms_edit_form gforms_form_settings_wrap <?php echo GFCommon::get_browser_class() ?>">

			<?php
				GFSettings::page_header_bar();
				GFForms::top_toolbar();
				echo GFCommon::get_remote_message();
				GFCommon::notices_section();
			?>

			<div class="gform-settings__wrapper">

				<?php
					GFCommon::display_dismissible_message();
					GFCommon::display_admin_message();
				?>

				<nav class="gform-settings__navigation">
				<?php

				    foreach ( $setting_tabs as $tab ) {

						if ( rgar( $tab, 'capabilities' ) && ! GFCommon::current_user_can_any( $tab['capabilities'] ) ) {
							continue;
						}

						$query = array(
							'subview' => $tab['name'],
							'page'    => GFForms::get_page_query_arg(),
							'id'      => rgget( 'id' ),
							'view'    => rgget( 'view' ),
						);

						if ( isset( $tab['query'] ) ) {
							$query = array_merge( $query, $tab['query'] );
						}

						$url = add_query_arg( $query, admin_url( 'admin.php' ) );

						// Get tab icon.
						$icon_markup = GFCommon::get_icon_markup( $tab, 'gform-icon--cog' );

						printf(
							'<a href="%s"%s><span class="icon">%s</span> <span class="label">%s</span></a>',
							esc_url( $url ),
							$current_tab === $tab['name'] ? ' class="active"' : '',
							$icon_markup,
							esc_html( $tab['label'] )
						);
					}
					?>
				</nav>

				<div class="gform-settings__content" id="tab_<?php echo esc_attr( $current_tab ); ?>">
	<?php
	}

	/**
	 * Displays the Settings page footer.
	 *
	 * @since  Unknown
	 */
	public static function page_footer() {
		return GFSettings::page_footer();
	}

	/**
	 * Gets the Settings page tabs.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormSettings::page_header()
	 * @used-by GFForms::get_form_settings_sub_menu_items()
	 * @used-by GFForms::modify_admin_title()
	 *
	 * @param int $form_id The form ID to get tabs for.
	 *
	 * @return array $settings_tabs The form settings tabs to display.
	 */
	public static function get_tabs( $form_id ) {

		$setting_tabs = array(
			'10' => array(
				'name'         => 'settings',
				'label'        => __( 'Form Settings', 'gravityforms' ),
				'icon'         => 'gform-icon--cog',
				'query'        => array( 'cid' => null, 'nid' => null, 'fid' => null ),
				'capabilities' => array( 'gravityforms_edit_forms' ),
			),
			'20' => array(
				'name'         => 'confirmation',
				'label'        => __( 'Confirmations', 'gravityforms' ),
				'icon'         => 'gform-icon--confirmations',
				'query'        => array( 'cid' => null, 'duplicatedcid' => null ),
				'capabilities' => array( 'gravityforms_edit_forms' ),
			),
			'30' => array(
				'name'         => 'notification',
				'label'        => __( 'Notifications', 'gravityforms' ),
				'icon'         => 'gform-icon--flag',
				'query'        => array( 'nid' => null ),
				'capabilities' => array( 'gravityforms_edit_forms' ),
			),
			'40' => array(
				'name'         => 'personal-data',
				'label'        => __( 'Personal Data', 'gravityforms' ),
				'icon'         => 'gform-icon--user',
				'query'        => array( 'nid' => null ),
				'capabilities' => array( 'gravityforms_edit_forms' ),
			),
		);

		/**
		 * Filters the settings tabs before they are returned.
		 *
		 * Tabs are not sorted yet, and will be sorted numerically.
		 *
		 * @since Unknown
		 *
		 * @param array $setting_tabs The settings tabs.
		 * @param int   $form_id      The ID of the form being accessed.
		 */
		$setting_tabs = apply_filters( 'gform_form_settings_menu', $setting_tabs, $form_id );

		$primary_settings_tab_keys = array(
			'confirmation',
			'notification',
			'personal-data',
			'settings',
		);

		return self::sorting_tabs_alphabetical( $setting_tabs, $primary_settings_tab_keys );
	}

	/**
	 * Orders tabs array into alphabetical order
	 *
	 * @return array
	 *
	 * @since  2.7.4
	 * @access public
	 *
	 * @used-by GFFormSettings::get_tabs()
	 */
	public static function sorting_tabs_alphabetical( array $settings_tab, array $primary_settings_tab_keys ) {
		usort( $settings_tab, function( $a, $b ) use ( $primary_settings_tab_keys ) {
			if ( $a['name'] === 'settings' ) {
				return -1;
			} elseif ( $b['name'] === 'settings' ) {
				return 1;
			}

			$key_a = in_array( $a['name'], $primary_settings_tab_keys );
			$key_b = in_array( $b['name'], $primary_settings_tab_keys );

			if ( $key_a !== false && $key_b === false ) {
				return -1;
			} elseif ( $key_a === false && $key_b !== false ) {
				return 1;
			} else {
				return strcasecmp( $a['label'], $b['label'] );
			}
		});

		return $settings_tab;
	}

	/**
	 * Processes actions made from the Confirmations List page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormSettings::confirmations_list_page()
	 * @uses    GFFormSettings::delete_confirmation()
	 * @uses    GFCommon::add_message()
	 * @uses    GFCommon::add_error_message()
	 *
	 * @return void
	 */
	public static function maybe_process_confirmation_list_action() {

		if ( empty( $_POST ) || ! check_admin_referer( 'gform_confirmation_list_action', 'gform_confirmation_list_action' ) )
			return;

		$action    = rgpost( 'action' );
		$object_id = rgpost( 'action_argument' );

		switch ( $action ) {
			case 'delete':
				$confirmation_deleted = GFFormsModel::delete_form_confirmation( $object_id, rgget( 'id' ) );
				if ( $confirmation_deleted ) {
					GFCommon::add_message( __( 'Confirmation deleted.', 'gravityforms' ) );
				} else {
					GFCommon::add_error_message( __( 'There was an issue deleting this confirmation.', 'gravityforms' ) );
				}
				break;
		}

	}

	/**
	 * Delete a form confirmation by ID.
	 *
	 * @since  Unknown
	 *
	 * @param string    $confirmation_id The confirmation to be deleted.
	 * @param int|array $form_id         The form ID or Form Object form the confirmation being deleted.
	 *
	 * @return false|int The result of the database operation.
	 */
	public static function delete_confirmation( $confirmation_id, $form_id ) {

		return GFFormsModel::delete_form_confirmation( $confirmation_id, $form_id );

	}

	/**
	 * Echos a variable.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFNotification::notification_edit_page()
	 *
	 * @param string $a Thing to echo.
	 *
	 * @return void
	 */
	public static function output( $a ) {
		echo $a;
	}

	/**
	 * Outputs scripts for conditional logic fields.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GF_Fields::get_all()
	 * @uses GF_Field::is_conditional_logic_supported()
	 *
	 * @param bool $echo If the scripts should be echoed. Defaults to true.
	 *
	 * @return string $script_str The scripts to be output.
	 */
	public static function output_field_scripts( $echo = true ) {
		$script_str = '';
		$conditional_logic_fields = array();

		foreach ( GF_Fields::get_all() as $gf_field ) {
			if ( $gf_field->is_conditional_logic_supported() ) {
				$conditional_logic_fields[] = $gf_field->type;
			}
		}

		$script_str .= sprintf( 'function GetConditionalLogicFields(){return %s;}', json_encode( $conditional_logic_fields ) ) . PHP_EOL;

		if ( ! empty( $script_str ) && $echo ) {
			echo $script_str;
		}

		return $script_str;
	}

	/**
	 * Handles the saving of notifications and confirmations when activated.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormSettings::form_settings_ui()
	 * @uses    GFFormsModel::save_form_notifications()
	 * @uses    GFFormsModel::save_form_confirmations()
	 *
	 * @param array $form The Form Object to be saved.
	 *
	 * @return array $form The Form Object.
	 */
	public static function activate_save( $form ) {

		$form_id = $form['id'];

		$has_save_notification = false;
		foreach ( $form['notifications'] as $notification ) {
			if ( rgar( $notification, 'event' ) == 'form_save_email_requested' ) {
				$has_save_notification = true;
				break;
			}
		}
		if ( ! $has_save_notification ) {
			$notification_id = uniqid();
			$form['notifications'][ $notification_id ] = array(
				'id'      => $notification_id,
				'isDefault' => true,
				'name'    => __( 'Save and Continue Email', 'gravityforms' ),
				'event'   => 'form_save_email_requested',
				'toType'  => 'hidden',
				'from' => '{admin_email}',
				'subject' => __( 'Link to continue {form_title}' ),
				'message' => __( 'Thank you for saving {form_title}. Please use the unique link below to return to the form from any computer. <br /><br /> {save_link} <br /><br /> Remember that the link will expire after 30 days so please return via the provided link to complete your form submission.', 'gravityforms' ),
			);
			GFFormsModel::save_form_notifications( $form_id, $form['notifications'] );
		}


		$has_save_confirmation = false;
		foreach ( $form['confirmations'] as $confirmation ) {
			if ( rgar( $confirmation, 'event' ) == 'form_saved' ) {
				$has_save_confirmation = true;
				break;
			}
		}

		if ( ! $has_save_confirmation ) {
			$confirmation_1 = GFFormsModel::get_default_confirmation( 'form_saved' );
			$confirmation_2 = GFFormsModel::get_default_confirmation( 'form_save_email_sent' );

			$form['confirmations'][ $confirmation_1['id'] ] = $confirmation_1;
			$form['confirmations'][ $confirmation_2['id'] ] = $confirmation_2;
			GFFormsModel::save_form_confirmations( $form_id, $form['confirmations'] );
		}
		return $form;
	}

	/**
	 * Handles the saving of confirmation and notifications when deactivating.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFFormsModel::save_form_notifications()
	 * @uses GFFormsModel::save_form_confirmations()
	 *
	 * @param array $form The Form Object.
	 *
	 * @return array $form The Form Object.
	 */
	public static function deactivate_save( $form ) {

		$form_id = $form['id'];

		foreach ( $form['notifications'] as $notification_id => $notification ) {
			if ( rgar( $notification, 'isDefault' ) && rgar( $notification, 'event' ) == 'form_save_email_requested' ) {
				unset( $form['notifications'][ $notification_id ] );
				GFFormsModel::save_form_notifications( $form_id, $form['notifications'] );
				break;
			}
		}

		$changed = false;
		foreach ( $form['confirmations'] as $confirmation_id => $confirmation ) {
			$event = rgar( $confirmation, 'event' );
			if ( rgar( $confirmation, 'isDefault' ) && ( $event == 'form_saved' || $event == 'form_save_email_sent' ) ) {
				unset( $form['confirmations'][ $confirmation_id ] );
				$changed = true;
			}
		}
		if ( $changed ) {
			GFFormsModel::save_form_confirmations( $form_id, $form['confirmations'] );
		}

		return $form;
	}

	/**
	 * Handles the saving of form titles.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFAPI::get_form()
	 * @uses GFAPI::update_form()
	 *
	 * @return void
	 */
	public static function save_form_title() {

		check_admin_referer( 'gf_save_title', 'gf_save_title' );

		$form_title = json_decode( rgpost( 'title' ) );
		$form_id = rgpost( 'formId' );

		$result = array( 'isValid' => true, 'message' => '' );

		if ( empty( $form_title ) ) {

			$result['isValid'] = false;
			$result['message'] = __( 'Please enter a form title.', 'gravityforms' );

		} elseif ( ! GFFormsModel::is_unique_title( $form_title, $form_id ) ) {
			$result['isValid'] = false;
			$result['message'] = __( 'Please enter a unique form title.', 'gravityforms' );

		} else {

			$form = GFAPI::get_form( $form_id );
			$form['title'] = $form_title;

			GFAPI::update_form( $form, $form_id );

		}

		die( json_encode( $result ) );

	}

	/**
	 * Saves new or changed form settings fields to the form object to automatically save custom fields.
	 *
	 * @since  2.5.2
	 * @access public
	 *
	 * @param  array $form   The form object.
	 * @param  array $values The array of values being saved.
	 *
	 * @return array $form The Form Object.
	 */
	public static function save_changed_form_settings_fields( $form, $values ) {

		// Find the new settings that are not already saved to the form object or changed settings.
		foreach ( $values as $key => $value ) {

			if ( array_key_exists( $key, $form ) && $value === $form[ $key ] ) {
				continue;
			}

			$form[ $key ] = $value;
		}

		return $form;
	}
}
