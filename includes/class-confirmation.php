<?php

class_exists( 'GFForms' ) or die();

use Gravity_Forms\Gravity_Forms\Settings\Settings;

/**
 * Handles listing and editing Form Confirmations.
 *
 * @since 2.5
 *
 * Class GF_Confirmations
 */
class GF_Confirmation {

	use Redirects_On_Save;

	/**
	 * Regular expression for determining if string contains unsafe merge tags.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	private static $unsafe_regex = '/(\S+)\s*=\s*["|\']({[^{]*?:(\d+(\.\d+)?)(:(.*?))?})["|\']/mi';

	/**
	 * Stores the current instance of the Settings renderer.
	 *
	 * @since 2.5
	 *
	 * @var false|Gravity_Forms\Gravity_Forms\Settings\Settings
	 */
	private static $_settings_renderer = false;

	/**
	 * Displays the Confirmations list or edit page.
	 *
	 * @since 2.5
	 */
	public static function render_page() {

		// Get form, confirmation IDs.
		$form_id         = absint( rgget( 'id' ) );
		$confirmation_id = rgpost( 'confirmation_id' ) ? rgpost( 'confirmation_id' ) : rgget( 'cid' );

		// Display edit or list page.
		if ( ! rgblank( $confirmation_id ) ) {
			self::confirmations_edit_page( $form_id, $confirmation_id );
		} else {
			self::confirmations_list_page( $form_id );
		}

	}





	// # LIST PAGE -----------------------------------------------------------------------------------------------------

	/**
	 * Displays the Confirmations listing page.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @used-by GFFormSettings::confirmations_page()
	 * @uses    GFFormSettings::maybe_process_confirmation_list_action()
	 * @uses    GFFormSettings::page_header()
	 * @uses    GFFormsModel::get_form_meta()
	 * @uses    GFConfirmationTable::prepare_items()
	 * @uses    GFConfirmationTable::display()
	 * @uses    GFFormSettings::page_footer()
	 *
	 * @param int $form_id The form ID to display the confirmations for.
	 *
	 * @return void
	 */
	private static function confirmations_list_page( $form_id ) {

		// Process list actions.
		GFFormSettings::maybe_process_confirmation_list_action();

		// Get form object.
		$form = GFFormsModel::get_form_meta( $form_id );

		// Prepare list table.
		$confirmation_table = new GFConfirmationTable( $form );
		$confirmation_table->prepare_items();

		?>

		<div class="gform-settings-panel">
			<header class="gform-settings-panel__header">
				<h4 class="gform-settings-panel__title"><?php esc_html_e( 'Confirmations', 'gravityforms' ); ?></h4>
			</header>

			<div class="gform-settings-panel__content">

				<form id="confirmation_list_form" method="post">

					<?php
					$confirmation_table->display();
					wp_nonce_field( 'gform_confirmation_list_action', 'gform_confirmation_list_action' );
					?>

					<input id="action_argument" name="action_argument" type="hidden" />
					<input id="action" name="action" type="hidden" />

				</form>
			</div>

			<script type="text/javascript">
				var form = <?php echo json_encode( $form ); ?>;

				function ToggleActive( btn, confirmation_id ) {
					var is_active = jQuery( btn ).hasClass( 'gform-status--active' );

					jQuery.ajax(
						{
							url:      '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							method:   'POST',
							dataType: 'json',
							data: {
								action:                        'rg_update_confirmation_active',
								rg_update_confirmation_active: '<?php echo wp_create_nonce( 'rg_update_confirmation_active' ); ?>',
								form_id:                       '<?php echo intval( $form_id ); ?>',
								confirmation_id:               confirmation_id,
								is_active:                     is_active ? 0 : 1,
							},
							success:  function() {
								if ( is_active ) {
									setToggleInactive();
								} else {
									setToggleActive();
								}
							},
							error:    function() {
								if ( ! is_active ) {
									setToggleInactive();
								} else {
									setToggleActive();
								}

								alert( '<?php echo esc_js( __( 'Ajax error while updating form', 'gravityforms' ) ); ?>' );
							}
						}
					);

					function setToggleInactive() {
						jQuery( btn ).removeClass( 'gform-status--active' ).addClass( 'gform-status--inactive' ).find( '.gform-status-indicator-status' ).html( <?php echo wp_json_encode( esc_attr__( 'Inactive', 'gravityforms' ) ); ?> );
					}

					function setToggleActive() {
						jQuery( btn ).removeClass( 'gform-status--inactive' ).addClass( 'gform-status--active' ).find( '.gform-status-indicator-status' ).html( <?php echo wp_json_encode( esc_attr__( 'Active', 'gravityforms' ) ); ?> );
					}

				}

			</script>

		</div>

		<?php
	}





	// # EDIT PAGE -----------------------------------------------------------------------------------------------------

	/**
	 * Displays the Confirmation Edit page.
	 *
	 * @since  2.5
	 * @access public
	 *
	 * @param int $form_id         The ID of the form confirmations are being edited for.
	 * @param int $confirmation_id The confirmation ID being edited.
	 */
	private static function confirmations_edit_page( $form_id, $confirmation_id ) {

		// Initialize settings.
		if ( ! self::get_settings_renderer() ) {
			self::initialize_settings_renderer();
		}

		// Render settings.
		self::get_settings_renderer()->render();

	}

	/**
	 * Get Confirmation settings fields.
	 *
	 * @since 2.5
	 *
	 * @param array $confirmation Confirmation being edited.
	 * @param array $form         The Form object.
	 *
	 * @return array
	 */
	private static function settings_fields( $confirmation, $form ) {

		// Initialize page choices array.
		$page_choices = array(
			array(
				'label' => esc_html__( 'Select a Page', 'gravityforms' ),
				'value' => '',
			),
		);

		// Get pages.
		$pages = get_pages( array( 'depth' => 0, 'child_of' => 0 ) );

		// Loop through pages, add as choices.
		if ( is_array( $pages ) && ! empty( $pages ) ) {
			foreach ( $pages as $page ) {
				$page_choices[] = array(
					'label' => esc_html( $page->post_title ),
					'value' => esc_attr( $page->ID ),
				);
			}
		}

		// Build confirmation settings fields.
		$fields = array(
			array(
				'title'  => esc_html__( 'Confirmations', 'gravityforms' ),
				'fields' => array(
					array(
						'name'     => 'name',
						'label'    => esc_html__( 'Confirmation Name', 'gravityforms' ),
						'type'     => 'text',
						'required' => true,
						'hidden'   => rgar( $confirmation, 'isDefault' ),
					),
					array(
						'name' => 'event',
						'type' => 'hidden',
					),
					array(
						'name'          => 'type',
						'label'         => esc_html__( 'Confirmation Type', 'gravityforms' ),
						'type'          => 'radio',
						'horizontal'    => true,
						'default_value' => 'message',
						'choices'       => array(
							array(
								'label' => esc_html__( 'Text', 'gravityforms' ),
								'value' => 'message',
							),
							array(
								'label' => esc_html__( 'Page', 'gravityforms' ),
								'value' => 'page',
							),
							array(
								'label' => esc_html__( 'Redirect', 'gravityforms' ),
								'value' => 'redirect',
							),
						),
					),
					array(
						'name'       => 'message',
						'type'       => 'textarea',
						'label'      => esc_html__( 'Message', 'gravityforms' ),
						'tooltip'    => gform_tooltip( 'form_confirmation_message', null, true ),
						'use_editor' => true,
						'dependency' => array(
							'live'     => true,
							'operator' => 'ALL',
							'fields'   => array(
								array(
									'field'  => 'type',
									'values' => array( 'message' ),
								),
							),
						),
					),
					array(
						'name'       => 'disableAutoformat',
						'label'      => esc_html__( 'Auto-Formatting', 'gravityforms' ),
						'type'       => 'checkbox',
						'choices'    => array(
							array(
								'name'    => 'disableAutoformat',
								'label'   => esc_html__( 'Disable auto-formatting', 'gravityforms' ),
								'tooltip' => gform_tooltip( 'form_confirmation_autoformat', null, true ),
							),
						),
						'dependency' => array(
							'live'     => true,
							'operator' => 'ALL',
							'fields'   => array(
								array(
									'field'  => 'type',
									'values' => array( 'message' ),
								),
							),
						),
					),
					array(
						'name'       => 'page',
						'label'      => esc_html__( 'Page', 'gravityforms' ),
						'type'       => 'select',
						'choices'    => $page_choices,
						'required'   => true,
						'dependency' => array(
							'live'     => true,
							'operator' => 'ALL',
							'fields'   => array(
								array(
									'field'  => 'type',
									'values' => array( 'page' ),
								),
							),
						),
					),
					array(
						'name'                => 'url',
						'label'               => esc_html__( 'Redirect URL', 'gravityforms' ),
						'type'                => 'text',
						'required'            => true,
						'dependency'          => array(
							'live'     => true,
							'operator' => 'ALL',
							'fields'   => array(
								array(
									'field'  => 'type',
									'values' => array( 'redirect' ),
								),
							),
						),
						'validation_callback' => function( $field, $value ) {

							if ( ( empty( $value ) || ! GFCommon::is_valid_url( $value ) ) && ! GFCommon::has_merge_tag( $value ) ) {
								$field->set_error( __( 'You must specify a valid Redirect URL.', 'gravityforms' ) );
							}

						}
					),
					array(
						'name'        => 'queryString',
						'type'        => 'text',
						'label'       => esc_html__( 'Pass Field Data via Query String', 'gravityforms' ),
						'class'       => 'merge-tag-support mt-position-right mt-hide_all_fields mt-option-url',
						'tooltip'     => gform_tooltip( 'form_redirect_querystring', null, true ),
						'dependency'  => array(
							'live'     => true,
							'operator' => 'ALL',
							'fields'   => array(
								array(
									'field'  => 'type',
									'values' => array( 'page', 'redirect' ),
								),
							),
						),
						'description' => esc_html__( 'Sample: phone={Phone:1}&email={Email:2}', 'gravityforms' ),
					),
					array(
						'name'        => 'conditionalLogic',
						'label'       => esc_html__( 'Conditional Logic', 'gravityforms ' ),
						'type'        => 'conditional_logic',
						'object_type' => 'confirmation',
						'checkbox'    => array(
							'label'  => esc_html__( 'Enable conditional logic', 'gravityforms' ),
							'hidden' => false,
						),
						'dependency' => function( $settings ) use ( $confirmation ) {
							return ! rgar( $confirmation, 'isDefault' );
						}
					),
					array(
						'type'  => 'save',
						'value' => esc_html__( 'Save Confirmation', 'gravityforms' ),
					),
				),
			),
		);

		/**
		 * Filters the form settings before they are displayed.
		 *
		 * @since      Unknown
		 * @deprecated 2.5
		 *
		 * @param array $ui_settings  The Settings page markup.
		 * @param array $confirmation Contains the confirmation details.
		 * @param array $form         The Form Object.
		 */
		$legacy_settings = apply_filters( 'gform_confirmation_ui_settings', array(), $confirmation, $form );

		// If legacy settings exist, add to fields.
		if ( ! empty( $legacy_settings ) ) {

			// Prepare HTML.
			$html = '<table class="gforms_form_settings" cellspacing="0" cellpadding="0" width="100%">';
			foreach ( $legacy_settings as $legacy_field ) {
				$html .= $legacy_field;
			}
			$html .= '</table>';

			// Add section.
			$fields[] = array(
				'title'  => esc_html__( 'Legacy Settings', 'gravityforms' ),
				'class'  => 'gform-settings-panel--full',
				'fields' => array(
					array(
						'name' => 'legacy',
						'type' => 'html',
						'html' => $html,
					),
				),
			);

		}

		/**
		 * Filters the confirmation settings fields before they are displayed.
		 *
		 * @since 2.5
		 *
		 * @param array $fields       Form settings fields.
		 * @param array $confirmation Contains the Confirmation meta.
		 * @param array $form         Form Object.
		 */
		$fields = gf_apply_filters( array(
			'gform_confirmation_settings_fields',
			$form['id'],
		), $fields, $confirmation, $form );

		return $fields;

	}

	/**
	 * Get Confirmation object for Confirmation edit page.
	 *    Handles duplication.
	 *
	 * @param string $confirmation_id Confirmation ID.
	 * @param array  $form            Form object.
	 *
	 * @return array
	 */
	private static function get_confirmation( $confirmation_id, $form ) {

		// Get ID of confirmation to duplicate, determine if we are duplicating confirmation.
		$duplicated_cid = sanitize_key( rgget( 'duplicatedcid' ) );
		$is_duplicate   = empty( $_POST ) && ! empty( $duplicated_cid );

		// Get confirmation object.
		$confirmation = rgar( $form['confirmations'], $is_duplicate ? $duplicated_cid : $confirmation_id, array() );

		// If confirmation is not being duplicated, return.
		if ( ! $is_duplicate ) {
			return $confirmation;
		}

		// Reset confirmation ID, default status, conditional logic.
		$confirmation['id']               = null;
		$confirmation['isDefault']        = false;
		$confirmation['conditionalLogic'] = null;

		// Check for confirmation count in confirmation name.
		preg_match_all( '/(\\(([0-9])*\\))$/mi', $confirmation['name'], $count_exists_in_name );

		// If count does not exist, set count to 1.
		if ( empty( $count_exists_in_name[0] ) ) {

			// Set initial count.
			$count = 1;

		} else {

			// Set existing count to current count plus one.
			$count = (int) $count_exists_in_name[2][0] + 1;

			// Remove existing count from title.
			$confirmation['name'] = preg_replace( '/(\\(([0-9])*\\))$/mi', null, $confirmation['name'] );

		}

		// Trim confirmation name, add copy count.
		$confirmation['name'] = trim( $confirmation['name'] );
		$new_name             = $confirmation['name'] . " ($count)";

		// If new confirmation name is not unique, increment the count until a unique confirmation name is created.
		while ( ! self::is_unique_name( $new_name, rgar( $form, 'confirmations', array() ) ) ) {
			$count++;
			$new_name = $confirmation['name'] . " ($count)";
		}

		// Set confirmation name.
		$confirmation['name'] = $new_name;

		return $confirmation;

	}





	// # SETTINGS RENDERER ---------------------------------------------------------------------------------------------

	/**
	 * Initialize Plugin Settings fields renderer.
	 *
	 * @since 2.5
	 */
	public static function initialize_settings_renderer() {

		if ( ! class_exists( 'GFFormSettings' ) ) {
			require_once( GFCommon::get_base_path() . '/form_settings.php' );
		}

		// Get form, confirmation IDs.
		$form_id         = absint( rgget( 'id' ) );
		$confirmation_id = rgpost( 'confirmation_id' ) ? rgpost( 'confirmation_id' ) : rgget( 'cid' );

		/**
		 * Filters to form meta being used within the confirmations edit page.
		 *
		 * @since Unknown
		 *
		 * @param array $form The Form Object.
		 */
		$form = gf_apply_filters( array(
			'gform_admin_pre_render',
			$form_id
		), GFFormsModel::get_form_meta( $form_id ) );

		// Get confirmation object.
		$confirmation = self::get_confirmation( $confirmation_id, $form );

		// Get initial values.
		$initial_values = array();
		foreach ( $confirmation as $key => $value ) {
			$initial_values[ $key ] = $value;
		}
		$initial_values['page']                                  = rgar( $confirmation, 'pageId' );
		$initial_values['type']                                  = rgar( $confirmation, 'type' ) ? rgar( $confirmation, 'type' ) : 'message';
		$initial_values['confirmation_conditional_logic']        = ! rgar( $confirmation, 'isDefault' );
		$initial_values['confirmation_conditional_logic_object'] = htmlentities( json_encode( rgget( 'conditionalLogic', $confirmation ) ) );

		// Add warning if confirmation message is unsafe.
		if ( ! empty( $confirmation['message'] ) && self::confirmation_looks_unsafe( $confirmation['message'] ) ) {
			$dismissible_message = esc_html__( 'Your confirmation message appears to contain a merge tag as the value for an HTML attribute. Depending on the attribute and field type, this might be a security risk. %sFurther details%s', 'gravityforms' );
			$dismissible_message = sprintf( $dismissible_message, '<a href="https://docs.gravityforms.com/security-warning-merge-tags-html-attribute-values/" target="_blank">', '</a>' );
			GFCommon::add_dismissible_message( $dismissible_message, 'confirmation_unsafe_' . $form_id );
		}

		// Get fields.
		$sections = self::settings_fields( $confirmation, $form );

		// Initialize new settings renderer.
		$renderer = new Settings(
			array(
				'fields'         => $sections,
				'header'         => array(
					'icon'  => 'fa fa-cogs',
					'title' => esc_html__( 'Confirmation Settings', 'gravityforms' ),
				),
				'initial_values' => $initial_values,
				'save_callback'  => function( $values ) use ( &$confirmation, &$form, &$confirmation_id ) {

					// Determine if new confirmation.
					$is_new_confirmation = ! $confirmation;

					// Set confirmation ID.
					if ( $is_new_confirmation ) {
						$confirmation_id = $confirmation['id'] = uniqid();
					}

					// Save values to the confirmation object in advance so non-custom values will be rewritten when we apply values below.
					$confirmation = GFFormSettings::save_changed_form_settings_fields( $confirmation, $values );

					// Apply values.
					$confirmation['name']              = rgar( $values, 'name' );
					$confirmation['event']             = rgar( $values, 'event' );
					$confirmation['type']              = GFCommon::whitelist( rgar( $values, 'type' ), array(
						'message',
						'page',
						'redirect',
					) );
					$confirmation['message']           = self::maybe_wp_kses( rgar( $values, 'message' ) );
					$confirmation['disableAutoformat'] = (bool) rgar( $values, 'disableAutoformat' );
					$confirmation['pageId']            = rgar( $values, 'page' );
					$confirmation['url']               = rgar( $values, 'url' );
					$confirmation['queryString']       = rgar( $values, 'queryString' );

					$confirmation['conditionalLogic'] = rgar( $confirmation, 'isDefault' ) ? array() : rgar( $values, 'confirmation_conditional_logic_object' );
					$confirmation['conditionalLogic'] = GFFormsModel::sanitize_conditional_logic( $confirmation['conditionalLogic'] );

					/**
					 * Filters the confirmation before it is saved.
					 *
					 * @since Unknown
					 *
					 * @param array $confirmation        The confirmation details.
					 * @param array $form                The Form Object.
					 * @param bool  $is_new_confirmation True if this is a new confirmation. False if editing existing.
					 */
					$confirmation = gf_apply_filters( array(
						'gform_pre_confirmation_save',
						$form['id']
					), $confirmation, $form, $is_new_confirmation );

					$confirmation = GFFormsModel::trim_conditional_logic_values_from_element( $confirmation, $form );

					// Save confirmation.
					$form['confirmations'][ $confirmation['id'] ] = $confirmation;
					$result                                       = GFFormsModel::save_form_confirmations( $form['id'], $form['confirmations'] );

					self::$_saved_item_id = $confirmation_id;
				},
				'before_fields'  => function() use ( &$confirmation, $confirmation_id, $form ) {

					$entry_meta = GFFormsModel::get_entry_meta( $form['id'] );
					/**
					 * Filters the entry meta used within confirmations.
					 *
					 * @since Unknown
					 *
					 * @param array $entry_meta      The Entry Object.
					 * @param array $form            The Form Object.
					 * @param int   $confirmation_id The ID of the confirmation being edited.
					 */
					$entry_meta = apply_filters( 'gform_entry_meta_conditional_logic_confirmations', $entry_meta, $form, $confirmation_id );

					?>

					<script type="text/javascript">

						var confirmation = <?php echo $confirmation ? json_encode( $confirmation ) : 'new ConfirmationObj()' ?>;
						var form = <?php echo json_encode( $form ); ?>;
						var entry_meta = <?php echo GFCommon::json_encode( $entry_meta ) ?>;

						gform.addFilter( 'gform_merge_tags', 'MaybeAddSaveMergeTags' );

						function MaybeAddSaveMergeTags( mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option ) {
							var event = confirmation.event;
							if ( event === 'form_saved' || event === 'form_save_email_sent' ) {
								mergeTags[ 'other' ].tags.push( {
									tag: '{save_link}',
									label: <?php echo json_encode( __( 'Save &amp; Continue Link', 'gravityforms' ) ) ?> } );
								mergeTags[ 'other' ].tags.push( {
									tag: '{save_token}',
									label: <?php echo json_encode( __( 'Save &amp; Continue Token', 'gravityforms' ) ) ?> } );
							}

							if ( event === 'form_saved' ) {
								mergeTags[ 'other' ].tags.push( {
									tag: '{save_email_input}',
									label: <?php echo json_encode( __( 'Save &amp; Continue Email Input', 'gravityforms' ) ) ?> } );
							}

							return mergeTags;
						}

						jQuery( function() {
							if ( confirmation.event === 'form_saved' || confirmation.event === 'form_save_email_sent'  ) {
								jQuery( '#type1, #type2' ).attr( 'disabled', true );
							}
						} );

						<?php if ( ! rgar( $confirmation, 'isDefault' ) ) : ?>
						jQuery( function() {
							ToggleConditionalLogic( true, 'confirmation' );
						} );
						<?php endif; ?>

						<?php GFFormSettings::output_field_scripts() ?>

					</script>
					<?php

				},
				'after_fields'   => function() use ( &$confirmation_id ) {
					printf( '<input type="hidden" id="confirmation_id" name="confirmation_id" value="%s" />', esc_attr( $confirmation_id ) );
				}
			)
		);

		self::set_settings_renderer( $renderer );

		if ( self::is_save_redirect( 'cid' ) ) {
			self::get_settings_renderer()->set_save_message_after_redirect();
		}

		// Process save callback.
		if ( self::get_settings_renderer()->is_save_postback() ) {
			self::get_settings_renderer()->process_postback();
			self::redirect_after_valid_save( 'cid' );
		}

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
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $renderer Settings renderer.
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






	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Checks if a confirmation name is unique.
	 *
	 * @since  2.5
	 *
	 * @param string $name          The confirmation name to check for.
	 * @param array  $confirmations The confirmations to check through.
	 *
	 * @return bool True if unique. False otherwise.
	 */
	private static function is_unique_name( $name, $confirmations ) {

		foreach ( $confirmations as $confirmation ) {
			if ( strtolower( rgar( $confirmation, 'name' ) ) == strtolower( $name ) ) {
				return false;
			}
		}

		return true;

	}






	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Checks the text for merge tags as attribute values.
	 *
	 * @since  Unknown
	 *
	 * @param string $text The confirmation text to check.
	 *
	 * @return bool
	 */
	private static function confirmation_looks_unsafe( $text ) {

		$unsafe = false;
		preg_match_all( self::$unsafe_regex, $text, $matches, PREG_SET_ORDER );
		if ( is_array( $matches ) && count( $matches ) > 0 ) {
			foreach ( $matches as $match ) {
				if ( strtolower( $match[1] ) !== 'merge_tag' ) {
					$unsafe = true;
				}
			}
		}

		return $unsafe;

	}

	/**
	 * Alias for GFCommon::maybe_wp_kses().
	 *
	 * @since  2.5
	 *
	 * @param string $html              The HTML markup to sanitize.
	 * @param string $allowed_html      The allowed HTML content. Defaults to 'post'.
	 * @param array  $allowed_protocols Allowed protocols. Defaults to empty array.
	 *
	 * @return string The sanitized HTML markup.
	 */
	private static function maybe_wp_kses( $html, $allowed_html = 'post', $allowed_protocols = array() ) {

		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$html = self::remove_unsafe_merge_tags( $html );
		}

		return GFCommon::maybe_wp_kses( $html, $allowed_html, $allowed_protocols );

	}

	/**
	 * Removes merge tags used as HTML attributes.
	 *
	 * @since  2.5
	 *
	 * @param string $text The confirmation text to check.
	 *
	 * @return bool
	 */
	private static function remove_unsafe_merge_tags( $text ) {

		preg_match_all( self::$unsafe_regex, $text, $matches, PREG_SET_ORDER );

		if ( is_array( $matches ) && count( $matches ) > 0 ) {
			foreach ( $matches as $match ) {
				// Ignore conditional shortcodes.
				if ( strtolower( $match[1] ) !== 'merge_tag' ) {
					// Remove the merge tag.
					$text = str_replace( $match[0], $match[1] . '=""', $text );
				}
			}
		}

		return $text;

	}

}

// Include WP_List_Table.
require_once( ABSPATH . '/wp-admin/includes/class-wp-list-table.php' );

/**
 * Class GFConfirmationTable
 *    Handles the creation of a list table for displaying the confirmations listing.
 *
 * @since Unknown
 *
 * @param array $form The form to display the confirmation listing for.
 */
class GFConfirmationTable extends WP_List_Table {

	/**
	 * @since  Unknown
	 * @access public
	 *
	 * @var array The Form Object to get confirmations from.
	 */
	public $form;

	/**
	 * GFConfirmationTable constructor.
	 *
	 * @since  Unknown
	 *
	 * @param array $form The Form Object to display the confirmation listing for.
	 */
	public function __construct( $form ) {

		$this->form = $form;

		$this->_column_headers = array(
			array(
				'cb'      => '',
				'name'    => __( 'Name', 'gravityforms' ),
				'type'    => __( 'Type', 'gravityforms' ),
				'content' => __( 'Content', 'gravityforms' ),
			),
			array(),
			array( 'name' => array( 'name', false ) ),
			'name',
		);

		parent::__construct();
	}

	/**
	 * Prepares the confirmation items.
	 *
	 * @since  Unknown
	 */
	public function prepare_items() {

		$this->items = $this->form['confirmations'];

		switch ( rgget( 'orderby' ) ) {

			case 'name':

				// Sort confirmations alphabetically.
				usort( $this->items, array( $this, 'sort_confirmations' ) );

				// Reverse sort.
				if ( 'desc' === rgget( 'order' ) ) {
					$this->items = array_reverse( $this->items );
				}

				break;

			default:
				break;

		}

	}

	/**
	 * Sort confirmations alphabetically.
	 *
	 * @since  2.4
	 *
	 * @param array $a First confirmation to compare.
	 * @param array $b Second confirmation to compare.
	 *
	 * @return int
	 */
	public function sort_confirmations( $a = array(), $b = array() ) {

		return strcasecmp( $a['name'], $b['name'] );

	}

	/**
	 * Displays the list table.
	 *
	 * @since  Unknown
	 */
	public function display() {

		$singular = rgar( $this->_args, 'singular' );

		$this->display_tablenav( 'top' );
		?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>" cellspacing="0">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>

			<tbody id="the-list"<?php if ( $singular ) {
				echo " class='list:$singular'";
			} ?>>

			<?php $this->display_rows_or_placeholder(); ?>

			</tbody>
		</table>

		<?php

	}

	/**
	 * Displays a single list table row.
	 *
	 * @since  Unknown
	 *
	 * @param array $item The row item.
	 */
	public function single_row( $item ) {

		static $row_class = '';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		printf(
			'<tr id="confirmation-%s" %s>',
			esc_attr( $item['id'] ),
			$row_class
		);
		$this->single_row_columns( $item );
		echo '</tr>';

	}

	/**
	 * Gets the list table column headers.
	 *
	 * @since  Unknown
	 *
	 * @return string The primary column header.
	 */
	public function get_columns() {

		return $this->_column_headers[0];

	}

	/**
	 * Gets the column content.
	 *
	 * @since  Unknown
	 *
	 * @param array $item The column item to process.
	 *
	 * @return string
	 */
	public function column_content( $item ) {

		return self::get_column_content( $item );

	}

	/**
	 * Sets the default column data.
	 *
	 * @since  Unknown
	 *
	 * @param array  $item   The column item.
	 * @param string $column The column name.
	 */
	public function column_default( $item, $column ) {

		echo rgar( $item, $column );

	}

	/**
	 * Sets the column type.
	 *
	 * @since  Unknown
	 *
	 * @param object $item The column item.
	 *
	 * @return string The column type.
	 */
	public function column_type( $item ) {

		return self::get_column_type( $item );

	}

	/**
	 * Handles the activation/deactivation button on confirmation list table items.
	 *
	 * @since  Unknown
	 *
	 * @param array $item The list table item.
	 */
	public function column_cb( $item ) {

		if ( isset( $item['isDefault'] ) && $item['isDefault'] ) {
			return;
		}

		$active = rgar( $item, 'isActive' ) !== false;

		if ( $active ) {
			$class = 'gform-status--active';
			$text  = esc_html__( 'Active', 'gravityforms' );
		} else {
			$class = 'gform-status--inactive';
			$text  = esc_html__( 'Inactive', 'gravityforms' );
		}
		?>
		<button type="button" class="gform-status-indicator <?php echo esc_attr( $class ); ?>" onclick="ToggleActive( this, '<?php echo esc_js( $item['id'] ); ?>' );" onkeypress="ToggleActive( this, '<?php echo esc_js( $item['id'] ); ?>' );">
			<svg viewBox="0 0 6 6" xmlns="http://www.w3.org/2000/svg"><circle cx="3" cy="2" r="1" stroke-width="2"/></svg>
			<span class="gform-status-indicator-status"><?php echo esc_html( $text ); ?></span>
		</button>
		<?php

	}

	/**
	 * Displays the available confirmation list item actions.
	 *
	 * @since  Unknown
	 *
	 * @param array $item The list table column item.
	 */
	public function column_name( $item ) {

		$edit_url      = add_query_arg( array( 'cid' => $item['id'] ) );
		$duplicate_url = add_query_arg( array( 'cid' => 0, 'duplicatedcid' => $item['id'] ) );
		$actions       = apply_filters(
			'gform_confirmation_actions', array(
				'edit'      => '<a href="' . esc_url( $edit_url ) . '">' . __( 'Edit', 'gravityforms' ) . '</a>',
				'duplicate' => '<a href="' . esc_url( $duplicate_url ) . '">' . __( 'Duplicate', 'gravityforms' ) . '</a>',
				'delete'    => '<a class="submitdelete" onclick="javascript: if(confirm(\'' . __( 'WARNING: You are about to delete this confirmation.', 'gravityforms' ) . __( "\'Cancel\' to stop, \'OK\' to delete.", 'gravityforms' ) . '\')){ DeleteConfirmation(\'' . esc_js( $item['id'] ) . '\'); }" onkeypress="javascript: if(confirm(\'' . __( 'WARNING: You are about to delete this confirmation.', 'gravityforms' ) . __( "\'Cancel\' to stop, \'OK\' to delete.", 'gravityforms' ) . '\')){ DeleteConfirmation(\'' . esc_js( $item['id'] ) . '\'); }" style="cursor:pointer;">' . __( 'Delete', 'gravityforms' ) . '</a>',
			)
		);

		if ( isset( $item['isDefault'] ) && $item['isDefault'] ) {
			unset( $actions['delete'] );
		}


		?>

		<a href="<?php echo esc_url( $edit_url ); ?>"><strong><?php echo esc_html( rgar( $item, 'name' ) ); ?></strong></a>
		<div class="row-actions">

			<?php

			if ( is_array( $actions ) && ! empty( $actions ) ) {
				$keys     = array_keys( $actions );
				$last_key = array_pop( $keys );
				foreach ( $actions as $key => $html ) {
					$divider = $key == $last_key ? '' : ' | ';
					?>
					<span class="<?php echo $key; ?>">
                        <?php echo $html . $divider; ?>
                    </span>
					<?php

				}
			}
			?>

		</div>

		<?php

	}

	/**
	 * Displays the confirmations list item column content.
	 *
	 * @since  Unknown
	 *
	 * @param array $item The list item.
	 *
	 * @return string The HTML markup for the column content.
	 */
	public static function get_column_content( $item ) {

		switch ( rgar( $item, 'type' ) ) {

			case 'message':
				return '<a class="limit-text">' . wp_strip_all_tags( $item['message'] ) . '</a>';

			case 'page':

				$page = get_post( $item['pageId'] );
				if ( empty( $page ) ) {
					return __( '<em>This page does not exist.</em>', 'gravityforms' );
				}

				return '<a href="' . get_permalink( $item['pageId'] ) . '">' . esc_html( $page->post_title ) . '</a>';

			case 'redirect':
				$url_pieces    = parse_url( $item['url'] );
				$url_connector = rgar( $url_pieces, 'query' ) ? '&' : '?';
				$url           = rgar( $item, 'queryString' ) ? "{$item['url']}{$url_connector}{$item['queryString']}" : $item['url'];
				$url           = esc_url( $url );

				return '<a class="limit-text">' . $url . '</a>';
		}

		return '';

	}

	/**
	 * Gets the column type.
	 *
	 * @since  Unknown
	 *
	 * @param array $item The column item.
	 *
	 * @return string The column item type. If none found, empty string. Escaped.
	 */
	public static function get_column_type( $item ) {

		switch ( rgar( $item, 'type' ) ) {
			case 'message':
				return __( 'Text', 'gravityforms' );

			case 'page':
				return __( 'Page', 'gravityforms' );

			case 'redirect':
				return __( 'Redirect', 'gravityforms' );
		}

		return '';

	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 2.5
	 *
	 * @param string $which
	 */
	protected function extra_tablenav( $which ) {

		if ( $which !== 'top' ) {
			return;
		}

		printf(
			'<div class="alignright"><a href="%s" class="button">%s</a></div>',
			esc_url( add_query_arg( array( 'cid' => 0 ) ) ),
			esc_html__( 'Add New', 'gravityforms' )
		);

	}

}
