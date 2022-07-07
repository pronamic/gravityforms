<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


use Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Service_Provider;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Form_CRUD_Handler;

class GFFormDetail {

	public static function forms_page( $form_id ) {

		global $wpdb;

		if ( ! GFCommon::ensure_wp_version() ) {
			return;
		}

		self::update_recent_forms( $form_id );
		/**
		* @var Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Helper $save_form_helper
 		*/
		$save_form_helper = GFForms::get_service_container()->get( GF_Save_Form_Service_Provider::GF_SAVE_FROM_HELPER );
		$update_result = '';
		if ( rgpost( 'operation' ) == 'trash' ) {
			check_admin_referer( 'gforms_trash_form', 'gforms_trash_form' );
			GFFormsModel::trash_form( $form_id );
			?>
			<script type="text/javascript">
				jQuery(document).ready(
					function () {
						document.location.href = '?page=gf_edit_forms';
					}
				);
			</script>
			<?php
			exit;
		} elseif ( ! rgempty( 'gform_meta' ) && $save_form_helper->is_ajax_save_action() === false ) {
			check_admin_referer( "gforms_update_form_{$form_id}", 'gforms_update_form' );

			$update_result = self::save_form_info( $form_id, rgpost( 'gform_meta', false ) );

			?>
			<script type="text/javascript">
				var updateFormResult = <?php echo json_encode( $update_result ) ?>;
			</script>
			<?php
		}


		wp_print_styles( array( 'thickbox' ) );

		/* @var GF_Field_Address $gf_address_field  */
		$gf_address_field = GF_Fields::get( 'address' );

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		?>

		<script type="text/javascript" data-js-reload="gforms-scripts-globals">
			<?php GFCommon::gf_global(); ?>
			<?php GFCommon::gf_vars(); ?>
		</script>

		<script type="text/javascript"  data-js-reload="gforms-scripts-form-scripts" >

			function has_entry(fieldNumber) {
				var submitted_fields = [<?php echo RGFormsModel::get_submitted_fields( $form_id ); ?>];
				for (var i = 0; i < submitted_fields.length; i++) {
					if (submitted_fields[i] == fieldNumber)
						return true;
				}
				return false;
			}

			function InsertPostImageVariable(element_id, callback) {
				var variable = jQuery('#' + element_id + '_image_size_select').attr("variable");
				var size = jQuery('#' + element_id + '_image_size_select').val();
				if (size) {
					variable = "{" + variable + ":" + size + "}";
					InsertVariable(element_id, callback, variable);
					jQuery('#' + element_id + '_image_size_select').hide();
					jQuery('#' + element_id + '_image_size_select')[0].selectedIndex = 0;
				}
			}

			function InsertPostContentVariable(element_id, callback) {
				var variable = jQuery('#' + element_id + '_variable_select').val();
				var regex = /{([^{]*?: *(\d+\.?\d*).*?)}/;
				matches = regex.exec(variable);
				if (!matches) {
					InsertVariable(element_id, callback);
					return;
				}

				variable = matches[1];
				field_id = matches[2];

				for (var i = 0; i < form["fields"].length; i++) {
					if (form["fields"][i]["id"] == field_id) {
						if (form["fields"][i]["type"] == "post_image") {
							jQuery('#' + element_id + '_image_size_select').attr("variable", variable);
							jQuery('#' + element_id + '_image_size_select').show();
							return;
						}
					}
				}

				InsertVariable(element_id, callback);
			}

		</script>

		<?php

		$form = ! rgempty( 'meta', $update_result ) ? rgar( $update_result, 'meta' ) : GFFormsModel::get_form_meta( $form_id );

		if ( ! isset( $form['fields'] ) || ! is_array( $form['fields'] ) ){
			$form['fields'] = array();
		}

		if ( GFCommon::is_form_editor() ) {
			self::maybe_add_submit_button( $form );
		}

		$form = gf_apply_filters( array( 'gform_admin_pre_render', $form_id ), $form );

		if ( isset( $form['id'] ) ) {

			// Unset notifications and confirmations to reduce payload size.
			unset( $form['notifications'] );
			unset( $form['confirmations'] );

			echo "<script type=\"text/javascript\" data-js-reload=\"gforms-scripts-form-object\">var form = " . json_encode( $form ) . ';</script>';
		} else {
			echo "<script data-js-reload=\"gforms-scripts-form-object\" type=\"text/javascript\">var form = new Form();</script>";
		}

		?>
        <!-- Legacy Container allow old addons js to find legacy elements in a hidden container so they don't break other js code -->
		<div id="legacy_field_settings_container" data-js-reload="gforms-legacy-elements-container">
			<div id="field_settings">
				<ul>
					<li style="width:100px; padding:0px;">
						<a href="#gform_tab_1"><?php esc_html_e( 'General', 'gravityforms' ); ?></a>
					</li>
					<li style="width:100px; padding:0px; ">
						<a href="#gform_tab_3"><?php esc_html_e( 'Appearance', 'gravityforms' ); ?></a>
					</li>
					<li style="width:100px; padding:0px; ">
						<a href="#gform_tab_2"><?php esc_html_e( 'Advanced', 'gravityforms' ); ?></a>
					</li>
				</ul>
				<div id="gform_tab_1">

				</div>
				<div id="gform_tab_3">
				</div>

				<div id="gform_tab_2">
				</div>


			</div>
		</div>
        <!-- End legacy container -->

		<div class="wrap gforms_edit_form <?php echo GFCommon::get_browser_class() ?>" data-js="form-editor-wrapper">
        <?php
        $forms         = RGFormsModel::get_forms( null, 'title' );
        $id            = rgempty( 'id', $_GET ) ? count( $forms ) > 0 ? $forms[0]->id : '0' : rgget( 'id' );
        $browser_icons = array( 'ie', 'opera', 'chrome', 'firefox', 'safari', 'edge' );
        ?>

		<div id="gform-form-toolbar" class="gform-form-toolbar">
			<div class="gform-form-toolbar__logo">
				<a href="?page=gf_edit_forms">
					<span class="screen-reader-text"><?php esc_html_e( 'Return to form list', 'gravityforms' ); ?></span>
					<svg width="30" height="31" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M29 21.055c0 1.418-.998 3.156-2.22 3.861l-9.564 5.555c-1.222.705-3.217.705-4.44 0l-9.564-5.555C1.998 24.211 1 22.473 1 21.056V9.944c0-1.418.998-3.156 2.22-3.861L12.776.529c1.222-.705 3.217-.705 4.439 0l9.564 5.555c1.222.705 2.22 2.443 2.22 3.86v11.111z" fill="#fff"/><path class="g" d="M12.279 12.511H25V9H12.315c-1.814 0-3.324.612-4.47 1.816C5.073 13.707 5 21 5 21h19.913v-6.595h-3.578v3.084H8.81c.08-1.147.617-3.205 1.633-4.266.464-.484 1.052-.712 1.836-.712z" fill="#242748"/></svg>
				</a>
			</div>

			<div class="gform-form-toolbar__form-title gform-form-toolbar__form-title--form-editor" data-js-reload="gforms-form-switcher">
				<?php GFForms::form_switcher( $form['title'] ); ?>
			</div>

			<ul id="gform-form-toolbar__menu" class="gform-form-toolbar__menu">
				<?php
				$menu_items = apply_filters( 'gform_toolbar_menu', GFForms::get_toolbar_menu_items( $id ), $id );
				foreach ( $menu_items as $key => $item ) {
					if ( in_array( $key, array( 'edit', 'settings', 'entries' ) ) ) {
						$fixed_menu_items[ $key ] = $item;
					} else {
						$dynamic_menu_items[ $key ] = $item;
					}
				}
				echo GFForms::format_toolbar_menu_items( $fixed_menu_items );
				if ( ! empty( $dynamic_menu_items ) ) {
					echo '<span class="gform-form-toolbar__divider"></span>';
					echo GFForms::format_toolbar_menu_items( $dynamic_menu_items );
				}
				?>
			</ul>

			<div id="gf_toolbar_buttons_container" class="gf_toolbar_buttons_container">


				<?php
				/**
				 * Allow users to perform actions before toolbar buttons are displayed.
				 *
				 * @since 2.6
				 */
				do_action( 'gform_before_toolbar_buttons' );
				?>

				<?php
				$preview_args = array(
					'form_id' => $form_id,
				);
				echo GFCommon::get_preview_link( $preview_args );

				$ajax_save_disabled = $save_form_helper->is_ajax_save_disabled( $form_id );
				if ( $ajax_save_disabled ) {
					$save_button = '<button aria-disabled="false" aria-expanded="false" class="update-form gform-button gform-button--primary gform-button--icon-leading " onclick="SaveForm();" onkeypress="SaveForm();"> <i class="gform-button__icon gform-icon gform-icon--floppy-disk"></i>' . esc_html__( 'Save Form', 'gravityforms' ) . '</button>';
				} else {
					$save_button = '<button
						id="ajax-save-form-menu-bar"
						data-js="ajax-save-form"
						aria-disabled="false" 
						aria-expanded="false" 
						class="update-form update-form-ajax gform-button gform-button--primary gform-button--interactive gform-button--active-type-loader gform-button--icon-leading"
					>
						<i class="gform-button__icon gform-button__icon--inactive gform-icon gform-icon--floppy-disk" data-js="button-icon"></i>
						<span class="gform-button__text gform-button__text--inactive" data-js="button-inactive-text">
							' . esc_html__( 'Save Form', 'gravityforms' ) . '
						</span>
						<span class="gform-button__text gform-button__text--active" data-js="button-active-text">
							' . esc_html__( 'Saving', 'gravityforms' ) . '
						</span>
					</button>';
				}


				/**
				* A filter to allow you to modify the Form Save button.
				*
				* @since unknown
				*
				* @param string $save_button The Form Save button HTML.
				*/
				$save_button = apply_filters( 'gform_save_form_button', $save_button );
				echo $save_button;
				?>
				<span id="please_wait_container" style="display:none;"><i class='gficon-gravityforms-spinner-icon gficon-spin'></i></span>
			</div>
		</div>
		<form method="post" id="form_trash">
			<?php wp_nonce_field( 'gforms_trash_form', 'gforms_trash_form' ); ?>
			<input type="hidden" value="trash" name="operation" />
		</form>

		<div id="form_editor_ie_notice" class="form_editor_fields_container gform-hide-if-not-ie gform-browser-notice">
			<div class="gform-browser-notice__inner">
				<div class="gform-browser-notice__media gform-browser-notice__media--inline">
					<?php foreach( $browser_icons as $icon_name ) : ?>
						<img
							src="<?php echo GFCommon::get_image_url( 'browser-icons/' . $icon_name . '.png' ); ?>"
							alt="<?php echo $icon_name; ?> <?php _e( 'Browser Icon', 'gravityforms' ); ?>"
						/>
					<?php endforeach; ?>
				</div>
				<div class="gform-browser-notice__content">
					<p><?php _e( 'Hmmm, you seem to be using an unsupported browser. To get the most out of the Gravity Forms editing experience you’ll need to switch to a supported browser.', 'gravityforms' ); ?></p>
				</div>
			</div>
		</div>

		<?php
			$no_conflict_mode  = get_option( 'gform_enable_noconflict' );
			$no_conflict_class = $no_conflict_mode ? ' form_editor_no_conflict' : '';
			$no_fields_class   = empty( $form['fields'] ) ? ' form_editor_fields_no_fields' : '';
			$form_editor_class = sprintf( 'form_editor_fields_container gform-show-if-not-ie%s%s', $no_fields_class, $no_conflict_class );
		?>

		<div
			id="form_editor_fields_container"
			class="<?php esc_attr_e( $form_editor_class ); ?>"
			data-js="form-editor"
			<?php if ( ! $no_conflict_mode ) { ?>
			<?php echo ! empty( $form['fields'] ) ? 'data-simplebar' : ''; ?>
			<?php echo ! empty( $form['fields'] ) && is_rtl() ? 'data-simplebar-direction="rtl"' : ''; ?>
			<?php } ?>
		>
		<?php
		$has_pages  = GFCommon::has_pages( $form );
		$wrapper_el = GFCommon::is_legacy_markup_enabled( $form_id ) ? 'ul' : 'div';
		?>
		<?php GFFormDetail::editor_notices( $form ); ?>

			<div class="gform_wrapper gform_editor gravity-theme<?php echo GFCommon::is_legacy_markup_enabled( $form_id ) ? ' gform_legacy_markup' : ''; ?>">

			<div id="gform_pagination" data-title="<?php esc_attr_e('Pagination Options', 'gravityforms');?>" data-description="<?php esc_attr_e('Manage pagination options', 'gravityforms');?>" class="selectable" style="display:<?php echo $has_pages ? 'block' : 'none' ?>;">
				<div class="gf-pagebreak-first gf-pagebreak"><?php esc_html_e( 'START PAGING', 'gravityforms' ) ?></div>
			</div>

				<<?php echo $wrapper_el; ?> id="gform_fields" class="<?php echo GFCommon::get_ul_classes( $form ) ?>">

					<?php
					if ( is_array( rgar( $form, 'fields' ) ) ) {
						require_once( GFCommon::get_base_path() . '/form_display.php' );
						foreach ( $form['fields'] as $field ) {
							echo GFFormDisplay::get_field( $field, '', true, $form );
							if ( $field->layoutSpacerGridColumnSpan && ! GFCommon::is_legacy_markup_enabled( $form_id ) ) {
								printf( '<div class="spacer gfield" style="grid-column: span %d;" data-groupId="%s"></div>', $field->layoutSpacerGridColumnSpan, $field->layoutGroupId );
							}
						}
					}
					?>
				</<?php echo $wrapper_el; ?>>
				<div id="no-fields-drop" class="dropzone__target" style="<?php echo empty( $form['fields'] ) ? '' : 'display:none;'; ?>"></div>
				<div id="no-fields" class="dropzone__placeholder" style="<?php echo empty( $form['fields'] ) ? '' : 'display:none;'; ?>">
					<img class="gform-editor__no-fields-graphic" src="<?php echo GFCommon::get_base_url() . '/images/no-fields.svg'; ?>" alt="" />
					<p><?php esc_html_e( 'Simply drag and drop the fields or elements you want in this form.', 'gravityforms' ); ?></p>
				</div>

				<div id="gform_last_page_settings" data-title="<?php esc_attr_e('Last page options', 'gravityforms');?>" data-description="<?php esc_attr_e('Manage last page options', 'gravityforms');?>" class="selectable" style="display:<?php echo $has_pages ? 'block' : 'none' ?>;">
					<div class="gf-pagebreak-end gf-pagebreak"><?php esc_html_e( 'END PAGING', 'gravityforms' ) ?></div>
				</div>
			</div>



			<div>

				<div id="after_insert_dialog" style="display:none;">
					<h3><?php esc_html_e( 'You have successfully saved your form!', 'gravityforms' ); ?></h3>

					<p><?php esc_html_e( 'What would you like to do next?', 'gravityforms' ); ?></p>

					<div class="new-form-option">
						<a id="preview_form_link" href="<?php echo esc_url_raw( trailingslashit( site_url() ) ); ?>?gf_page=preview&id={formid}" target="_blank"><?php esc_html_e( 'Preview this Form', 'gravityforms' ); ?></a>
					</div>

					<?php if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) { ?>
						<div class="new-form-option">
							<a id="notification_form_link" href="#"><?php esc_html_e( 'Setup Email Notifications for this Form', 'gravityforms' ); ?></a>
						</div>
					<?php } ?>

					<div class="new-form-option">
						<a id="edit_form_link" href="#"><?php esc_html_e( 'Continue Editing this Form', 'gravityforms' ); ?></a>
					</div>

					<div class="new-form-option">
						<a href="?page=gf_edit_forms"><?php esc_html_e( 'Return to Form List', 'gravityforms' ); ?></a>
					</div>

				</div>


			</div>

		</div>

		<div class="editor-sidebar">

			<?php
			/**
			 * Filters custom sidebar panels.
			 *
			 * @since 2.5
			 *
			 * @param array $setting_panels        Custom panels array.
			 * @param array $from                  The current form object.
			 */
			$setting_panels = gf_apply_filters( array( 'gform_editor_sidebar_panels', $form_id ), array(), $form );
			?>

			<aside class="sidebar ui-tabs" role="region" >
				<div class="sidebar__nav-wrapper">
					<div class="search-button">
						<label for="form_editor_search_input" class="gform-visually-hidden"><?php echo esc_attr__( 'Search a form field by name', 'gravityforms' ); ?></label>
						<input id="form_editor_search_input" type="text" class="search-button__input" placeholder="<?php echo esc_attr__( 'Search for a field', 'gravityforms' ); ?>">
						<span class="clear-button"></span>
					</div>
					<ul class="sidebar__nav ui-tabs-nav">
						<li class="sidebar__nav__item ui-state-default ui-state-active ui-corner-top"><a href="#add_fields"><span class="sidebar__nav__item-text"><span class="sidebar__nav__item-text-inner"><?php esc_html_e( 'Add Fields', 'gravityforms' ); ?></span></span></a></li>
						<li class="sidebar__nav__item ui-state-default ui-corner-top" id="settings_tab_item"><a href="#field_settings_container"><span class="sidebar__nav__item-text"><span class="sidebar__nav__item-text-inner"><?php esc_html_e( 'Field Settings', 'gravityforms' ); ?></span></span></a></li>

						<?php
						foreach ( $setting_panels as $panel ) {
							if ( empty( $panel['id'] ) )
								continue;
								$panel_title       = empty( $panel['title'] ) ? esc_html__( 'Custom settings', 'gravityforms' ) : $panel['title'];
								$panel_nav_classes = ! empty( $panel['nav_classes'] ) ? $panel['nav_classes'] : array();
							?>
								<li class="sidebar__nav__item <?php echo is_array( $panel_nav_classes ) ? implode(' ', $panel_nav_classes) : $panel_nav_classes; ?>" ><a href="#<?php echo $panel['id']; ?>"><span class="sidebar__nav__item-text"><span class="sidebar__nav__item-text-inner"><?php echo $panel_title; ?></span></span></a></li>
								<?php
						}
						?>
					</ul>
				</div>
				<div class="sidebar__panel" id="add_fields">
					<div id="floatMenu" style="display: none !important;"></div>
					<!-- begin add button boxes -->
					<div class="sidebar-instructions">
						<p><?php esc_html_e( 'Drag a field to the left to start building your form and then start configuring it.', 'gravityforms' ); ?></p>
					</div>
					<div class="panel-block panel-block-tabs "id="add_fields_menu" data-simplebar<?php echo is_rtl() ? ' data-simplebar-direction="rtl"' : ''; ?>>
						<?php
						$field_groups = self::get_field_groups();

						foreach ( $field_groups as $group ) {
							$tooltip_class = empty( $group['tooltip_class'] ) ? 'tooltip_left' : $group['tooltip_class'];
							?>
							<div class="panel-block-tabs__wrapper">
								<button tabindex="0" class="panel-block-tabs__toggle" >
									<?php echo esc_html( $group['label'] ); ?>
								</button>
								<div class="panel-block-tabs__body panel-block-tabs__body--nopadding gf-field-group" id="add_<?php echo esc_attr( $group['name'] ); ?>">
									<div class="gf-field-group__no-results" style="display: none;">
										<span><?php esc_html_e( 'No Matching Fields', 'gravityforms' ); ?></span>
									</div>
									<ul class="add-buttons" >
										<?php self::display_buttons( $group['fields'] ); ?>
									</ul>
								</div>
							</div>
							<?php
						}
						?>
					</div>
					<!--end add button boxes -->

					<!-- this field allows us to force onblur events for field setting inputs that are otherwise not triggered
									when closing the field settings UI -->
					<input type="text" id="gform_force_focus" style="position:absolute;left:-9999em;" data-js="force-focus" />

					<form method="post" id="gform_update">
						<?php wp_nonce_field( "gforms_update_form_{$form_id}", 'gforms_update_form' ); ?>
						<input type="hidden" id="gform_meta" name="gform_meta" />
						<input type="hidden" id="gform_export" name="gform_export" value="false"/>
					</form>
				</div>
				<div class="sidebar__panel sidebar__panel--settings" id="field_settings_container" data-active-field-class="">
					<div class="panel-block" id="nothing_selected"><?php echo esc_html__( 'No field selected' ,'gravityforms' ); ?></div>
					<div class="panel-block panel-block--hidden"  id="sidebar_field_info">
						<div id="sidebar_field_icon"></div>
						<div id="sidebar_field_description">
							<div id="sidebar_field_label"></div>
							<div id="sidebar_field_text"></div>
						</div>
					</div>
					<div class="panel-block panel-block-tabs panel-block--hidden field_settings" data-js="gform-simplebar" data-simplebar-delay="1000">
						<button tabindex="0" id="general_tab_toggle" class="panel-block-tabs__toggle">
							<?php esc_html_e( 'General', 'gravityforms' ); ?>
						</button>
						<ul id="general_tab" class="panel-block-tabs__body panel-block-tabs__body--settings" data-js="form-editor-general-settings">
							<li class="pagination_setting">
								<label for="pagination_type_container" class="section_label">
									<?php esc_html_e( 'Progress Indicator', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_progress_indicator' ); ?>
								</label>
								<div id="pagination_type_container" class="pagination_container">
									<input type="radio" id="pagination_type_percentage" name="pagination_type" value="percentage" onclick='InitPaginationOptions();' onkeypress='InitPaginationOptions();' />
									<label for="pagination_type_percentage"  class="inline"><?php esc_html_e( 'Progress Bar', 'gravityforms' ); ?></label>&nbsp;&nbsp;
									<input type="radio" id="pagination_type_steps" name="pagination_type" value="steps" onclick='InitPaginationOptions();' onkeypress='InitPaginationOptions();' />
									<label for="pagination_type_steps" class="inline"><?php esc_html_e( 'Steps', 'gravityforms' ); ?></label>&nbsp;&nbsp;
									<input type="radio" id="pagination_type_none" name="pagination_type" value="none" onclick='InitPaginationOptions();' onkeypress='InitPaginationOptions();' />
									<label for="pagination_type_none" class="inline"><?php esc_html_e( 'None', 'gravityforms' ); ?></label>
								</div>
							</li>
							<li class="pagination_setting" id="percentage_style_setting">
								<div class="percentage_style_setting" style="z-index: 99;">
									<label for="percentage_style" style="display:block;" class="section_label">
										<?php esc_html_e( 'Progress Bar Style', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_percentage_style' ); ?>
									</label>
									<select id="percentage_style" onchange="TogglePercentageStyle();">
										<option value="blue">  <?php esc_html_e( 'Blue', 'gravityforms' ); ?>  </option>
										<option value="gray">  <?php esc_html_e( 'Gray', 'gravityforms' ); ?>  </option>
										<option value="green">  <?php esc_html_e( 'Green', 'gravityforms' ); ?>  </option>
										<option value="orange">  <?php esc_html_e( 'Orange', 'gravityforms' ); ?>  </option>
										<option value="red">  <?php esc_html_e( 'Red', 'gravityforms' ); ?>  </option>
										<option value="spring">  <?php esc_html_e( 'Gradient: Spring', 'gravityforms' ); ?>  </option>
										<option value="blues">  <?php esc_html_e( 'Gradient: Blues', 'gravityforms' ); ?>  </option>
										<option value="rainbow">  <?php esc_html_e( 'Gradient: Rainbow', 'gravityforms' ); ?>  </option>
										<option value="custom">  <?php esc_html_e( 'Custom', 'gravityforms' ); ?>  </option>
									</select>
								</div>
								<div class="percentage_custom_container">
									<label for="percentage_background_color" style="display:block;">
										<?php esc_html_e( 'Text Color', 'gravityforms' ); ?>
									</label>
									<?php self::color_picker( 'percentage_style_custom_color', '' ); ?>
								</div>
								<div class="percentage_custom_container">
									<label for="percentage_background_bgcolor" style="display:block;">
										<?php esc_html_e( 'Background Color', 'gravityforms' ); ?>
									</label>
									<?php self::color_picker( 'percentage_style_custom_bgcolor', '' ); ?>
								</div>
							</li>
							<li class="pagination_setting" id="page_names_setting">
								<label for="page_names_container" class="section_label">
									<?php esc_html_e( 'Page Names', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_page_names' ); ?>
								</label>
								<div id="page_names_container" style="margin-top:5px;">
									<!-- Populated dynamically from js.php -->
								</div>
							</li>
							<li class="pagination_setting" id="percentage_confirmation_display_setting">
								<div class="percentage_confirmation_display_setting">
									<input type="checkbox" id="percentage_confirmation_display" onclick="TogglePercentageConfirmationText()" onkeypress="TogglePercentageConfirmationText()">
									<label for="percentage_confirmation_display" class="inline">
										<?php esc_html_e( 'Display completed progress bar on confirmation', 'gravityforms' ); ?>
									</label>
								</div>
							</li>
							<li class="pagination_setting" id="percentage_confirmation_page_name_setting">
								<div class="percentage_confirmation_page_name_setting">
									<label for="percentage_confirmation_page_name" style="display:block;" class="section_label">
										<?php esc_html_e( 'Completion Text', 'gravityforms' ); ?>
									</label>
									<input type="text" id="percentage_confirmation_page_name"/>
								</div>
							</li>
							<li class="last_pagination_setting">
								<label for="last_page_button_container" class="section_label">
									<?php esc_html_e( 'Previous Button', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_last_page_button' ); ?>
								</label>

								<div class="last_page_button_options" id="last_page_button_container">
									<input type="radio" id="last_page_button_text" name="last_page_button" value="text" onclick="TogglePageButton('last_page');" onkeypress="TogglePageButton('last_page');"/>
									<label for="last_page_button_text" class="inline"><?php esc_html_e( 'Default', 'gravityforms' ); ?><?php gform_tooltip( 'previous_button_text' ); ?></label>
									&nbsp;&nbsp;
									<input type="radio" id="last_page_button_image" name="last_page_button" value="image" onclick="TogglePageButton('last_page');" onkeypress="TogglePageButton('last_page');"/>
									<label for="last_page_button_image" class="inline"><?php esc_html_e( 'Image', 'gravityforms' ); ?><?php gform_tooltip( 'previous_button_image' ); ?></label>

									<div id="last_page_button_text_container">
										<label for="last_page_button_text_input" class="section_label">
											<?php esc_html_e( 'Button Text:', 'gravityforms' ); ?>
										</label>
										<input type="text" id="last_page_button_text_input" class="input_size_b"/>
									</div>

									<div id="last_page_button_image_container">
										<label for="last_page_button_image_url" class="section_label">
											<?php esc_html_e( 'Image Path:', 'gravityforms' ); ?>
										</label>
										<input type="text" id="last_page_button_image_url"/>
									</div>
								</div>
							</li>

							<?php
							/**
							 * Inserts additional content within the General field settings
							 *
							 * Note: This action fires multiple times.  Use the first parameter to determine positioning on the list.
							 *
							 * @param int 0        The placement of the action being fired
							 * @param int $form_id The current form ID
							 */
							do_action( 'gform_field_standard_settings', 0, $form_id );
							?>
							<li class="label_setting field_setting">
								<label for="field_label" class="section_label">
									<?php esc_html_e( 'Field Label', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_label' ); ?>
                                    <?php gform_tooltip( 'form_field_label_html' ); ?>
								</label>
								<input type="text" id="field_label"/>
							</li>
							<li class="submit_type_setting field_setting">
								<label for="submit_type" class="section_label">
									<?php esc_html_e( 'Submit Input Type', 'gravityforms' ); ?>
								</label>
								<div>
									<input type="radio" name="submit_type" id="submit_type_text" value="text" onclick="return ToggleSubmitType( false );" onkeypress="return ToggleSubmitType( false );"/>
									<label for="submit_type_text" class="inline"><?php esc_html_e( 'Text', 'gravityforms' ); ?></label>

									<input type="radio" name="submit_type" id="submit_type_image" value="image" onclick="return ToggleSubmitType( false );" onkeypress="return ToggleSubmitType( false );"/>
									<label for="submit_type_image" class="inline"><?php esc_html_e( 'Image', 'gravityforms' ); ?></label>
								</div>
							</li>
							<li class="submit_text_setting field_setting">
								<label for="submit_text" class="section_label">
									<?php esc_html_e( 'Submit Button Text', 'gravityforms' ); ?>
								</label>
								<input type="text" id="submit_text"/>
							</li>
							<li class="submit_image_setting field_setting">
								<label for="submit_image" class="section_label">
									<?php esc_html_e( 'Submit Button Image URL', 'gravityforms' ); ?>
								</label>
								<input type="text" id="submit_image"/>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 5, $form_id );
							?>
							<li class="checkbox_label_setting field_setting">
								<label for="field_checkbox_label" class="section_label">
									<?php esc_html_e( 'Checkbox Label', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_checkbox_label' ); ?>
								</label>
								<input type="text" id="field_checkbox_label"/>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 10, $form_id );
							?>
							<li class="description_setting field_setting">
								<label for="field_description" class="section_label">
									<?php esc_html_e( 'Description', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_description' ); ?>
								</label>
								<textarea id="field_description" fieldheight-2"></textarea>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 20, $form_id );
							?>
							<li class="product_field_setting field_setting">
								<label for="product_field" class="section_label">
									<?php esc_html_e( 'Product Field Mapping', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_product' ); ?>
								</label>
								<select id="product_field" onchange="SetFieldProperty('productField', jQuery(this).val());">
									<!-- will be populated when field is selected (js.php) -->
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 25, $form_id );
							?>
							<li class="product_field_type_setting field_setting">
								<label for="product_field_type" class="section_label">
									<?php esc_html_e( 'Field Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_type' ); ?>
								</label>
								<select id="product_field_type" onchange="if(jQuery(this).val() == ''){return;
} StartChangeProductType(jQuery('#product_field_type').val());">
									<option value="singleproduct"><?php esc_html_e( 'Single Product', 'gravityforms' ); ?></option>
									<option value="select"><?php esc_html_e( 'Drop Down', 'gravityforms' ); ?></option>
									<option value="radio"><?php esc_html_e( 'Radio Buttons', 'gravityforms' ); ?></option>
									<option value="price"><?php esc_html_e( 'User Defined Price', 'gravityforms' ); ?></option>
									<option value="hiddenproduct"><?php esc_html_e( 'Hidden', 'gravityforms' ); ?></option>
									<option value="calculation"><?php esc_html_e( 'Calculation', 'gravityforms' ); ?></option>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 37, $form_id );
							?>
							<li class="shipping_field_type_setting field_setting">
								<label for="shipping_field_type" class="section_label">
									<?php esc_html_e( 'Field Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_type' ); ?>
								</label>
								<select id="shipping_field_type" onchange="if(jQuery(this).val() == ''){return;
} StartChangeShippingType(jQuery('#shipping_field_type').val())">
									<option value="singleshipping"><?php esc_html_e( 'Single Method', 'gravityforms' ); ?></option>
									<option value="select"><?php esc_html_e( 'Drop Down', 'gravityforms' ); ?></option>
									<option value="radio"><?php esc_html_e( 'Radio Buttons', 'gravityforms' ); ?></option>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 50, $form_id );
							?>
							<li class="base_price_setting field_setting">
								<label for="field_base_price" class="section_label">
									<?php esc_html_e( 'Price', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_base_price' ); ?>
								</label>
								<input type="text" id="field_base_price" onchange="SetBasePrice(this.value)"/>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 75, $form_id );
							?>
							<li class="disable_quantity_setting field_setting">
								<input type="checkbox" name="field_disable_quantity" id="field_disable_quantity" onclick="SetDisableQuantity(jQuery(this).is(':checked'));" onkeypress="SetDisableQuantity(jQuery(this).is(':checked'));"/>
								<label for="field_disable_quantity" class="inline">
									<?php esc_html_e( 'Disable quantity field', 'gravityforms' ); ?>

								</label>

							</li>
							<?php
							do_action( 'gform_field_standard_settings', 100, $form_id );
							?>
							<li class="option_field_type_setting field_setting">
								<label for="option_field_type" class="section_label">
									<?php esc_html_e( 'Field Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_type' ); ?>
								</label>
								<select id="option_field_type" onchange="if(jQuery(this).val() == ''){return;
}StartChangeInputType(jQuery('#option_field_type').val());">
									<option value="select"><?php esc_html_e( 'Drop Down', 'gravityforms' ); ?></option>
									<option value="checkbox"><?php esc_html_e( 'Checkboxes', 'gravityforms' ); ?></option>
									<option value="radio"><?php esc_html_e( 'Radio Buttons', 'gravityforms' ); ?></option>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 125, $form_id );
							?>
							<li class="donation_field_type_setting field_setting">
								<label for="donation_field_type" class="section_label">
									<?php esc_html_e( 'Field Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_type' ); ?>
								</label>
								<select id="donation_field_type" onchange="if(jQuery(this).val() == ''){return;
}StartChangeDonationType(jQuery('#donation_field_type').val());">
									<option value="select"><?php esc_html_e( 'Drop Down', 'gravityforms' ); ?></option>
									<option value="donation"><?php esc_html_e( 'User Defined Price', 'gravityforms' ); ?></option>
									<option value="radio"><?php esc_html_e( 'Radio Buttons', 'gravityforms' ); ?></option>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 150, $form_id );
							?>
							<li class="quantity_field_type_setting field_setting">
								<label for="quantity_field_type" class="section_label">
									<?php esc_html_e( 'Field Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_type' ); ?>
								</label>
								<select id="quantity_field_type" onchange="if(jQuery(this).val() == ''){return;
} StartChangeInputType(jQuery('#quantity_field_type').val())">
									<option value="number"><?php esc_html_e( 'Number', 'gravityforms' ); ?></option>
									<option value="select"><?php esc_html_e( 'Drop Down', 'gravityforms' ); ?></option>
									<option value="hidden"><?php esc_html_e( 'Hidden', 'gravityforms' ); ?></option>
								</select>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 200, $form_id );
							?>
							<li class="content_setting field_setting">
								<label for="field_content" class="section_label">
									<?php esc_html_e( 'Content', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_content' ); ?>
								</label>
								<textarea id="field_content" class="fieldheight-1 merge-tag-support mt-position-right mt-prepopulate"></textarea>

							</li>

							<?php
							do_action( 'gform_field_standard_settings', 225, $form_id );
							?>
							<li class="next_button_setting field_setting">
								<label for="next_button_container">
									<?php esc_html_e( 'Next Button', 'gravityforms' ); ?>
								</label>

								<div class="next_button_options" id="next_button_container">
									<input type="radio" id="next_button_text" name="next_button" value="text" onclick="TogglePageButton('next'); SetPageButton('next');" onkeypress="TogglePageButton('next'); SetPageButton('next');"/>
									<label for="next_button_text" class="inline"><?php esc_html_e( 'Default', 'gravityforms' ); ?><?php gform_tooltip( 'next_button_text' ); ?>
									</label>
									&nbsp;&nbsp;
									<input type="radio" id="next_button_image" name="next_button" value="image" onclick="TogglePageButton('next'); SetPageButton('next');" onkeypress="TogglePageButton('next'); SetPageButton('next');"/>
									<label for="next_button_image" class="inline"><?php esc_html_e( 'Image', 'gravityforms' ); ?><?php gform_tooltip( 'next_button_image' ); ?></label>

									<div id="next_button_text_container" style="margin-top:5px;">
										<label for="next_button_text_input" class="inline">
											<?php esc_html_e( 'Text:', 'gravityforms' ); ?>
										</label>
										<input type="text" id="next_button_text_input" class="input_size_b"/>
									</div>

									<div id="next_button_image_container" style="margin-top:5px;">
										<label for="next_button_image_url" class="inline">
											<?php esc_html_e( 'Image Path:', 'gravityforms' ); ?>
										</label>
										<input type="text" id="next_button_image_url"/>
									</div>
								</div>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 237, $form_id );
							?>
							<li class="previous_button_setting field_setting">
								<label for="previous_button_container">
									<?php esc_html_e( 'Previous Button', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_previous_button' ); ?>
								</label>

								<div class="previous_button_options" id="previous_button_container">
									<input type="radio" id="previous_button_text" name="previous_button" value="text" onclick="TogglePageButton('previous'); SetPageButton('previous');" onkeypress="TogglePageButton('previous'); SetPageButton('previous');"/>
									<label for="previous_button_text" class="inline"><?php esc_html_e( 'Default', 'gravityforms' ); ?><?php gform_tooltip( 'previous_button_text' ); ?></label>
									&nbsp;&nbsp;
									<input type="radio" id="previous_button_image" name="previous_button" value="image" onclick="TogglePageButton('previous'); SetPageButton('previous');" onkeypress="TogglePageButton('previous'); SetPageButton('previous');"/>
									<label for="previous_button_image" class="inline"><?php esc_html_e( 'Image', 'gravityforms' ); ?><?php gform_tooltip( 'previous_button_image' ); ?></label>

									<div id="previous_button_text_container" style="margin-top:5px;">
										<label for="previous_button_text_input" class="inline">
											<?php esc_html_e( 'Text:', 'gravityforms' ); ?>
										</label>
										<input type="text" id="previous_button_text_input" class="input_size_b"/>
									</div>

									<div id="previous_button_image_container" style="margin-top:5px;">
										<label for="previous_button_image_url" class="inline">
											<?php esc_html_e( 'Image Path:', 'gravityforms' ); ?>
										</label>
										<input type="text" id="previous_button_image_url"/>
									</div>
								</div>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 250, $form_id );
							?>
							<li class="disable_margins_setting field_setting">
								<input type="checkbox" id="field_margins" onclick="SetHTMLMargins( this.checked );" onkeypress="SetHTMLMargins( 'disableMargins' );"/>
								<label for="field_margins" class="inline">
									<?php esc_html_e( 'Disable default margins', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_disable_margins' ); ?>
								</label><br/>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 300, $form_id );
							?>
							<li class="post_custom_field_type_setting field_setting">
								<label for="post_custom_field_type" class="section_label">
									<?php esc_html_e( 'Field Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_type' ); ?>
								</label>
								<select id="post_custom_field_type" onchange="if(jQuery(this).val() == ''){return;
} StartChangePostCustomFieldType(jQuery('#post_custom_field_type').val());">
									<optgroup class="option_header" label="<?php esc_attr_e( 'Standard Fields', 'gravityforms' ); ?>">
										<option value="text"><?php esc_html_e( 'Single line text', 'gravityforms' ); ?></option>
										<option value="textarea"><?php esc_html_e( 'Paragraph Text', 'gravityforms' ); ?></option>
										<option value="select"><?php esc_html_e( 'Drop Down', 'gravityforms' ); ?></option>
										<option value="multiselect"><?php esc_html_e( 'Multi Select', 'gravityforms' ); ?></option>
										<option value="number"><?php esc_html_e( 'Number', 'gravityforms' ); ?></option>
										<option value="checkbox"><?php esc_html_e( 'Checkboxes', 'gravityforms' ); ?></option>
										<option value="radio"><?php esc_html_e( 'Radio Buttons', 'gravityforms' ); ?></option>
										<option value="hidden"><?php esc_html_e( 'Hidden', 'gravityforms' ); ?></option>
									</optgroup>
									<optgroup class="option_header" label="<?php esc_html_e( 'Advanced Fields', 'gravityforms' ); ?>">
										<option value="date"><?php esc_html_e( 'Date', 'gravityforms' ); ?></option>
										<option value="time"><?php esc_html_e( 'Time', 'gravityforms' ); ?></option>
										<option value="phone"><?php esc_html_e( 'Phone', 'gravityforms' ); ?></option>
										<option value="website"><?php esc_html_e( 'Website', 'gravityforms' ); ?></option>
										<option value="email"><?php esc_html_e( 'Email', 'gravityforms' ); ?></option>
										<option value="fileupload"><?php esc_html_e( 'File Upload', 'gravityforms' ); ?></option>
										<option value="list"><?php esc_html_e( 'List', 'gravityforms' ); ?></option>
									</optgroup>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 350, $form_id );
							?>
							<li class="post_tag_type_setting field_setting">
								<label for="post_tag_type" class="section_label">
									<?php esc_html_e( 'Field Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_type' ); ?>
								</label>
								<select id="post_tag_type" onchange="if(jQuery(this).val() == ''){return;
} StartChangeInputType(jQuery('#post_tag_type').val());">
									<option value="text"><?php esc_html_e( 'Single line text', 'gravityforms' ); ?></option>
									<option value="select"><?php esc_html_e( 'Drop Down', 'gravityforms' ); ?></option>
									<option value="checkbox"><?php esc_html_e( 'Checkboxes', 'gravityforms' ); ?></option>
									<option value="radio"><?php esc_html_e( 'Radio Buttons', 'gravityforms' ); ?></option>
									<option value="multiselect"><?php esc_html_e( 'Multi Select', 'gravityforms' ); ?></option>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 400, $form_id );
							?>
							<?php
							if ( class_exists( 'ReallySimpleCaptcha' ) ) {
							//the field_captcha_type drop down has options dynamically added in form_editor.js for the v1/v2 versions of google recaptcha
								?>
								<li class="captcha_type_setting field_setting">
									<label for="field_captcha_type">
										<?php esc_html_e( 'Type', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_captcha_type' ); ?>
									</label>
									<select id="field_captcha_type" onchange="StartChangeCaptchaType(jQuery(this).val())">
										<option value="simple_captcha"><?php esc_html_e( 'Really Simple CAPTCHA', 'gravityforms' ); ?></option>
										<option value="math"><?php esc_html_e( 'Math Challenge', 'gravityforms' ); ?></option>
									</select>
								</li>
								<?php
								do_action( 'gform_field_standard_settings', 450, $form_id );
								?>
								<li class="captcha_size_setting field_setting">
									<label for="field_captcha_size">
										<?php esc_html_e( 'Size', 'gravityforms' ); ?>
									</label>
									<select id="field_captcha_size" onchange="SetCaptchaSize(jQuery(this).val());">
										<option value="small"><?php esc_html_e( 'Small', 'gravityforms' ); ?></option>
										<option value="medium"><?php esc_html_e( 'Medium', 'gravityforms' ); ?></option>
										<option value="large"><?php esc_html_e( 'Large', 'gravityforms' ); ?></option>
									</select>
								</li>
								<?php
								do_action( 'gform_field_standard_settings', 500, $form_id );
								?>
								<li class="captcha_fg_setting field_setting">
									<label for="field_captcha_fg">
										<?php esc_html_e( 'Font Color', 'gravityforms' ); ?>
									</label>
									<?php self::color_picker( 'field_captcha_fg', 'SetCaptchaFontColor' ); ?>
								</li>
								<?php
								do_action( 'gform_field_standard_settings', 550, $form_id );
								?>
								<li class="captcha_bg_setting field_setting">
									<label for="field_captcha_bg">
										<?php esc_html_e( 'Background Color', 'gravityforms' ); ?>
									</label>
									<?php self::color_picker( 'field_captcha_bg', 'SetCaptchaBackgroundColor' ) ?>
								</li>
								<?php
								}
								do_action( 'gform_field_standard_settings', 600, $form_id );
								$recaptcha_type = get_option( 'rg_gforms_captcha_type' );
								$recaptcha_image_base = $recaptcha_type == 'invisible' ? '/images/captcha_invisible_' : '/images/captcha_';
								?>
								<li class="captcha_theme_setting field_setting">
									<label for="field_captcha_theme" class="section_label">
										<?php esc_html_e( 'Theme', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_recaptcha_theme' ); ?>
									</label>
									<select id="field_captcha_theme" onchange="SetCaptchaTheme(this.value, '<?php echo GFCommon::get_base_url() . $recaptcha_image_base ?>' + this.value + '.jpg')">
										<option value="light"><?php esc_html_e( 'Light', 'gravityforms' ); ?></option>
										<option value="dark"><?php esc_html_e( 'Dark', 'gravityforms' ); ?></option>
									</select>
								</li>
								<?php
								if ( $recaptcha_type == 'invisible' ) {
									do_action( 'gform_field_standard_settings', 625, $form_id );
									?>
									<li class="captcha_badge_setting field_setting">
										<label for="field_captcha_badge" class="section_label">
											<?php esc_html_e( 'Badge Position', 'gravityforms' ); ?>
											<?php gform_tooltip( 'form_field_recaptcha_badge' ); ?>
										</label>
										<select id="field_captcha_badge" onchange="SetFieldProperty('captchaBadge', jQuery(this).val());">
											<option value="bottomright"><?php esc_html_e( 'Bottom Right', 'gravityforms' ); ?></option>
											<option value="bottomleft"><?php esc_html_e( 'Bottom Left', 'gravityforms' ); ?></option>
											<option value="inline"><?php esc_html_e( 'Inline', 'gravityforms' ); ?></option>
										</select>
									</li>
									<?php
								}
								do_action( 'gform_field_standard_settings', 650, $form_id );
								?>
								<li class="post_custom_field_setting field_setting">
									<label for="field_custom_field_name" class="section_label">
										<?php esc_html_e( 'Custom Field Name', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_custom_field_name' ); ?>
									</label>
									<div class="gform_inline_options">
										<div>
											<input type="radio" name="field_custom" id="field_custom_existing" onclick="ToggleCustomField();" onkeypress="ToggleCustomField();"/>
											<label for="field_custom_existing" class="inline"><?php esc_html_e( 'Existing', 'gravityforms' ); ?></label>
										</div>
										<div>
											<input type="radio" name="field_custom" id="field_custom_new" onclick="ToggleCustomField();" onkeypress="ToggleCustomField();"/>
											<label for="field_custom_new" class="inline"><?php esc_html_e( 'New', 'gravityforms' ); ?></label>
										</div>
									</div>
									<input type="text" id="field_custom_field_name_text"/>
									<select id="field_custom_field_name_select" onchange="SetFieldProperty( 'postCustomFieldName', jQuery(this).val() );" style="max-width:100%;">
										<option value=""><?php esc_html_e( 'Select an existing custom field', 'gravityforms' ); ?></option>
										<?php
										$custom_field_names = RGFormsModel::get_custom_field_names();
										foreach ( $custom_field_names as $name ) {
											?>
											<option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></option>
											<?php
										}
										?>
									</select>
									</p>
								</li>
								<?php
								do_action( 'gform_field_standard_settings', 700, $form_id );
								?>
								<li class="post_status_setting field_setting">
									<label for="field_post_status" class="section_label">
										<?php esc_html_e( 'Post Status', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_post_status' ); ?>
									</label>
									<select id="field_post_status" name="field_post_status">
										<?php $post_stati = apply_filters( 'gform_post_status_options', array(
											'draft'   => esc_html__( 'Draft', 'gravityforms' ),
											'pending' => esc_html__( 'Pending Review', 'gravityforms' ),
											'publish' => esc_html__( 'Published', 'gravityforms' ),
										)
									);
									foreach ( $post_stati as $value => $label ) {
									?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php } ?>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 750, $form_id );
							?>
							<li class="post_author_setting field_setting">
								<label for="field_post_author" class="section_label">
									<?php esc_html_e( 'Default Post Author', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_post_author' ); ?>
								</label>
								<?php
								$args = array( 'name' => 'field_post_author' );
								$args = gf_apply_filters( array( 'gform_author_dropdown_args', rgar( $form, 'id' ) ), $args );
								wp_dropdown_users( $args );
								?>
								<div>
									<input type="checkbox" id="gfield_current_user_as_author"/>
									<label for="gfield_current_user_as_author" class="inline"><?php esc_html_e( 'Use logged in user as author', 'gravityforms' ); ?><?php gform_tooltip( 'form_field_current_user_as_author' ); ?></label>
								</div>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 775, $form_id );
							?>

							<?php if ( current_theme_supports( 'post-formats' ) ) { ?>

							<li class="post_format_setting field_setting">
								<label for="field_post_format" class="section_label">
									<?php esc_html_e( 'Post Format', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_post_format' ); ?>
								</label>

								<?php

								$post_formats = get_theme_support( 'post-formats' );
								$post_formats_dropdown = '<option value="0">Standard</option>';
								foreach ( $post_formats[0] as $post_format ) {
									$post_format_val       = esc_attr( $post_format );
									$post_format_text      = esc_html( $post_format );
									$post_formats_dropdown .= "<option value='$post_format_val'>" . ucfirst( $post_format_text ) . '</option>';
								}

								echo '<select name="field_post_format" id="field_post_format">' . $post_formats_dropdown . '</select>';

								?>

							</li>

							<?php } // if theme supports post formats ?>

							<?php
							do_action( 'gform_field_standard_settings', 800, $form_id );
							?>

							<li class="post_category_setting field_setting">
								<label for="field_post_category" class="section_label">
									<?php esc_html_e( 'Post Category', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_post_category' ); ?>
								</label>
								<?php wp_dropdown_categories( array( 'selected' => get_option( 'default_category' ), 'hide_empty' => 0, 'id' => 'field_post_category', 'name' => 'field_post_category', 'orderby' => 'name', 'selected' => 'field_post_category', 'hierarchical' => true ) ); ?>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 825, $form_id );
							?>

							<li class="post_category_field_type_setting field_setting">
								<label for="post_category_field_type" class="section_label">
									<?php esc_html_e( 'Field Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_type' ); ?>
								</label>
								<select id="post_category_field_type" onchange="StartChangeInputType( jQuery('#post_category_field_type').val() );">
									<option value="select"><?php esc_html_e( 'Drop Down', 'gravityforms' ); ?></option>
									<option value="checkbox"><?php esc_html_e( 'Checkboxes', 'gravityforms' ); ?></option>
									<option value="radio"><?php esc_html_e( 'Radio Buttons', 'gravityforms' ); ?></option>
									<option value="multiselect"><?php esc_html_e( 'Multi Select', 'gravityforms' ); ?></option>
								</select>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 850, $form_id );
							?>
							<li class="post_category_checkbox_setting field_setting">
								<label for="field_post_category">
									<?php esc_html_e( 'Category', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_post_category_selection' ); ?>
								</label>

								<input type="radio" id="gfield_category_all" name="gfield_category" value="all" onclick="ToggleCategory();" onkeypress="ToggleCategory();"/>
								<label for="gfield_category_all" class="inline"><?php esc_html_e( 'All Categories', 'gravityforms' ); ?></label>
								&nbsp;&nbsp;
								<input type="radio" id="gfield_category_select" name="gfield_category" value="select" onclick="ToggleCategory();" onkeypress="ToggleCategory();"/>
								<label for="gfield_category_select" class="inline"><?php esc_html_e( 'Select Categories', 'gravityforms' ); ?></label>

								<div id="gfield_settings_category_container" data-js-reload="gfield_settings_category_container">
									<table cellpadding="0" cellspacing="5">
										<?php
										$categories = get_categories( array( 'hide_empty' => 0 ) );
										$count = 0;
										$category_rows = '';
										self::_cat_rows( $categories, $count, $category_rows );
										echo $category_rows;

										?>
									</table>
								</div>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 875, $form_id );
							?>
							<li class="post_category_initial_item_setting field_setting">
								<input type="checkbox" id="gfield_post_category_initial_item_enabled" onclick="TogglePostCategoryInitialItem(); SetCategoryInitialItem();" onkeypress="TogglePostCategoryInitialItem(); SetCategoryInitialItem();"/>
								<label for="gfield_post_category_initial_item_enabled" class="inline">
									<?php esc_html_e( 'Display placeholder', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_post_category_initial_item' ); ?>
								</label>
							</li>
							<li id="gfield_post_category_initial_item_container">
								<label for="field_post_category_initial_item">
									<?php esc_html_e( 'Placeholder Label', 'gravityforms' ); ?>
								</label>
								<input type="text" id="field_post_category_initial_item" onchange="SetCategoryInitialItem();"/>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 900, $form_id );
							?>
							<li class="post_content_template_setting field_setting">
								<label class="section_label"><?php esc_html_e( 'Content Template', 'gravityforms' ) ?></label>
								<input type="checkbox" id="gfield_post_content_enabled" onclick="TogglePostContentTemplate();" onkeypress="TogglePostContentTemplate();"/>
								<label for="gfield_post_content_enabled" class="inline">
									<?php esc_html_e( 'Create content template', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_post_content_template_enable' ); ?>
								</label>

								<div id="gfield_post_content_container">
									<div>
										<?php GFCommon::insert_post_content_variables( $form['fields'], 'field_post_content_template', '', 25 ); ?>
									</div>
									<textarea id="field_post_content_template" fieldheight-1"></textarea>
								</div>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 950, $form_id );
							?>
							<li class="post_title_template_setting field_setting">
								<label class="section_label"><?php esc_html_e( 'Content Template', 'gravityforms' ) ?></label>
								<input type="checkbox" id="gfield_post_title_enabled" onclick="TogglePostTitleTemplate();" onkeypress="TogglePostTitleTemplate();"/>
								<label for="gfield_post_title_enabled" class="inline">
									<?php esc_html_e( 'Create content template', 'gravityforms' ); ?>
									 <?php gform_tooltip( 'form_field_post_title_template_enable' ); ?>
								</label>

								<div id="gfield_post_title_container">
									<input type="text" id="field_post_title_template" merge-tag-support mt-position-right mt-hide_all_fields mt-exclude-post_image-fileupload"
									/>
								</div>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 975, $form_id );
							?>
							<li class="customfield_content_template_setting field_setting">
								<input type="checkbox" id="gfield_customfield_content_enabled" onclick="ToggleCustomFieldTemplate(); SetCustomFieldTemplate();" onkeypress="ToggleCustomFieldTemplate(); SetCustomFieldTemplate();"/>
								<label for="gfield_customfield_content_enabled" class="inline">
									<?php esc_html_e( 'Create content template', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_customfield_content_template_enable' ); ?>
								</label>

								<div id="gfield_customfield_content_container">
									<div>
										<?php GFCommon::insert_post_content_variables( $form['fields'], 'field_customfield_content_template', 'SetCustomFieldTemplate', 25 ); ?>
									</div>
									<textarea id="field_customfield_content_template" fieldheight-1"></textarea>
								</div>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1000, $form_id );
							?>
							<li class="post_image_setting field_setting">
								<label class="section_label"><?php esc_html_e( 'Image Metadata', 'gravityforms' ); ?> <?php gform_tooltip( 'form_field_image_meta' ); ?></label>
								<input type="checkbox" id="gfield_display_alt" onclick="SetPostImageMeta();" onkeypress="SetPostImageMeta();"/>
								<label for="gfield_display_alt" class="inline">
									<?php esc_html_e( 'Alternative Text', 'gravityforms' ); ?>
								</label>
								<br/>
								<input type="checkbox" id="gfield_display_title" onclick="SetPostImageMeta();" onkeypress="SetPostImageMeta();"/>
								<label for="gfield_display_title" class="inline">
									<?php esc_html_e( 'Title', 'gravityforms' ); ?>
								</label>
								<br/>
								<input type="checkbox" id="gfield_display_caption" onclick="SetPostImageMeta();" onkeypress="SetPostImageMeta();"/>
								<label for="gfield_display_caption" class="inline">
									<?php esc_html_e( 'Caption', 'gravityforms' ); ?>
								</label>
								<br/>
								<input type="checkbox" id="gfield_display_description" onclick="SetPostImageMeta();" onkeypress="SetPostImageMeta();"/>
								<label for="gfield_display_description" class="inline">
									<?php esc_html_e( 'Description', 'gravityforms' ); ?>
								</label>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1025, $form_id );
							?>

							<li class="post_image_featured_image field_setting">
								<label class="section_label"><?php esc_html_e( 'Featured Image', 'gravityforms' ) ?></label>
								<input type="checkbox" id="gfield_featured_image" onclick="SetFeaturedImage();" onkeypress="SetFeaturedImage();"/>
								<label for="gfield_featured_image" class="inline"><?php esc_html_e( 'Set as Featured Image', 'gravityforms' ); ?><?php gform_tooltip( 'form_field_featured_image' ); ?></label>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1050, $form_id );
							?>
							<li class="address_setting field_setting">
								<?php

								$addressTypes = $gf_address_field->get_address_types( rgar( $form, 'id' ) );
								?>
								<label for="field_address_type" class="section_label">
									<?php esc_html_e( 'Address Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_address_type' ); ?>
								</label>
								<select id="field_address_type" onchange="ChangeAddressType();">
									<?php
									foreach ( $addressTypes as $key => $addressType ) {
										?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $addressType['label'] ); ?></option>
										<?php
									}
									?>
								</select>

								<div class="custom_inputs_sub_setting gfield_sub_setting">
									<label for="field_address_fields" class="section_label inline">
										<?php esc_html_e( 'Address Fields', 'gravityforms' ); ?>

									</label>

									<div id="field_address_fields_container" style="padding-top:10px;">
										<!-- content dynamically created from js.php -->
									</div>
								</div>

								<?php
								foreach ( $addressTypes as $key => $addressType ) {
									$state_label = isset( $addressType['state_label'] ) ? esc_attr( $addressType['state_label'] ) : __( 'State', 'gravityforms' );
								?>
								<div id="address_type_container_<?php echo esc_attr( $key ); ?>" class="gfield_sub_setting gfield_address_type_container">
									<input type="hidden" id="field_address_country_<?php echo esc_attr( $key ); ?>" value="<?php echo isset( $addressType['country'] ) ? esc_attr( $addressType['country'] ) : ''; ?>"/>
									<input type="hidden" id="field_address_zip_label_<?php echo esc_attr( $key ); ?>" value="<?php echo isset( $addressType['zip_label'] ) ? esc_attr( $addressType['zip_label'] ) : __( 'Postal Code', 'gravityforms' ); ?>"/>
									<input type="hidden" id="field_address_state_label_<?php echo esc_attr( $key ); ?>" value="<?php echo $state_label; ?>"/>
									<input type="hidden" id="field_address_has_states_<?php echo esc_attr( $key ); ?>" value="<?php echo is_array( rgget( 'states', $addressType ) ) ? '1' : ''; ?>"/>

									<?php
									if ( isset( $addressType['states'] ) && is_array( $addressType['states'] ) ) {
										?>
										<label for="field_address_default_state_<?php echo esc_attr( $key ); ?>" class="section_label">
										<?php echo sprintf( __( 'Default %s', 'gravityforms' ), $state_label ); ?>
										<?php gform_tooltip( "form_field_address_default_state_{$key}" ); ?>
									</label>

									<select id="field_address_default_state_<?php echo esc_attr( $key ); ?>" class="field_address_default_state" onchange="SetAddressProperties();">
										<?php echo $gf_address_field->get_state_dropdown( $addressType['states'] ); ?>
									</select>
										<?php
										}
									if ( ! isset( $addressType['country'] ) ) {
										?>
										<label for="field_address_default_country_<?php echo $key; ?>" class="section_label">
											<?php esc_html_e( 'Default Country', 'gravityforms' ); ?>
											<?php gform_tooltip( 'form_field_address_default_country' ); ?>
										</label>
										<select id="field_address_default_country_<?php echo $key; ?>" class="field_address_default_country" onchange="SetAddressProperties();">
											<?php echo $gf_address_field->get_country_dropdown() ?>
										</select>
										<?php
									}
									?>
								</div>
									<?php
								}
								?>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1100, $form_id );
							?>
							<li class="name_format_setting field_setting">
								<label for="field_name_format">
									<?php esc_html_e( 'Name Format', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_name_format' ); ?>
								</label>
								<select id="field_name_format" onchange="StartChangeNameFormat(jQuery(this).val());">
									<option value="extended"><?php esc_html_e( 'Extended', 'gravityforms' ); ?></option>
									<option value="advanced"><?php esc_html_e( 'Advanced', 'gravityforms' ); ?></option>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1125, $form_id );
							?>
							<li class="name_setting field_setting">
								<div class="custom_inputs_setting gfield_sub_setting">
									<label for="field_name_fields" class="section_label inline">
										<?php esc_html_e( 'Name Fields', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_name_fields' ); ?>
									</label>

									<div id="field_name_fields_container" style="padding-top:10px;">
										<!-- content dynamically created from js.php -->
									</div>
								</div>

							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1150, $form_id );
							?>
							<li class="date_input_type_setting field_setting">
								<label for="field_date_input_type" class="section_label">
									<?php esc_html_e( 'Date Input Type', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_date_input_type' ); ?>
								</label>
								<select id="field_date_input_type" onchange="SetDateInputType(jQuery(this).val());">
									<option value="datefield"><?php esc_html_e( 'Date Field', 'gravityforms' ) ?></option>
									<option value="datepicker"><?php esc_html_e( 'Date Picker', 'gravityforms' ) ?></option>
									<option value="datedropdown"><?php esc_html_e( 'Date Drop Down', 'gravityforms' ) ?></option>
								</select>

								<div id="date_picker_container">

									<input type="radio" id="gsetting_icon_none" name="gsetting_icon" value="none" onclick="SetCalendarIconType(this.value);" onkeypress="SetCalendarIconType(this.value);"/>
									<label for="gsetting_icon_none" class="inline"><?php esc_html_e( 'No Icon', 'gravityforms' ); ?></label>
									&nbsp;&nbsp;
									<input type="radio" id="gsetting_icon_calendar" name="gsetting_icon" value="calendar" onclick="SetCalendarIconType(this.value);" onkeypress="SetCalendarIconType(this.value);"/>
									<label for="gsetting_icon_calendar" class="inline"><?php esc_html_e( 'Calendar Icon', 'gravityforms' ); ?></label>
									&nbsp;&nbsp;
									<input type="radio" id="gsetting_icon_custom" name="gsetting_icon" value="custom" onclick="SetCalendarIconType(this.value);" onkeypress="SetCalendarIconType(this.value);"/>
									<label for="gsetting_icon_custom" class="inline"><?php esc_html_e( 'Custom Icon', 'gravityforms' ); ?></label>

									<div id="gfield_icon_url_container">
										<label for="gfield_calendar_icon_url" class="inline">
											<?php esc_html_e( 'Image Path: ', 'gravityforms' ); ?>
										</label>
										<input type="text" id="gfield_calendar_icon_url"/>

										<div class="instruction"><?php esc_html_e( 'Preview this form to see your custom icon.', 'gravityforms' ) ?></div>
									</div>
								</div>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1200, $form_id );
							?>
							<li class="date_format_setting field_setting">
								<label for="field_date_format" class="section_label">
									<?php esc_html_e( 'Date Format', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_date_format' ); ?>
								</label>
								<select id="field_date_format" onchange="SetDateFormat(jQuery(this).val());">
									<option value="mdy">mm/dd/yyyy</option>
									<option value="dmy">dd/mm/yyyy</option>
									<option value="dmy_dash">dd-mm-yyyy</option>
									<option value="dmy_dot">dd.mm.yyyy</option>
									<option value="ymd_slash">yyyy/mm/dd</option>
									<option value="ymd_dash">yyyy-mm-dd</option>
									<option value="ymd_dot">yyyy.mm.dd</option>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1225, $form_id );
							?>
							<li class="date_format_placement_setting field_setting">
								<label for="field_date_format_placement" class="section_label">
									<?php esc_html_e( 'Date Format Placement', 'gravityforms' ); ?>
								</label>
								<select id="field_date_format_placement" onchange="SetDateFormatPlacement(jQuery(this).val());">
									<option value="below"><?php esc_html_e( 'Below inputs', 'gravityforms' ); ?></option>
									<option value="above"><?php esc_html_e( 'Above inputs', 'gravityforms' ); ?></option>
									<option value="hidden_label"><?php esc_html_e( 'Hidden', 'gravityforms' ); ?></option>
									<option value="placeholder"><?php esc_html_e( 'Placeholder', 'gravityforms' ); ?></option>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1235, $form_id );
							?>
							<li class="customize_inputs_setting field_setting">
								<label for="field_enable_customize_inputs" class="inline">
									<?php esc_html_e( 'Customize Fields', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_customize_inputs' ); ?>
								</label>

								<div id="field_customize_inputs_container" style="padding-top:10px;">
									<!-- content dynamically created from js.php -->
								</div>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1250, $form_id );
							?>
							<li class="file_extensions_setting field_setting">
								<label for="field_file_extension" class="section_label">
									<?php esc_html_e( 'Allowed file extensions', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_fileupload_allowed_extensions' ); ?>
								</label>
								<input type="text" id="field_file_extension"/>

								<div>
									<small><?php esc_html_e( 'Separated with commas (i.e. jpg, gif, png, pdf)', 'gravityforms' ); ?></small>
								</div>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1260, $form_id );
							?>
							<li class="multiple_files_setting field_setting">
								<label class="section_label"><?php esc_html_e( 'Multiple Files', 'gravityforms' ); ?></label>

								<input type="checkbox" id="field_multiple_files" onclick="ToggleMultiFile();" onkeypress="ToggleMultiFile();"/>
								<label for="field_multiple_files" class="inline">
									<?php esc_html_e( 'Enable Multi-File Upload', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_multiple_files' ); ?>
								</label>

								<div id="gform_multiple_files_options">
									<br/>

									<div>
										<label for="field_max_files" class="section_label">
											<?php esc_html_e( 'Maximum Number of Files', 'gravityforms' ); ?>
											<?php gform_tooltip( 'form_field_max_files' ); ?>
										</label>
										<input type="text" id="field_max_files"/>
									</div>
									<br/>

								</div>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1267, $form_id );
							?>
							<li class="file_size_setting field_setting">
								<label for="field_max_file_size" class="section_label">
									<?php esc_html_e( 'Maximum File Size', 'gravityforms' ); ?>

								</label>
								<input type="text" id="field_max_file_size" placeholder="<?php $max_upload_size = wp_max_upload_size() / 1048576;
								echo $max_upload_size; ?>MB"/>

								<div id="gform_server_max_file_size_notice">
									<small><?php printf( esc_html__( 'Maximum allowed on this server: %sMB', 'gravityforms' ), $max_upload_size ); ?></small>
								</div>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1275, $form_id );
							?>
							<li class="columns_setting field_setting">

								<label class="section_label"><?php esc_html_e( 'Columns', 'gravityforms' ); ?></label>

								<input type="checkbox" id="field_columns_enabled" onclick="SetFieldProperty('enableColumns', this.checked); ToggleColumns();" onkeypress="SetFieldProperty('enableColumns', this.checked); ToggleColumns();"/>
								<label for="field_columns_enabled" class="inline"><?php esc_html_e( 'Enable multiple columns', 'gravityforms' ) ?><?php gform_tooltip( 'form_field_columns' ); ?></label>
								<br/>

								<div id="gfield_settings_columns_container">
									<ul id="field_columns"></ul>
								</div>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1287, $form_id );
							?>
							<li class="maxrows_setting field_setting">
								<label for="field_maxrows" class="section_label">
									<?php esc_html_e( 'Maximum Rows', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_maxrows' ); ?>
								</label>
								<input type="text" id="field_maxrows"/>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1300, $form_id );
							?>

							<li class="time_format_setting field_setting">
								<label for="field_time_format" class="section_label">
									<?php esc_html_e( 'Time Format', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_time_format' ); ?>
								</label>
								<select id="field_time_format" onchange="SetTimeFormat(this.value);">
									<option value="12"><?php esc_html_e( '12 hour', 'gravityforms' ) ?></option>
									<option value="24"><?php esc_html_e( '24 hour', 'gravityforms' ) ?></option>
								</select>

							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1325, $form_id );
							?>

							<li class="phone_format_setting field_setting">
								<label for="field_phone_format" class="section_label">
									<?php esc_html_e( 'Phone Format', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_phone_format' ); ?>
								</label>
								<select id="field_phone_format" onchange="SetFieldPhoneFormat(jQuery(this).val());">
									<?php
									$phone_formats = GF_Fields::get( 'phone' )->get_phone_formats( $form_id );

									foreach ( $phone_formats as $key => $phone_format ) {
									?>
									<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $phone_format['label'] ); ?></option>
									<?php
									}
									?>
								</select>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1350, $form_id );
							?>
							<li class="choices-ui__trigger-section">
								<span class="section_label" data-js="choices-ui-trigger-label"><?php esc_html_e( 'Choices', 'gravityforms' ); ?></span>
								<button
									class="choices-ui__trigger gform-button gform-button--size-r gform-button--white gform-button--icon-leading"
									data-js="choices-ui-trigger"
									style="display:none;"
								>
									<span class="gform-button__icon gform-icon gform-icon--cog choices-ui__trigger-icon"></span>
									<?php esc_html_e( 'Edit Choices', 'gravityforms' ); ?>
								</button>
							</li>
							<li class="choices_setting field_setting" data-js="choices-ui-setting" data-type="main">

								<div id="gfield_settings_choices_container">
									<label class="gfield_choice_header_label" data-js="choices-ui-label"><?php esc_html_e( 'Label', 'gravityforms' ) ?></label>
									<label class="gfield_choice_header_value" data-js="choices-ui-label"><?php esc_html_e( 'Value', 'gravityforms' ) ?></label>
									<label class="gfield_choice_header_price" data-js="choices-ui-label"><?php esc_html_e( 'Price', 'gravityforms' ) ?></label>
									<ul id="field_choices"></ul>
								</div>

								<div class="choices-ui__section" data-js="choices-ui-section" data-type="options">
									<h6 class="choices-ui__section-label"><?php esc_html_e( 'Options', 'gravityforms' ) ?></h6>
									<ul class="choices-ui__options-list" data-js="choices-ui-option-list">
										<li class="choices-ui__options-list-item">
											<input
												type="checkbox"
												id="field_choice_values_enabled"
												onclick="SetFieldProperty('enableChoiceValue', this.checked); ToggleChoiceValue(); SetFieldChoices();"
												onkeypress="SetFieldProperty('enableChoiceValue', this.checked); ToggleChoiceValue(); SetFieldChoices();"
											/>
											<label
												for="field_choice_values_enabled"
												class="inline gfield_value_label"
											><?php esc_html_e( 'Show Values', 'gravityforms' ) ?></label>
										</li>
									</ul>
								</div>

								<?php
								$window_title = esc_html__( 'Bulk Add / Predefined Choices', 'gravityforms' );
								$modal = json_encode( "<div class='tb-title'><div class='tb-title__logo'></div><div class='tb-title__text'><div class='tb-title__main'>" . $window_title . "</div><div class='tb-title__sub'>" . esc_html__( 'Select a category and customize the predefined choices or paste your own list to bulk add choices.', 'gravityforms' ) . "</div></div></div>" );
								?>
								<div class="choices-ui__section" data-js="choices-ui-section" data-type="bulk-choices">
									<h6 class="choices-ui__section-label"><?php esc_html_e( 'Add Bulk Choices', 'gravityforms' ) ?></h6>
									<input
										type='button'
										value='<?php echo esc_attr( $window_title ) ?>'
										onclick="tb_show(<?php echo esc_js( $modal ); ?>, '#TB_inline?height=460&amp;width=600&amp;inlineId=gfield_bulk_add', '');"
										onkeypress="tb_show(<?php echo esc_js( $modal ); ?>, '#TB_inline?height=460&amp;width=600&amp;inlineId=gfield_bulk_add', '');"
										class="gform-button gform-button--white gform-button--size-sm"
									/>
								</div>

								<div id="gfield_bulk_add" style="display:none;">
									<div class="gform_column_wrapper">
										<?php

										$predefined_choices = array(
											__( 'Countries', 'gravityforms' )                   => $gf_address_field->get_countries(),
											__( 'U.S. States', 'gravityforms' )                 => $gf_address_field->get_us_states(),
											__( 'Canadian Province/Territory', 'gravityforms' ) => $gf_address_field->get_canadian_provinces(),
											__( 'Continents', 'gravityforms' )                  => array( __( 'Africa', 'gravityforms' ), __( 'Antarctica', 'gravityforms' ), __( 'Asia', 'gravityforms' ), __( 'Australia', 'gravityforms' ), __( 'Europe', 'gravityforms' ), __( 'North America', 'gravityforms' ), __( 'South America', 'gravityforms' ) ),
											__( 'Gender', 'gravityforms' )                      => array( __( 'Male', 'gravityforms' ), __( 'Female', 'gravityforms' ), __( 'Prefer Not to Answer', 'gravityforms' ) ),
											__( 'Age', 'gravityforms' )                         => array( __( 'Under 18', 'gravityforms' ), __( '18-24', 'gravityforms' ), __( '25-34', 'gravityforms' ), __( '35-44', 'gravityforms' ), __( '45-54', 'gravityforms' ), __( '55-64', 'gravityforms' ), __( '65 or Above', 'gravityforms' ), __( 'Prefer Not to Answer', 'gravityforms' ) ),
											__( 'Marital Status', 'gravityforms' )              => array( __( 'Single', 'gravityforms' ), __( 'Married', 'gravityforms' ), __( 'Divorced', 'gravityforms' ), __( 'Widowed', 'gravityforms' ) ),
											__( 'Employment', 'gravityforms' )                  => array( __( 'Employed Full-Time', 'gravityforms' ), __( 'Employed Part-Time', 'gravityforms' ), __( 'Self-employed', 'gravityforms' ), __( 'Not employed but looking for work', 'gravityforms' ), __( 'Not employed and not looking for work', 'gravityforms' ), __( 'Homemaker', 'gravityforms' ), __( 'Retired', 'gravityforms' ), __( 'Student', 'gravityforms' ), __( 'Prefer Not to Answer', 'gravityforms' ) ),
											__( 'Job Type', 'gravityforms' )                    => array( __( 'Full-Time', 'gravityforms' ), __( 'Part-Time', 'gravityforms' ), __( 'Per Diem', 'gravityforms' ), __( 'Employee', 'gravityforms' ), __( 'Temporary', 'gravityforms' ), __( 'Contract', 'gravityforms' ), __( 'Intern', 'gravityforms' ), __( 'Seasonal', 'gravityforms' ) ),
											__( 'Industry', 'gravityforms' )                    => array( __( 'Accounting/Finance', 'gravityforms' ), __( 'Advertising/Public Relations', 'gravityforms' ), __( 'Aerospace/Aviation', 'gravityforms' ), __( 'Arts/Entertainment/Publishing', 'gravityforms' ), __( 'Automotive', 'gravityforms' ), __( 'Banking/Mortgage', 'gravityforms' ), __( 'Business Development', 'gravityforms' ), __( 'Business Opportunity', 'gravityforms' ), __( 'Clerical/Administrative', 'gravityforms' ), __( 'Construction/Facilities', 'gravityforms' ), __( 'Consumer Goods', 'gravityforms' ), __( 'Customer Service', 'gravityforms' ), __( 'Education/Training', 'gravityforms' ), __( 'Energy/Utilities', 'gravityforms' ), __( 'Engineering', 'gravityforms' ), __( 'Government/Military', 'gravityforms' ), __( 'Green', 'gravityforms' ), __( 'Healthcare', 'gravityforms' ), __( 'Hospitality/Travel', 'gravityforms' ), __( 'Human Resources', 'gravityforms' ), __( 'Installation/Maintenance', 'gravityforms' ), __( 'Insurance', 'gravityforms' ), __( 'Internet', 'gravityforms' ), __( 'Job Search Aids', 'gravityforms' ), __( 'Law Enforcement/Security', 'gravityforms' ), __( 'Legal', 'gravityforms' ), __( 'Management/Executive', 'gravityforms' ), __( 'Manufacturing/Operations', 'gravityforms' ), __( 'Marketing', 'gravityforms' ), __( 'Non-Profit/Volunteer', 'gravityforms' ), __( 'Pharmaceutical/Biotech', 'gravityforms' ), __( 'Professional Services', 'gravityforms' ), __( 'QA/Quality Control', 'gravityforms' ), __( 'Real Estate', 'gravityforms' ), __( 'Restaurant/Food Service', 'gravityforms' ), __( 'Retail', 'gravityforms' ), __( 'Sales', 'gravityforms' ), __( 'Science/Research', 'gravityforms' ), __( 'Skilled Labor', 'gravityforms' ), __( 'Technology', 'gravityforms' ), __( 'Telecommunications', 'gravityforms' ), __( 'Transportation/Logistics', 'gravityforms' ), __( 'Other', 'gravityforms' ) ),
											__( 'Education', 'gravityforms' )                   => array( __( 'High School', 'gravityforms' ), __( 'Associate Degree', 'gravityforms' ), __( "Bachelor's Degree", 'gravityforms' ), __( 'Graduate or Professional Degree', 'gravityforms' ), __( 'Some College', 'gravityforms' ), __( 'Other', 'gravityforms' ), __( 'Prefer Not to Answer', 'gravityforms' ) ),
											__( 'Days of the Week', 'gravityforms' )            => array( __( 'Sunday', 'gravityforms' ), __( 'Monday', 'gravityforms' ), __( 'Tuesday', 'gravityforms' ), __( 'Wednesday', 'gravityforms' ), __( 'Thursday', 'gravityforms' ), __( 'Friday', 'gravityforms' ), __( 'Saturday', 'gravityforms' ) ),
											__( 'Months of the Year', 'gravityforms' )          => array( __( 'January', 'gravityforms' ), __( 'February', 'gravityforms' ), __( 'March', 'gravityforms' ), __( 'April', 'gravityforms' ), __( 'May', 'gravityforms' ), __( 'June', 'gravityforms' ), __( 'July', 'gravityforms' ), __( 'August', 'gravityforms' ), __( 'September', 'gravityforms' ), __( 'October', 'gravityforms' ), __( 'November', 'gravityforms' ), __( 'December', 'gravityforms' ) ),
											__( 'How Often', 'gravityforms' )                   => array( __( 'Every day', 'gravityforms' ), __( 'Once a week', 'gravityforms' ), __( '2 to 3 times a week', 'gravityforms' ), __( 'Once a month', 'gravityforms' ), __( '2 to 3 times a month', 'gravityforms' ), __( 'Less than once a month', 'gravityforms' ) ),
											__( 'How Long', 'gravityforms' )                    => array( __( 'Less than a month', 'gravityforms' ), __( '1-6 months', 'gravityforms' ), __( '1-3 years', 'gravityforms' ), __( 'Over 3 years', 'gravityforms' ), __( 'Never used', 'gravityforms' ) ),
											__( 'Satisfaction', 'gravityforms' )                => array( __( 'Very Satisfied', 'gravityforms' ), __( 'Satisfied', 'gravityforms' ), __( 'Neutral', 'gravityforms' ), __( 'Unsatisfied', 'gravityforms' ), __( 'Very Unsatisfied', 'gravityforms' ) ),
											__( 'Importance', 'gravityforms' )                  => array( __( 'Very Important', 'gravityforms' ), __( 'Important', 'gravityforms' ), __( 'Somewhat Important', 'gravityforms' ), __( 'Not Important', 'gravityforms' ) ),
											__( 'Agreement', 'gravityforms' )                   => array( __( 'Strongly Agree', 'gravityforms' ), __( 'Agree', 'gravityforms' ), __( 'Disagree', 'gravityforms' ), __( 'Strongly Disagree', 'gravityforms' ) ),
											__( 'Comparison', 'gravityforms' )                  => array( __( 'Much Better', 'gravityforms' ), __( 'Somewhat Better', 'gravityforms' ), __( 'About the Same', 'gravityforms' ), __( 'Somewhat Worse', 'gravityforms' ), __( 'Much Worse', 'gravityforms' ) ),
											__( 'Would You', 'gravityforms' )                   => array( __( 'Definitely', 'gravityforms' ), __( 'Probably', 'gravityforms' ), __( 'Not Sure', 'gravityforms' ), __( 'Probably Not', 'gravityforms' ), __( 'Definitely Not', 'gravityforms' ) ),
											__( 'Size', 'gravityforms' )                        => array( __( 'Extra Small', 'gravityforms' ), __( 'Small', 'gravityforms' ), __( 'Medium', 'gravityforms' ), __( 'Large', 'gravityforms' ), __( 'Extra Large', 'gravityforms' ) ),

										);
										$predefined_choices = gf_apply_filters( array( 'gform_predefined_choices', rgar( $form, 'id' ) ), $predefined_choices );

										$custom_choices = RGFormsModel::get_custom_choices();

										?>

										<div class="bulk-left-panel-wrapper panel">
											<div class="bulk-left-panel">
												<ul id="bulk_items">
													<?php
													foreach ( array_keys( $predefined_choices ) as $name ) {
													$key = str_replace( "'", "\'", $name );
													?>
													<li>
														<a href="javascript:void(0);" onclick="SelectPredefinedChoice('<?php echo $key ?>');" onkeypress="SelectPredefinedChoice('<?php echo $key ?>');"
																class="bulk-choice"><?php echo $name ?>
														</a>
													</li>
													<?php } ?>
												</ul>
											</div>
										</div>
										<div class="bulk-arrow-mid">
											<svg width="28" height="28" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M14 27.5c7.456 0 13.5-6.044 13.5-13.5S21.456.5 14 .5.5 6.544.5 14 6.544 27.5 14 27.5z" fill="#fff" stroke="#9092B2"/>
												<path fill-rule="evenodd" clip-rule="evenodd" d="M14.91 18.28a.75.75 0 010-1.06L17.13 15H8.75a.75.75 0 010-1.5h8.38l-2.22-2.22a.75.75 0 111.06-1.06L20 14.25l-4.03 4.03a.75.75 0 01-1.06 0z" fill="#3E7DA6"/>
											</svg>
										</div>
										<div class="bulk-right-panel panel">
											<textarea id="gfield_bulk_add_input"></textarea>
										</div>
									</div>
									<div class="modal_footer">

										<div class="panel-buttons" style="">
											<input type="button" onclick="InsertBulkChoices(jQuery('#gfield_bulk_add_input').val().split('\n')); tb_remove();" onkeypress="InsertBulkChoices(jQuery('#gfield_bulk_add_input').val().split('\n')); tb_remove();" class="button-primary" value="<?php esc_attr_e( 'Insert Choices', 'gravityforms' ) ?>"/>&nbsp;
											<input type="button" onclick="tb_remove();" onkeypress="tb_remove();" class="button" value="<?php esc_attr_e( 'Cancel', 'gravityforms' ) ?>"/>
										</div>

										<div class="panel-custom" style="">
											<a href="javascript:void(0);" onclick="LoadCustomChoicesPanel(true, 0);" onkeypress="LoadCustomChoicesPanel(true, 0);" id="bulk_save_as"><?php esc_html_e( 'Save as new custom choice', 'gravityforms' ) ?>
												&nbsp;<span>&rarr;</span></a>

											<div id="bulk_custom_edit" style="display:none;">
												<?php esc_html_e( 'Save as', 'gravityforms' ); ?>
												<input type="text" id="custom_choice_name" value="<?php esc_attr_e( 'Enter name', 'gravityforms' ); ?>" onfocus="if(this.value == '<?php echo esc_js( __( 'enter name', 'gravityforms' ) ); ?>'){this.value='';
														}">&nbsp;&nbsp;
												<a href="javascript:void(0);" onclick="SaveCustomChoices();" onkeypress="SaveCustomChoices();" class="button" id="bulk_save_button"><?php esc_html_e( 'Save', 'gravityforms' ) ?></a>&nbsp;
												<a href="javascript:void(0);" onclick="CloseCustomChoicesPanel();" onkeypress="CloseCustomChoicesPanel();" id="bulk_cancel_link"><?php esc_html_e( 'Cancel', 'gravityforms' ) ?></a>
												<a href="javascript:void(0);" onclick="DeleteCustomChoice();" onkeypress="DeleteCustomChoice();" id="bulk_delete_link"><?php esc_html_e( 'Delete', 'gravityforms' ) ?></a>
											</div>
											<div id="bulk_custom_message" class="alert_yellow" style="display:none; margin-top:8px; padding: 8px;">
												<!--Message will be added via javascript-->
											</div>
										</div>

										<script type="text/javascript">
											var gform_selected_custom_choice = '';
											var gform_custom_choices = <?php echo GFCommon::json_encode( $custom_choices ) ?>;
											var gform_predefined_choices = <?php echo GFCommon::json_encode( $predefined_choices ) ?>;
										</script>

									</div>
								</div>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1360, $form_id );
							?>

							<li class="select_all_choices_setting field_setting" data-js="choices-ui-setting" data-type="option">

								<input
									type="checkbox"
									id="field_select_all_choices"
									onclick="var value = jQuery(this).is(':checked'); SetFieldProperty('enableSelectAll', value); RefreshSelectedFieldPreview();"
									onkeypress="var value = jQuery(this).is(':checked'); SetFieldProperty('enableSelectAll', value); RefreshSelectedFieldPreview();"
								/>
								<label for="field_select_all_choices" class="inline">
									<?php esc_html_e( 'Enable "Select All" choice', 'gravityforms' ); ?>
								</label>

							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1362, $form_id );
							?>

							<li class="other_choice_setting field_setting" data-js="choices-ui-setting" data-type="option">

								<input
									type="checkbox"
									id="field_other_choice"
									onclick="var value = jQuery(this).is(':checked'); SetFieldProperty('enableOtherChoice', value); UpdateFieldChoices(GetInputType(field));"
									onkeypress="var value = jQuery(this).is(':checked'); SetFieldProperty('enableOtherChoice', value); UpdateFieldChoices(GetInputType(field));"
								/>
								<label for="field_other_choice" class="inline">
									<?php esc_html_e( 'Enable "other" choice', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_other_choice' ); ?>
								</label>

							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1368, $form_id );
							?>

							<li class="email_confirm_setting field_setting">
								<input type="checkbox" id="gfield_email_confirm_enabled" onclick="SetEmailConfirmation(this.checked);" onkeypress="SetEmailConfirmation(this.checked);"/>
								<label for="gfield_email_confirm_enabled" class="inline">
									<?php esc_html_e( 'Enable Email Confirmation', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_email_confirm_enable' ); ?>
								</label>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1375, $form_id );
							?>
							<li class="password_setting field_setting">
								<div class="custom_inputs_setting gfield_sub_setting">
									<label for="field_password_fields" class="section_label inline">
										<?php esc_html_e( 'Password Fields', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_password_fields' ); ?>
									</label>

									<div id="field_password_fields_container" style="padding-top:10px;">
										<!-- content dynamically created from js.php -->
									</div>
								</div>

							</li>
							<li class="password_visibility_setting field_setting">
								<input type="checkbox" id="gfield_password_visibility_enabled" onclick="TogglePasswordVisibility(); SetFieldProperty('passwordVisibilityEnabled', this.checked);" onkeypress="TogglePasswordVisibility(); SetFieldProperty('passwordVisibilityEnabled', this.checked);"/>
								<label for="gfield_password_visibility_enabled" class="inline">
									<?php esc_html_e( 'Enable Password Visibility Toggle', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_password_visibility_enable' ); ?>
								</label>
							</li>
							<li class="password_strength_setting field_setting">
								<input type="checkbox" id="gfield_password_strength_enabled" onclick="TogglePasswordStrength(); SetPasswordStrength(this.checked);" onkeypress="TogglePasswordStrength(); SetPasswordStrength(this.checked);"/>
								<label for="gfield_password_strength_enabled" class="inline">
									<?php esc_html_e( 'Enable Password Strength', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_password_strength_enable' ); ?>
								</label>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1387, $form_id );
							?>

							<li id="gfield_min_strength_container">
								<label for="gfield_min_strength">
									<?php esc_html_e( 'Minimum Strength', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_password_strength_enable' ); ?>
								</label>
								<select id="gfield_min_strength" onchange="SetFieldProperty('minPasswordStrength', jQuery(this).val());">
									<option value=""><?php esc_html_e( 'None', 'gravityforms' ) ?></option>
									<option value="short"><?php esc_html_e( 'Short', 'gravityforms' ) ?></option>
									<option value="bad"><?php esc_html_e( 'Bad', 'gravityforms' ) ?></option>
									<option value="good"><?php esc_html_e( 'Good', 'gravityforms' ) ?></option>
									<option value="strong"><?php esc_html_e( 'Strong', 'gravityforms' ) ?></option>
								</select>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1400, $form_id );
							?>

							<li class="number_format_setting field_setting">
								<label for="field_number_format" class="section_label">
									<?php esc_html_e( 'Number Format', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_number_format' ); ?>
								</label>
								<select id="field_number_format" onchange="SetFieldProperty('numberFormat', this.value);jQuery('.field_calculation_rounding').toggle(this.value != 'currency');">
									<option value="decimal_dot">9,999.99</option>
									<option value="decimal_comma">9.999,99</option>
									<option value="currency"><?php esc_html_e( 'Currency', 'gravityforms' ) ?></option>
								</select>

							</li>

							<?php do_action( 'gform_field_standard_settings', 1415, $form_id ); ?>

							<li class="sub_labels_setting field_setting">
								<label for="field_sub_labels" class="section_label">
									<?php esc_html_e( 'Sub-Labels', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_sub_labels' ); ?>
								</label>

								<div id="field_sub_labels_container">
									<!-- content dynamically created from js.php -->
								</div>
							</li>

							<?php do_action( 'gform_field_standard_settings', 1425, $form_id ); ?>


							<?php do_action( 'gform_field_standard_settings', 1430, $form_id ); ?>
							<li class="credit_card_setting field_setting">
								<label class="section_label">
									<?php esc_html_e( 'Supported Credit Cards', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_credit_cards' ); ?>
								</label>
								<ul>
									<?php $cards = GFCommon::get_card_types();
									foreach ( $cards as $card ) {
									?>

									<li>
										<input type="checkbox" id="field_credit_card_<?php echo esc_attr( $card['slug'] ); ?>" value="<?php echo esc_attr( $card['slug'] ); ?>" onclick="SetCardType(this, this.value);" onkeypress="SetCardType(this, this.value);"/>
										<label for="field_credit_card_<?php echo esc_attr( $card['slug'] ); ?>" class="inline"><?php echo esc_html( $card['name'] ); ?></label>
									</li>

									<?php } ?>
								</ul>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1435, $form_id );
							?>

							<?php do_action( 'gform_field_standard_settings', 1440, $form_id ); ?>

							<li class="input_mask_setting field_setting">

								<input type="checkbox" id="field_input_mask" onclick="ToggleInputMask();" onkeypress="ToggleInputMask();"/>
								<label for="field_input_mask" class="inline">
									<?php esc_html_e( 'Input Mask', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_mask' ); ?>
								</label><br/>

								<div id="gform_input_mask">

									<div class="gform_inline_options">
										<div>
											<input type="radio" name="field_mask_option" id="field_mask_standard" onclick="ToggleInputMaskOptions();" onkeypress="ToggleInputMaskOptions();"/>
											<label for="field_mask_standard" class="inline"><?php esc_html_e( 'Standard', 'gravityforms' ); ?></label>
										</div>
										<div>
											<input type="radio" name="field_mask_option" id="field_mask_custom" onclick="ToggleInputMaskOptions();" onkeypress="ToggleInputMaskOptions();"/>
											<label for="field_mask_custom" class="inline"><?php esc_html_e( 'Custom', 'gravityforms' ); ?></label>
										</div>
									</div>

									<input type="text" id="field_mask_text"/>

									<p class="mask_text_description" style="margin:5px 0 0;">
										<?php esc_html_e( 'Enter a custom mask', 'gravityforms' ) ?>.
										<a href="javascript:void(0);" onclick="tb_show('<?php echo esc_js( __( 'Custom Mask Instructions', 'gravityforms' ) ); ?>', '#TB_inline?width=350&amp;inlineId=custom_mask_instructions', '');" onkeypress="tb_show('<?php echo esc_js( __( 'Custom Mask Instructions', 'gravityforms' ) ); ?>', '#TB_inline?width=350&amp;inlineId=custom_mask_instructions', '');"><?php esc_html_e( 'Help', 'gravityforms' ) ?></a>
									</p>

									<div id="custom_mask_instructions" style="display:none;">
										<div class="custom_mask_instructions">

											<h4><?php esc_html_e( 'Usage', 'gravityforms' ) ?></h4>
											<ul class="description-list">
												<li><?php esc_html_e( "Use a '9' to indicate a numerical character.", 'gravityforms' ) ?></li>
												<li><?php esc_html_e( "Use a lower case 'a' to indicate an alphabetical character.", 'gravityforms' ) ?></li>
												<li><?php esc_html_e( "Use an asterisk '*' to indicate any alphanumeric character.", 'gravityforms' ) ?></li>
												<li><?php esc_html_e( "Use a question mark '?' to indicate optional characters. Note: All characters after the question mark will be optional.", 'gravityforms' ) ?></li>
												<li><?php esc_html_e( 'All other characters are literal values and will be displayed automatically.', 'gravityforms' ) ?></li>
											</ul>

											<h4><?php esc_html_e( 'Examples', 'gravityforms' ) ?></h4>
											<ul class="examples-list">
												<li>
													<h5><?php esc_html_e( 'Date', 'gravityforms' ) ?></h5>
													<span class="label"><?php esc_html_e( 'Mask', 'gravityforms' ) ?></span>
													<code>99/99/9999</code><br/>
													<span class="label"><?php esc_html_e( 'Valid Input', 'gravityforms' ) ?></span>
													<code>10/21/2011</code>
												</li>
												<li>
													<h5><?php esc_html_e( 'Social Security Number', 'gravityforms' ) ?></h5>
													<span class="label"><?php esc_html_e( 'Mask', 'gravityforms' ) ?></span>
													<code>999-99-9999</code><br/>
													<span class="label"><?php esc_html_e( 'Valid Input', 'gravityforms' ) ?></span>
													<code>987-65-4329</code>
												</li>
												<li>
													<h5><?php esc_html_e( 'Course Code', 'gravityforms' ) ?></h5>
													<span class="label"><?php esc_html_e( 'Mask', 'gravityforms' ) ?></span>
													<code>aaa 999</code><br/>
													<span class="label"><?php esc_html_e( 'Valid Input', 'gravityforms' ) ?></span>
													<code>BIO 101</code>
												</li>
												<li>
													<h5><?php esc_html_e( 'License Key', 'gravityforms' ) ?></h5>
													<span class="label"><?php esc_html_e( 'Mask', 'gravityforms' ) ?></span>
													<code>***-***-***</code><br/>
													<span class="label"><?php esc_html_e( 'Valid Input', 'gravityforms' ) ?></span>
													<code>a9a-f0c-28Q</code>
												</li>
												<li>
													<h5><?php esc_html_e( 'Zip Code w/ Optional Plus Four', 'gravityforms' ) ?></h5>
													<span class="label"><?php esc_html_e( 'Mask', 'gravityforms' ) ?></span>
													<code>99999?-9999</code><br/>
													<span class="label"><?php esc_html_e( 'Valid Input', 'gravityforms' ) ?></span>
													<code>23462</code> or <code>23462-4062</code>
												</li>
											</ul>

										</div>
									</div>

									<select id="field_mask_select" onchange="SetFieldProperty('inputMaskValue', jQuery(this).val());">
										<option value=""><?php esc_html_e( 'Select a Mask', 'gravityforms' ); ?></option>
										<?php
										$masks = RGFormsModel::get_input_masks();
										foreach ( $masks as $mask_name => $mask_value ) {
										?>
										<option value="<?php echo esc_attr( $mask_value ); ?>"><?php echo esc_html( $mask_name ); ?></option>
										<?php
										}
										?>
									</select>

								</div>

							</li>

							<?php do_action( 'gform_field_standard_settings', 1450, $form_id ); ?>

							<li class="maxlen_setting field_setting">
								<label for="field_maxlen" class="section_label">
									<?php esc_html_e( 'Maximum Characters', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_maxlength' ); ?>
								</label>
								<input type="text" id="field_maxlen"/></input>
							</li>
							<?php
							do_action( 'gform_field_standard_settings', 1500, $form_id );
							?>

							<li class="range_setting field_setting">
								<div style="clear:both;">

									<label class="section_label"><?php esc_html_e( 'Range', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_number_range' ); ?>
									</label>
								</div>
								<div class="range_min">
									<input type="text" id="field_range_min"/>
									<label for="field_range_min">
										<?php esc_html_e( 'Min', 'gravityforms' ); ?>
									</label>
								</div>
								<div class="range_max">
									<input type="text" id="field_range_max"/>
									<label for="field_range_max">
										<?php esc_html_e( 'Max', 'gravityforms' ); ?>
									</label>

								</div>
								<br class="clear"/>
							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1550, $form_id );
							?>

							<li class="calculation_setting field_setting">

								<div class="field_enable_calculation">
									<input type="checkbox" id="field_enable_calculation" onclick="ToggleCalculationOptions(this.checked, field);" onkeypress="ToggleCalculationOptions(this.checked, field);"/>
									<label for="field_enable_calculation" class="inline">
										<?php esc_html_e( 'Enable Calculation', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_enable_calculation' ); ?>
									</label>
								</div>

								<div id="calculation_options" style="display:none;margin-top:10px;">

									<label for="field_calculation_formula">
										<?php esc_html_e( 'Formula', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_calculation_formula' ); ?>
									</label>

									<div>
										<?php GFCommon::insert_calculation_variables( $form['fields'], 'field_calculation_formula', '', 'FormulaContentCallback', 40 ); ?>
										<div class="gf_calculation_buttons">
											<?php foreach ( array( '+', '-', '/', '*', '(', ')', '.' ) as $button ) { ?>
											<input type="button" value="<?php echo in_array( $button, array( '.' ) ) ? $button : " $button "; ?>" onclick="InsertVariable('field_calculation_formula', 'FormulaContentCallback', this.value);" onkeypress="InsertVariable('field_calculation_formula', 'FormulaContentCallback', this.value);"/>
											<?php } ?>
										</div>
									</div>
									<textarea id="field_calculation_formula" fieldheight-2"></textarea>
									<br/>
									<a class="gf_calculation_trigger" href="javascript:void(0)" onclick="var field = GetSelectedField(); alert(IsValidFormula(field.calculationFormula) ? '<?php echo esc_js( __( 'The formula appears to be valid.', 'gravityforms' ) ); ?>' : '<?php echo esc_js( __( 'There appears to be a problem with the formula.', 'gravityforms' ) ); ?>');" onkeypress="var field = GetSelectedField(); alert(IsValidFormula(field.calculationFormula) ? '<?php echo esc_js( __( 'The formula appears to be valid.', 'gravityforms' ) ); ?>' : '<?php echo esc_js( __( 'There appears to be a problem with the formula.', 'gravityforms' ) ); ?>');"><?php esc_html_e( 'Validate Formula', 'gravityforms' ); ?></a>

									<div class="field_calculation_rounding">
										<label for="field_calculation_rounding" style="margin-top:10px;">
											<?php esc_html_e( 'Rounding', 'gravityforms' ); ?>
											<?php gform_tooltip( 'form_field_calculation_rounding' ); ?>
										</label>
										<select id="field_calculation_rounding" onchange="SetFieldProperty('calculationRounding', this.value);">
											<option value="0">0</option>
											<option value="1">1</option>
											<option value="2">2</option>
											<option value="3">3</option>
											<option value="4">4</option>
											<option value="norounding"><?php esc_html_e( 'Do not round', 'gravityforms' ); ?></option>
										</select>
									</div>

								</div>

								<br class="clear"/>

							</li>

							<?php
							do_action( 'gform_field_standard_settings', 1600, $form_id );
							?>

							<li class="rules_setting field_setting">
								<label for="rules" class="section_label"><?php esc_html_e( 'Rules', 'gravityforms' ); ?></label>

								<ul class="rules_container">
									<li>
										<input type="checkbox" id="field_required" onclick="SetFieldRequired(this.checked);" onkeypress="SetFieldRequired(this.checked);"/>
										<label for="field_required" class="inline">
											<?php esc_html_e( 'Required', 'gravityforms' ); ?>
											<?php gform_tooltip( 'form_field_required' ); ?>
										</label>

									</li>
									<li>
										<div class="duplicate_setting field_setting">
											<input type="checkbox" id="field_no_duplicates" onclick="SetFieldProperty('noDuplicates', this.checked);" onkeypress="SetFieldProperty('noDuplicates', this.checked);"/>
											<label for="field_no_duplicates" class="inline">
												<?php esc_html_e( 'No Duplicates', 'gravityforms' ); ?>
												<?php gform_tooltip( 'form_field_no_duplicate' ); ?>
											</label>
										</div>
									</li>
								</ul>

							</li>

							<?php
							do_action( 'gform_field_standard_settings', - 1, $form_id );
							?>
						</ul>

						<button tabindex="0" id="appearance_tab_toggle" class="panel-block-tabs__toggle">
							<?php esc_html_e( 'Appearance', 'gravityforms' ); ?>
						</button>
						<ul id="appearance_tab" class="panel-block-tabs__body panel-block-tabs__body--settings">

							<li class="pagination_setting">
								<label for="first_page_css_class" style="display:block;" class="section_label">
									<?php esc_html_e( 'CSS Class Name', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_css_class' ); ?>
								</label>
								<input type="text" id="first_page_css_class" size="30"/>
							</li>
							<?php

							/**
							 * Inserts additional content within the Appearance field settings
							 *
							 * Note: This action fires multiple times.  Use the first parameter to determine positioning on the list.
							 *
							 * @param int 0        The placement of the action being fired
							 * @param int $form_id The current form ID
							 */
							do_action( 'gform_field_appearance_settings', 0, $form_id );
							?>
							<li class="placeholder_setting field_setting">
								<label for="field_placeholder" class="section_label">
									<?php esc_html_e( 'Placeholder', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_placeholder' ); ?>
								</label>
								<input type="text" id="field_placeholder" class="field_placeholder  merge-tag-support mt-position-right mt-prepopulate"/>
								<span id="placeholder_warning" style="display:none"><?php _e( 'Placeholder text is not supported when using the Rich Text Editor.', 'gravityforms' ); ?></span>
							</li>
							<?php
							do_action( 'gform_field_appearance_settings', 20, $form_id );
							?>
							<li class="placeholder_textarea_setting field_setting">
								<label for="field_placeholder_textarea" class="section_label">
									<?php esc_html_e( 'Placeholder', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_placeholder' ); ?>
								</label>
								<textarea id="field_placeholder_textarea" class="field_placeholder_textarea merge-tag-support mt-position-right mt-prepopulate"></textarea>
								<span id="placeholder_warning" style="display:none"><?php _e( 'Placeholder text is not supported when using the Rich Text Editor.', 'gravityforms' ); ?></span>
							</li>
							<?php
							do_action( 'gform_field_appearance_settings', 50, $form_id );
							?>

							<li class="input_placeholders_setting field_setting">
								<label for="placeholders" class="section_label">
									<?php esc_html_e( 'Placeholders', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_input_placeholders' ); ?>
								</label>

								<div id="field_input_placeholders_container">
									<!-- content dynamically created from js.php -->
								</div>
							</li>

							<?php
							do_action( 'gform_field_appearance_settings', 100, $form_id );

							$label_placement_form_setting = rgar( $form, 'labelPlacement' );
							switch ( $label_placement_form_setting ) {
								case 'left_label' :
									$label_placement_form_setting_label = __( 'Left aligned', 'gravityforms' );
									break;
								case 'right_label' :
									$label_placement_form_setting_label = __( 'Right aligned', 'gravityforms' );
									break;
								case 'top_label' :
								default :
									$label_placement_form_setting_label = __( 'Top aligned', 'gravityforms' );
							}

							$description_placement_form_setting = rgar( $form, 'descriptionPlacement' );
							$description_placement_form_setting_label = $description_placement_form_setting == 'above' ? $description_placement_form_setting_label = __( 'Above inputs', 'gravityforms' ) : $description_placement_form_setting_label = __( 'Below inputs', 'gravityforms' );
							?>
							<li class="label_placement_setting field_setting">
								<label for="field_label_placement" class="section_label">
									<?php esc_html_e( 'Field Label Visibility', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_label_placement' ); ?>
								</label>
								<select id="field_label_placement" onchange="SetFieldLabelPlacement(jQuery(this).val());">
									<option value=""><?php printf( __( 'Visible (%s)', 'gravityforms' ), esc_html( $label_placement_form_setting_label ) ); ?></option>
									<option value="hidden_label"><?php esc_html_e( 'Hidden', 'gravityforms' ); ?></option>
								</select>
								<div id="field_description_placement_container" style="display:none; padding-top:10px;">
									<label for="field_description_placement" class="section_label">
										<?php esc_html_e( 'Description Placement', 'gravityforms' ); ?>
										 <?php gform_tooltip( 'form_field_description_placement' ); ?>
									</label>
									<select id="field_description_placement"
											onchange="SetFieldDescriptionPlacement(jQuery(this).val());">
										<option
												value=""><?php printf( __( 'Use Form Setting (%s)', 'gravityforms' ), esc_html( $description_placement_form_setting_label ) ); ?></option>
										<option value="below"><?php esc_html_e( 'Below inputs', 'gravityforms' ); ?></option>
										<option value="above"><?php esc_html_e( 'Above inputs', 'gravityforms' ); ?></option>
									</select>
								</div>
							</li>
							<?php

							do_action( 'gform_field_appearance_settings', 150, $form_id );

							$sub_label_placement_form_setting = rgar( $form, 'subLabelPlacement' );
							$sub_label_placement_form_setting_label = $sub_label_placement_form_setting == 'above' ? $sub_label_placement_form_setting_label = __( 'Above inputs', 'gravityforms' ) : $sub_label_placement_form_setting_label = __( 'Below inputs', 'gravityforms' );
							?>
							<li class="sub_label_placement_setting field_setting">
								<label for="field_sub_label_placement" class="section_label">
									<?php esc_html_e( 'Sub-Label Placement', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_sub_label_placement' ); ?>
								</label>
								<select id="field_sub_label_placement"
										onchange="SetFieldSubLabelPlacement(jQuery(this).val());">
									<option
											value=""><?php printf( __( 'Use Form Setting (%s)', 'gravityforms' ), esc_html( $sub_label_placement_form_setting_label ) ); ?></option>
									<option value="below"><?php esc_html_e( 'Below inputs', 'gravityforms' ); ?></option>
									<option value="above"><?php esc_html_e( 'Above inputs', 'gravityforms' ); ?></option>
									<option value="hidden_label"><?php esc_html_e( 'Hidden', 'gravityforms' ); ?></option>
								</select>
							</li>
							<?php do_action( 'gform_field_appearance_settings', 200, $form_id ); ?>

							<li class="error_message_setting field_setting">
								<label for="field_error_message" class="section_label">
									<?php esc_html_e( 'Custom Validation Message', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_validation_message' ); ?>
								</label>
								<input type="text" id="field_error_message"/>
							</li>

							<?php
							do_action( 'gform_field_appearance_settings', 250, $form_id );
							?>

							<li class="submit_width_setting field_setting">
								<label for="submit_width" class="section_label">
									<?php esc_html_e( 'Submit Button Width', 'gravityforms' ); ?>
								</label>
								<div>
									<input type="radio" name="submit_width" id="submit_width_auto" value="auto" onclick="return SetSubmitWidth( this.value );" onkeypress="return SetSubmitWidth( this.value );"/>
									<label for="submit_width_auto" class="inline"><?php esc_html_e( 'Auto', 'gravityforms' ); ?></label>

									<input type="radio" name="submit_width" id="submit_width_full" value="full" onclick="return SetSubmitWidth( this.value );" onkeypress="return SetSubmitWidth( this.value );"/>
									<label for="submit_width_full" class="inline"><?php esc_html_e( 'Fill Container', 'gravityforms' ); ?></label>
								</div>
							</li>

							<?php
							$disable_location = '';
							if ( $form['fields'] ) {
								foreach ( $form['fields'] as $field ) {
									if ( $field['type'] === 'page' ) {
										$disable_location = 'disabled';
									}
								}
							}
							 ?>

							<li class="submit_location_setting field_setting">
								<label for="submit_location" class="section_label">
									<?php esc_html_e( 'Submit Button Location', 'gravityforms' ); ?>
								</label>
								<div>
									<input type="radio" name="submit_location" id="submit_location_bottom" value="bottom" onclick="return SetSubmitLocation( this.value );" onkeypress="return SetSubmitLocation( this.value );"/>
									<label for="submit_location_bottom" class="inline"><?php esc_html_e( 'End of the form', 'gravityforms' ); ?></label>

									<input type="radio" name="submit_location" id="submit_location_inline" value="inline" <?php echo $disable_location ?> onclick="return SetSubmitLocation( this.value );" onkeypress="return SetSubmitLocation( this.value );"/>
									<label for="submit_location_inline" class="inline"><?php esc_html_e( 'End of the last row', 'gravityforms' ); ?></label>
								</div>
							</li>


							<li class="css_class_setting field_setting">
								<label for="field_css_class" class="section_label">
									<?php esc_html_e( 'Custom CSS Class', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_css_class' ); ?>
								</label>
								<input type="text" id="field_css_class"/>
							</li>

							<?php
							do_action( 'gform_field_appearance_settings', 300, $form_id );
							?>

							<li class="enable_enhanced_ui_setting field_setting">
								<input type="checkbox" id="gfield_enable_enhanced_ui" onclick="SetFieldEnhancedUI(jQuery(this).is(':checked'));" onkeypress="SetFieldEnhancedUI(jQuery(this).is(':checked'));"/>
								<label for="gfield_enable_enhanced_ui" class="inline">
									<?php esc_html_e( 'Enable enhanced user interface', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_enable_enhanced_ui' ); ?>
								</label>
							</li>

							<?php
							do_action( 'gform_field_appearance_settings', 400, $form_id );

							$size_choices = GF_Fields::get( 'text' )->get_size_choices();
							?>

							<li class="size_setting field_setting">
								<label for="field_size" class="section_label">
									<?php esc_html_e( 'Field Size', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_size' ); ?>
								</label>
								<select id="field_size" onchange="SetFieldSize(jQuery(this).val());"><?php
								foreach ( $size_choices as $size_choice ) {
									if ( empty( $size_choice['value'] ) || empty( $size_choice['text'] ) ) {
										continue;
									}
									printf( '<option value="%s">%s</option>', esc_attr( $size_choice['value'] ), esc_html( $size_choice['text'] ) );
								}
								?></select>
							</li>
							<?php
							do_action( 'gform_field_appearance_settings', 500, $form_id );
							?>

						</ul>

						<button tabindex="0" id="advanced_tab_toggle" class="panel-block-tabs__toggle">
							<?php esc_html_e( 'Advanced', 'gravityforms' ); ?>
						</button>
						<ul id="advanced_tab" class="panel-block-tabs__body panel-block-tabs__body--settings">
							<?php
							/**
							 * Inserts additional content within the Advanced field settings
							 *
							 * Note: This action fires multiple times.  Use the first parameter to determine positioning on the list.
							 *
							 * @param int 0        The placement of the action being fired
							 * @param int $form_id The current form ID
							 */
							do_action( 'gform_field_advanced_settings', 0, $form_id );
							?>
							<li class="admin_label_setting field_setting">
								<label for="field_admin_label" class="section_label">
									<?php esc_html_e( 'Admin Field Label', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_admin_label' ); ?>
								</label>
								<input type="text" id="field_admin_label"/>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 25, $form_id );
							do_action( 'gform_field_advanced_settings', 35, $form_id );
							do_action( 'gform_field_advanced_settings', 50, $form_id );
							do_action( 'gform_field_advanced_settings', 100, $form_id );
							do_action( 'gform_field_advanced_settings', 125, $form_id );
							?>
							<li class="default_value_setting field_setting">
								<label for="field_default_value" class="section_label">
									<?php esc_html_e( 'Default Value', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_default_value' ); ?>
								</label>
								<input type="text" id="field_default_value" class="field_default_value  merge-tag-support mt-position-right mt-prepopulate"/>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 150, $form_id );
							?>
							<li class="default_value_textarea_setting field_setting">
								<label for="field_default_value_textarea" class="section_label">
									<?php esc_html_e( 'Default Value', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_default_value' ); ?>
								</label>
								<textarea id="field_default_value_textarea" class="field_default_value merge-tag-support mt-position-right mt-prepopulate"></textarea>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 155, $form_id );
							?>
							<li class="name_prefix_choices_setting field_setting" style="display:none;">
								<label for="gfield_settings_prefix_input_choices_container" class="section_label">
									<?php esc_html_e( 'Prefix Choices', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_name_prefix_choices' ); ?>
								</label>

								<div id="gfield_settings_prefix_input_choices_container" class="gfield_settings_input_choices_container">
									<label class="gfield_choice_header_label"><?php esc_html_e( 'Label', 'gravityforms' ) ?></label><label class="gfield_choice_header_value"><?php esc_html_e( 'Value', 'gravityforms' ) ?></label>
									<ul id="field_prefix_choices" class="field_input_choices">
										<!-- content dynamically created from js.php -->
									</ul>
								</div>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 165, $form_id );
							?>
							<li class="autocomplete_setting field_setting">
								<input type="checkbox" id="field_enable_autocomplete" onclick="SetAutocompleteProperty( false, this.checked);" onkeypress="setAutocompleteProperty( false, this.checked);"/>
								<label for="field_enable_autocomplete" class="inline"><?php esc_html_e( 'Enable Autocomplete', 'gravityforms' ); ?><?php gform_tooltip( 'form_field_autocomplete' ); ?></label>
								<div id="autocomplete_attribute_container">
									<!-- content dynamically generated from js.php -->
								</div>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 175, $form_id );
							?>
							<li class="default_input_values_setting field_setting">
								<label for="default values" class="section_label">
									<?php esc_html_e( 'Default Values', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_default_input_values' ); ?>
								</label>

								<div id="field_default_input_values_container">
									<!-- content dynamically created from js.php -->
								</div>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 185, $form_id );
							?>

							<li class="copy_values_option field_setting">
								<input type="checkbox" id="field_enable_copy_values_option"/>
								<label for="field_enable_copy_values_option" class="inline">
									<?php esc_html_e( 'Display option to use the values submitted in different field', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_enable_copy_values_option' ); ?>
								</label>

								<div id="field_copy_values_disabled" style="display:none;padding-top: 10px;">
									<span class="instruction" style="margin-left:0">
									    <?php esc_html_e( 'To activate this option, please add a field to be used as the source.', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_enable_copy_values_disabled' ); ?>
									</span>
								</div>
								<div id="field_copy_values_container" style="display:none;" class="gfield_sub_setting">
									<label for="field_copy_values_option_label">
										<?php esc_html_e( 'Option Label', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_copy_values_option_label' ); ?>
									</label>
									<input id="field_copy_values_option_label" type="text"/>
									<label for="field_copy_values_option_field" style="padding-top: 10px;">
										<?php esc_html_e( 'Source Field', 'gravityforms' ); ?>
										<?php gform_tooltip( 'form_field_copy_values_option_field' ); ?>
									</label>
									<select id="field_copy_values_option_field">
										<!-- content dynamically created  -->
									</select>

									<div style="padding-top: 10px;">
										<input type="checkbox" id="field_copy_values_option_default"/>
										<label for="field_copy_values_option_default" class="inline">
											<?php esc_html_e( 'Activated by default', 'gravityforms' ); ?>
											<?php gform_tooltip( 'form_field_copy_values_option_default' ); ?>
										</label>
									</div>
								</div>
							</li>

							<?php
							do_action( 'gform_field_advanced_settings', 200, $form_id );
							do_action( 'gform_field_advanced_settings', 225, $form_id );
							do_action( 'gform_field_advanced_settings', 250, $form_id );
							?>
							<li class="captcha_language_setting field_setting">
								<label for="field_captcha_language" class="section_label">
									<?php esc_html_e( 'Language', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_recaptcha_language' ); ?>
								</label>

								<select id="field_captcha_language" onchange="SetFieldProperty('captchaLanguage', this.value);">
									<option value="ar"><?php esc_html_e( 'Arabic', 'gravityforms' ); ?></option>
									<option value="af"><?php esc_html_e( 'Afrikaans', 'gravityforms' ); ?></option>
									<option value="am"><?php esc_html_e( 'Amharic', 'gravityforms' ); ?></option>
									<option value="hy"><?php esc_html_e( 'Armenian', 'gravityforms' ); ?></option>
									<option value="az"><?php esc_html_e( 'Azerbaijani', 'gravityforms' ); ?></option>
									<option value="eu"><?php esc_html_e( 'Basque', 'gravityforms' ); ?></option>
									<option value="bn"><?php esc_html_e( 'Bengali', 'gravityforms' ); ?></option>
									<option value="bg"><?php esc_html_e( 'Bulgarian', 'gravityforms' ); ?></option>
									<option value="ca"><?php esc_html_e( 'Catalan', 'gravityforms' ); ?></option>
									<option value="zh-HK"><?php esc_html_e( 'Chinese (Hong Kong)', 'gravityforms' ); ?></option>
									<option value="zh-CN"><?php esc_html_e( 'Chinese (Simplified)', 'gravityforms' ); ?></option>
									<option value="zh-TW"><?php esc_html_e( 'Chinese (Traditional)', 'gravityforms' ); ?></option>
									<option value="hr"><?php esc_html_e( 'Croatian', 'gravityforms' ); ?></option>
									<option value="cs"><?php esc_html_e( 'Czech', 'gravityforms' ); ?></option>
									<option value="da"><?php esc_html_e( 'Danish', 'gravityforms' ); ?></option>
									<option value="nl"><?php esc_html_e( 'Dutch', 'gravityforms' ); ?></option>
									<option value="en-GB"><?php esc_html_e( 'English (UK)', 'gravityforms' ); ?></option>
									<option value="en"><?php esc_html_e( 'English (US)', 'gravityforms' ); ?></option>
									<option value="et"><?php esc_html_e( 'Estonian', 'gravityforms' ); ?></option>
									<option value="fil"><?php esc_html_e( 'Filipino', 'gravityforms' ); ?></option>
									<option value="fi"><?php esc_html_e( 'Finnish', 'gravityforms' ); ?></option>
									<option value="fr"><?php esc_html_e( 'French', 'gravityforms' ); ?></option>
									<option value="fr-CA"><?php esc_html_e( 'French (Canadian)', 'gravityforms' ); ?></option>
									<option value="gl"><?php esc_html_e( 'Galician', 'gravityforms' ); ?></option>
									<option value="ka"><?php esc_html_e( 'Georgian', 'gravityforms' ); ?></option>
									<option value="de"><?php esc_html_e( 'German', 'gravityforms' ); ?></option>
									<option value="de-AT"><?php esc_html_e( 'German (Austria)', 'gravityforms' ); ?></option>
									<option value="de-CH"><?php esc_html_e( 'German (Switzerland)', 'gravityforms' ); ?></option>
									<option value="el"><?php esc_html_e( 'Greek', 'gravityforms' ); ?></option>
									<option value="gu"><?php esc_html_e( 'Gujarati', 'gravityforms' ); ?></option>
									<option value="iw"><?php esc_html_e( 'Hebrew', 'gravityforms' ); ?></option>
									<option value="hi"><?php esc_html_e( 'Hindi', 'gravityforms' ); ?></option>
									<option value="hu"><?php esc_html_e( 'Hungarian', 'gravityforms' ); ?></option>
									<option value="is"><?php esc_html_e( 'Icelandic', 'gravityforms' ); ?></option>
									<option value="id"><?php esc_html_e( 'Indonesian', 'gravityforms' ); ?></option>
									<option value="it"><?php esc_html_e( 'Italian', 'gravityforms' ); ?></option>
									<option value="ja"><?php esc_html_e( 'Japanese', 'gravityforms' ); ?></option>
									<option value="kn"><?php esc_html_e( 'Kannada', 'gravityforms' ); ?></option>
									<option value="ko"><?php esc_html_e( 'Korean', 'gravityforms' ); ?></option>
									<option value="lo"><?php esc_html_e( 'Laothian', 'gravityforms' ); ?></option>
									<option value="lv"><?php esc_html_e( 'Latvian', 'gravityforms' ); ?></option>
									<option value="lt"><?php esc_html_e( 'Lithuanian', 'gravityforms' ); ?></option>
									<option value="ms"><?php esc_html_e( 'Malay', 'gravityforms' ); ?></option>
									<option value="ml"><?php esc_html_e( 'Malayalam', 'gravityforms' ); ?></option>
									<option value="mr"><?php esc_html_e( 'Marathi', 'gravityforms' ); ?></option>
									<option value="mn"><?php esc_html_e( 'Mongolian', 'gravityforms' ); ?></option>
									<option value="no"><?php esc_html_e( 'Norwegian', 'gravityforms' ); ?></option>
									<option value="fa"><?php esc_html_e( 'Persian', 'gravityforms' ); ?></option>
									<option value="pl"><?php esc_html_e( 'Polish', 'gravityforms' ); ?></option>
									<option value="pt"><?php esc_html_e( 'Portuguese', 'gravityforms' ); ?></option>
									<option value="pt-BR"><?php esc_html_e( 'Portuguese (Brazil)', 'gravityforms' ); ?></option>
									<option value="pt-PT"><?php esc_html_e( 'Portuguese (Portugal)', 'gravityforms' ); ?></option>
									<option value="ro"><?php esc_html_e( 'Romanian', 'gravityforms' ); ?></option>
									<option value="ru"><?php esc_html_e( 'Russian', 'gravityforms' ); ?></option>
									<option value="sr"><?php esc_html_e( 'Serbian', 'gravityforms' ); ?></option>
									<option value="si"><?php esc_html_e( 'Sinhalese', 'gravityforms' ); ?></option>
									<option value="sk"><?php esc_html_e( 'Slovak', 'gravityforms' ); ?></option>
									<option value="sl"><?php esc_html_e( 'Slovenian', 'gravityforms' ); ?></option>
									<option value="es"><?php esc_html_e( 'Spanish', 'gravityforms' ); ?></option>
									<option value="es-419"><?php esc_html_e( 'Spanish (Latin America)', 'gravityforms' ); ?></option>
									<option value="sw"><?php esc_html_e( 'Swahili', 'gravityforms' ); ?></option>
									<option value="sv"><?php esc_html_e( 'Swedish', 'gravityforms' ); ?></option>
									<option value="ta"><?php esc_html_e( 'Tamil', 'gravityforms' ); ?></option>
									<option value="te"><?php esc_html_e( 'Telugu', 'gravityforms' ); ?></option>
									<option value="th"><?php esc_html_e( 'Thai', 'gravityforms' ); ?></option>
									<option value="tr"><?php esc_html_e( 'Turkish', 'gravityforms' ); ?></option>
									<option value="uk"><?php esc_html_e( 'Ukrainian', 'gravityforms' ); ?></option>
									<option value="ur"><?php esc_html_e( 'Urdu', 'gravityforms' ); ?></option>
									<option value="vi"><?php esc_html_e( 'Vietnamese', 'gravityforms' ); ?></option>
									<option value="zu"><?php esc_html_e( 'Zulu', 'gravityforms' ); ?></option>
								</select>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 300, $form_id );
							do_action( 'gform_field_advanced_settings', 325, $form_id );
							?>
							<li class="add_icon_url_setting field_setting">
								<label for="field_add_icon_url" class="section_label">
									<?php esc_html_e( 'Add Icon URL', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_add_icon_url' ); ?>
								</label>
								<input type="text" id="field_add_icon_url"/>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 337, $form_id );
							?>
							<li class="delete_icon_url_setting field_setting">
								<label for="field_delete_icon_url" class="section_label">
									<?php esc_html_e( 'Delete Icon URL', 'gravityforms' ); ?>
									<?php gform_tooltip( 'form_field_delete_icon_url' ); ?>
								</label>
								<input type="text" id="field_delete_icon_url"/>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 350, $form_id );
							?>
							<li class="password_field_setting field_setting">
								<input type="checkbox" id="field_password" onclick="SetPasswordProperty(this.checked);" onkeypress="SetPasswordProperty(this.checked);"/>
								<label for="field_password" class="inline"><?php esc_html_e( 'Enable Password Input', 'gravityforms' ) ?><?php gform_tooltip( 'form_field_password' ); ?></label>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 375, $form_id );
							?>
							<li class="force_ssl_field_setting field_setting">
								<input type="checkbox" id="field_force_ssl" onclick="SetFieldProperty('forceSSL', this.checked);" onkeypress="SetFieldProperty('forceSSL', this.checked);"/>
								<label for="field_force_ssl" class="inline"><?php esc_html_e( 'Force SSL', 'gravityforms' ) ?><?php gform_tooltip( 'form_field_force_ssl' ); ?></label>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 400, $form_id );
							?>
							<li class="visibility_setting field_setting">
								<label for="visibility" class="section_label"><?php esc_html_e( 'Visibility', 'gravityforms' ); ?><?php gform_tooltip( 'form_field_visibility' ); ?></label>
								<div>
									<?php foreach ( GFCommon::get_visibility_options() as $visibility_option ):
									$slug = sanitize_title_with_dashes( $visibility_option['value'] );
									?>
										<input type="radio" name="field_visibility" id="field_visibility_<?php echo $slug; ?>" value="<?php echo $visibility_option['value']; ?>" onclick="return SetFieldVisibility( this.value );" onkeypress="return SetFieldVisibility( this.value );"/>
										<label for="field_visibility_<?php echo $slug; ?>" class="inline"><?php echo esc_html( $visibility_option['label'] ); ?></label>
									<?php endforeach; ?>
								</div>
								<br class="clear"/>
							</li>

							<?php
							do_action( 'gform_field_advanced_settings', 425, $form_id );
							?>
							<li class="rich_text_editor_setting field_setting">
								<input type="checkbox" id="field_rich_text_editor" onclick="ToggleRichTextEditor( this.checked );"/>
								<label for="field_rich_text_editor" class="inline"><?php _e( 'Use the Rich Text Editor', 'gravityforms' ) ?><?php gform_tooltip( 'form_field_rich_text_editor' ); ?></label>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', 450, $form_id );
							?>
							<li class="prepopulate_field_setting field_setting">
								<input type="checkbox" id="field_prepopulate" onclick="SetFieldProperty('allowsPrepopulate', this.checked); ToggleInputName()" onkeypress="SetFieldProperty('allowsPrepopulate', this.checked); ToggleInputName()"/>
								<label for="field_prepopulate" class="inline"><?php esc_html_e( 'Allow field to be populated dynamically', 'gravityforms' ) ?></label>
								<br/>
								<div id="field_input_name_container" style="display:none; padding-top:10px;">
									<!-- content dynamically created from js.php -->
								</div>
							</li>
							<?php
							do_action( 'gform_field_advanced_settings', - 1, $form_id );
							?>
						</ul>

						<?php
						/**
						 * Filters custom setting tabs.
						 *
						 * @param array $field_setting_tabs Custom tabs array.
						 * @param array $from The current form object.
						 *
						 * @since 2.5
						 *
						 */
						$field_setting_tabs = gf_apply_filters( array( 'gform_field_settings_tabs', $form_id ), array(), $form );
						foreach ( $field_setting_tabs as $tab ) {
						$tab_id = empty( $tab['id'] ) ? '' : $tab['id'];
						$tab_title = empty( $tab['title'] ) ? '' : $tab['title'];
						$tab_toggle_classes = empty( $tab['toggle_classes'] ) ? array() : $tab['toggle_classes'];
						$tab_body_classes = empty( $tab['body_classes'] ) ? array() : $tab['body_classes'];
						?>
						<button tabindex="0" id="<?php echo $tab_id ?>_tab_toggle" class="panel-block-tabs__toggle <?php echo implode( ' ', $tab_toggle_classes ); ?>">
							<?php echo $tab_title; ?>
						</button>
						<ul id="<?php echo $tab_id; ?>_tab" class="panel-block-tabs__body panel-block-tabs__body--settings <?php echo implode( ' ', $tab_body_classes ); ?>">
							<?php
							/**
							 * Insert field settings for custom settings panel.
							 *
							 * @param array $form The current form object.
							 * @param string $tab_id The current tab ID.
							 *
							 * @since 2.5
							 *
							 */
							gf_do_action( array( 'gform_field_settings_tab_content', $tab_id, $form_id ), $form, $tab_id );
							?>
						</ul>
						<?php
						}
						?>
						<div class="conditional_logic_wrapper">
							<?php
							do_action( 'gform_field_advanced_settings', 500, $form_id );
							?>
							<div class="conditional_logic_field_setting field_setting">
								<input type="checkbox" id="field_conditional_logic" onclick="SetFieldProperty('conditionalLogic', this.checked ? new ConditionalLogic() : null); ToggleConditionalLogic( false, 'field' );" onkeypress="SetFieldProperty('conditionalLogic', this.checked ? new ConditionalLogic() : null); ToggleConditionalLogic( false, 'field' );"/>
								<label for="field_conditional_logic" class="inline"><?php esc_html_e( 'Enable Conditional Logic', 'gravityforms' ) ?><?php gform_tooltip( 'form_field_conditional_logic' ); ?></label>
								<br/>
								<div id="field_conditional_logic_container" style="display:none; padding-top:10px;">
									<!-- content dynamically created from js.php -->
								</div>
							</div>

							<?php
							do_action( 'gform_field_advanced_settings', 525, $form_id );
							?>
							<div class="conditional_logic_page_setting field_setting">
								<input type="checkbox" id="page_conditional_logic" onclick="SetFieldProperty('conditionalLogic', this.checked ? new ConditionalLogic() : null); ToggleConditionalLogic( false, 'page' );" onkeypress="SetFieldProperty('conditionalLogic', this.checked ? new ConditionalLogic() : null); ToggleConditionalLogic( false, 'page' );"/>
								<label for="page_conditional_logic" class="inline"><?php esc_html_e( 'Enable Page Conditional Logic', 'gravityforms' ) ?><?php gform_tooltip( 'form_page_conditional_logic' ); ?></label>
								<br/>
								<div id="page_conditional_logic_container" style="display:none; padding-top:10px;">
									<!-- content dynamically created from js.php -->
								</div>
							</div>

							<?php
							do_action( 'gform_field_advanced_settings', 550, $form_id );
							?>
							<div class="conditional_logic_submit_setting field_setting">
								<input type="checkbox" id="submit_conditional_logic" onclick="SetSubmitConditionalLogic(this.checked); ToggleConditionalLogic( false, 'button' );" onkeypress="SetSubmitConditionalLogic(this.checked); ToggleConditionalLogic( false, 'button' );"/>
								<label for="submit_conditional_logic" class="inline"><?php esc_html_e( 'Enable Submit Button Conditional Logic', 'gravityforms' ) ?><?php gform_tooltip( 'form_submit_conditional_logic' ); ?></label>
								<br/>
								<div id="submit_conditional_logic_container" style="display:none; padding-top:10px;">
									<!-- content dynamically created from js.php -->
								</div>
							</div>
							<div class="conditional_logic_nextbutton_setting field_setting">
								<input type="checkbox" id="next_button_conditional_logic" onclick="SetNextButtonConditionalLogic(this.checked); ToggleConditionalLogic( false, 'next_button' );" onkeypress="SetNextButtonConditionalLogic(this.checked); ToggleConditionalLogic( false, 'next_button' );"/>
								<label for="next_button_conditional_logic" class="inline"><?php esc_html_e( 'Enable Next Button Conditional Logic', 'gravityforms' ) ?><?php gform_tooltip( 'form_nextbutton_conditional_logic' ); ?></label>
								<br/>
								<div id="next_button_conditional_logic_container" style="display:none; padding-top:10px;">
									<!-- content dynamically created from js.php -->
								</div>
							</div>
						</div>
					</div>
					<div class="conditional_logic_flyout_container" id="conditional_logic_flyout_container"></div>
					<div class="conditional_logic_flyout_container" id="conditional_logic_next_button_flyout_container"></div>
					<div class="conditional_logic_flyout_container" id="conditional_logic_submit_flyout_container"></div>
				</div>
				<?php
				foreach ( $setting_panels as $panel ) {
				if ( empty( $panel['id'] ) ) {
					continue;
				}

				$panel_body_classes = ! empty( $panel['body_classes'] ) ? $panel['body_classes'] : array();
				?>
				<div class="sidebar__panel <?php echo is_array( $panel_body_classes ) ? implode( ' ', $panel_body_classes ) : $panel_body_classes ?>" id="<?php echo $panel['id']; ?>">
					<?php
					/**
					 * Insert content into the custom sidebar panel.
					 *
					 * @param array $panel custom panel array.
					 * @param array $form The current form object.
					 *
					 * @since 2.5
					 *
					 */
					gf_do_action( array( 'gform_editor_sidebar_panel_content', $panel['id'], $form_id ), $panel, $form );
					?>
				</div>
				<?php
				}
				?>
			</aside>

		</div>


		</div>
		<!-- // including form setting hooks as a temporary fix to prevent issues where users using the "gform_before_update" hook are expecting
            form settings to be included on the form editor page -->
		<div>
			<!--form settings-->
			<?php do_action( 'gform_properties_settings', 100, $form_id ); ?>
			<?php do_action( 'gform_properties_settings', 200, $form_id ); ?>
			<?php do_action( 'gform_properties_settings', 300, $form_id ); ?>
			<?php do_action( 'gform_properties_settings', 400, $form_id ); ?>
			<?php do_action( 'gform_properties_settings', 500, $form_id ); ?>

			<!--advanced settings-->
			<?php do_action( 'gform_advanced_settings', 100, $form_id ); ?>
			<?php do_action( 'gform_advanced_settings', 200, $form_id ); ?>
			<?php do_action( 'gform_advanced_settings', 300, $form_id ); ?>
			<?php do_action( 'gform_advanced_settings', 400, $form_id ); ?>
			<?php do_action( 'gform_advanced_settings', 500, $form_id ); ?>
			<?php do_action( 'gform_advanced_settings', 600, $form_id ); ?>
			<?php do_action( 'gform_advanced_settings', 700, $form_id ); ?>
			<?php do_action( 'gform_advanced_settings', 800, $form_id ); ?>
		</div>

		<?php
		self::inline_scripts( $form );

		require_once( GFCommon::get_base_path() . '/js.php' );

	}

	/**
	 * Prepare form field groups.
	 *
	 * @since  2.0.7.7
	 * @access public
	 *
	 * @return array
	 */
	public static function get_field_groups() {
		// Set initial field groups.
		$field_groups = array(
			'standard_fields' => array(
				'name'          => 'standard_fields',
				'label'         => __( 'Standard Fields', 'gravityforms' ),
				'tooltip_class' => 'tooltip_bottomleft',
				'fields' => array(),
				'fields'        => array(
					array( 'data-type' => 'text',        'value' => GFCommon::get_field_type_title( 'text' ) ),
					array( 'data-type' => 'textarea',    'value' => GFCommon::get_field_type_title( 'textarea' ) ),
					array( 'data-type' => 'select',      'value' => GFCommon::get_field_type_title( 'select' ) ),
					array( 'data-type' => 'number',      'value' => GFCommon::get_field_type_title( 'number' ) ),
					array( 'data-type' => 'checkbox',    'value' => GFCommon::get_field_type_title( 'checkbox' ) ),
					array( 'data-type' => 'radio',       'value' => GFCommon::get_field_type_title( 'radio' ) ),
					array( 'data-type' => 'hidden',      'value' => GFCommon::get_field_type_title( 'hidden' ) ),
					array( 'data-type' => 'html',        'value' => GFCommon::get_field_type_title( 'html' ) ),
					array( 'data-type' => 'section',     'value' => GFCommon::get_field_type_title( 'section' ) ),
					array( 'data-type' => 'page',        'value' => GFCommon::get_field_type_title( 'page' ) ),
				),
			),
			'advanced_fields' => array(
				'name'   => 'advanced_fields',
				'label'  => __( 'Advanced Fields', 'gravityforms' ),
				'fields' => array(
					array( 'data-type' => 'name',       'value' => GFCommon::get_field_type_title( 'name' ) ),
					array( 'data-type' => 'date',       'value' => GFCommon::get_field_type_title( 'date' ) ),
					array( 'data-type' => 'time',       'value' => GFCommon::get_field_type_title( 'time' ) ),
					array( 'data-type' => 'phone',      'value' => GFCommon::get_field_type_title( 'phone' ) ),
					array( 'data-type' => 'address',    'value' => GFCommon::get_field_type_title( 'address' ) ),
					array( 'data-type' => 'website',    'value' => GFCommon::get_field_type_title( 'website' ) ),
					array( 'data-type' => 'email',      'value' => GFCommon::get_field_type_title( 'email' ) ),
					array( 'data-type' => 'fileupload', 'value' => GFCommon::get_field_type_title( 'fileupload' ) ),
					array( 'data-type' => 'captcha',    'value' => GFCommon::get_field_type_title( 'captcha' ) ),
					array( 'data-type' => 'list',       'value' => GFCommon::get_field_type_title( 'list' ) ),
					array( 'data-type' => 'multiselect', 'value' => GFCommon::get_field_type_title( 'multiselect' ) ),
				),
			),
			'post_fields'     => array(
				'name'   => 'post_fields',
				'label'  => __( 'Post Fields', 'gravityforms' ),
				'fields' => array(
					array( 'data-type' => 'post_title',        'value' => GFCommon::get_field_type_title( 'post_title' ) ),
					array( 'data-type' => 'post_content',      'value' => GFCommon::get_field_type_title( 'post_content' ) ),
					array( 'data-type' => 'post_excerpt',      'value' => GFCommon::get_field_type_title( 'post_excerpt' ) ),
					array( 'data-type' => 'post_tags',         'value' => GFCommon::get_field_type_title( 'post_tags' ) ),
					array( 'data-type' => 'post_category',     'value' => GFCommon::get_field_type_title( 'post_category' ) ),
					array( 'data-type' => 'post_image',        'value' => GFCommon::get_field_type_title( 'post_image' ) ),
					array( 'data-type' => 'post_custom_field', 'value' => GFCommon::get_field_type_title( 'post_custom_field' ) ),
				),
			),
			'pricing_fields'   => array(
				'name'   => 'pricing_fields',
				'label'  => __( 'Pricing Fields', 'gravityforms' ),
				'fields' => array(
					array( 'data-type' => 'product',  'value' => GFCommon::get_field_type_title( 'product' ) ),
					array( 'data-type' => 'quantity', 'value' => GFCommon::get_field_type_title( 'quantity' ) ),
					array( 'data-type' => 'option',   'value' => GFCommon::get_field_type_title( 'option' ) ),
					array( 'data-type' => 'shipping', 'value' => GFCommon::get_field_type_title( 'shipping' ) ),
					array( 'data-type' => 'total',    'value' => GFCommon::get_field_type_title( 'total' ) ),
				),
			),
		);

		// If enabled insert the password field between the email and fileupload fields.
		if ( apply_filters( 'gform_enable_password_field', false ) ) {
			$password = array(
				'data-type' => 'password',
				'value'     => GFCommon::get_field_type_title( 'password' )
			);

			array_splice( $field_groups['advanced_fields']['fields'], 7, 0, array( $password ) );
		}

		// Add credit card field, if enabled.
		if ( apply_filters( 'gform_enable_credit_card_field', false ) ) {
			$field_groups['pricing_fields']['fields'][] = array(
				'data-type' => 'creditcard',
				'value'     => GFCommon::get_field_type_title( 'creditcard' )
			);
		}

		/**
		 * Modify the field groups before fields are added.
		 *
		 * @since 2.2.6
		 *
		 * @param array $field_groups The field groups, including group name, label and fields.
		 */
		$field_groups = apply_filters( 'gform_field_groups_form_editor', $field_groups );

		// Remove array keys from field groups array.
		$field_groups = array_values( $field_groups );

		// Add buttons to fields.
		foreach ( GF_Fields::get_all() as $gf_field ) {
			$field_groups = $gf_field->add_button( $field_groups );
		}

		/**
		 * Add/edit/remove "Add Field" buttons from the form editor's floating toolbox.
		 *
		 * @param array $field_groups The field groups, including group name, label and fields.
		 */
		return apply_filters( 'gform_add_field_buttons', $field_groups );

	}

	public static function color_picker( $field_name, $callback ) {
		?>
		<table cellpadding="0" cellspacing="0">
			<tr>
				<td>
					<input type='text' class="iColorPicker" name='<?php echo esc_attr( $field_name ); ?>' onchange='SetColorPickerColor(this.name, this.value, "<?php echo $callback ?>");' id='<?php echo esc_attr( $field_name ) ?>' />
				</td>
				<td style="padding-right:5px; padding-left:5px;">
					<img style="top:3px; cursor:pointer; border:1px solid #dfdfdf;" id="chip_<?php echo esc_attr( $field_name ); ?>" valign="bottom" height="22" width="22" src="<?php echo GFCommon::get_base_url() ?>/images/blankspace.png" />
				</td>
				<td>
					<img style="cursor:pointer;" valign="bottom" id="chooser_<?php echo esc_attr( $field_name ); ?>" src="<?php echo GFCommon::get_base_url() ?>/images/color.png" />
				</td>
			</tr>
		</table>
		<script type="text/javascript">
			jQuery( "#chooser_<?php echo esc_js( $field_name ); ?>" ).click( function ( e ) {
				var rect = e.currentTarget.getBoundingClientRect();
				var top  = rect.top + 176;
				var side = rect.left - 260;

				iColorShow( side, top, '<?php echo esc_js( $field_name ); ?>', "<?php echo esc_js( $callback ); ?>" ) ;
			});
			jQuery("#chip_<?php echo esc_js( $field_name ); ?>").click(function (e) {
				var rect = e.currentTarget.getBoundingClientRect();
				var top  = rect.top + 176;
				var side = rect.left - 260;

				iColorShow( side, top, '<?php echo esc_js( $field_name ); ?>', "<?php echo esc_js( $callback ); ?>" );
			});
		</script>
		<?php
	}

	/**
	 * Generates add field buttons markup.
	 *
	 * @since unknown
	 * @since 2.5     Added data-icon and data-description to button markup.
	 *
	 * @param array $buttons Field buttons array.
	 */
	private static function display_buttons( $buttons ) {
		foreach ( $buttons as $button ) {
			$button['data-icon']        = empty( $button['data-icon'] ) ? 'gform-icon--cog' : $button['data-icon'];
			$button['data-description'] = empty( $button['data-description'] ) ? sprintf( esc_attr__( 'Add a %s field to your form.', 'gravityforms' ), $button['value'] ) : $button['data-description'];
			?>
			<li>
				<button title="<?php echo esc_attr( $button['data-description'] ); ?>"
					<?php
					foreach ( array_keys( $button ) as $attr ) {
						echo $attr . '="' . esc_attr( $button[ $attr ] ) . '" ';
					}
					?>
				>
				<div class="button-icon"><?php echo GFCommon::get_icon_markup( array( 'icon' => rgar( $button, 'data-icon' ) ) ); ?></div>
				<div class="button-text"><?php echo esc_html( $button['value'] ); ?></div>
				</button>
			</li>
			<?php
		}
	}

	//Hierarchical category functions copied from WordPress core and modified.
	private static function _cat_rows( $categories, &$count, &$output, $parent = 0, $level = 0, $page = 1, $per_page = 9999999 ) {
		if ( empty( $categories ) ) {
			$args = array( 'hide_empty' => 0 );
			if ( ! empty( $_POST['search'] ) )
				$args['search'] = $_POST['search'];
			$categories = get_categories( $args );
		}

		if ( ! $categories )
			return false;

		$children = self::_get_term_hierarchy( 'category' );

		$start = ( $page - 1 ) * $per_page;
		$end   = $start + $per_page;
		$i     = - 1;
		foreach ( $categories as $category ) {
			if ( $count >= $end )
				break;

			$i ++;

			if ( $category->parent != $parent )
				continue;

			// If the page starts in a subtree, print the parents.
			if ( $count == $start && $category->parent > 0 ) {
				$my_parents = array();
				while ( $my_parent ) {
					$my_parent    = get_category( $my_parent );
					$my_parents[] = $my_parent;
					if ( ! $my_parent->parent )
						break;
					$my_parent = $my_parent->parent;
				}
				$num_parents = count( $my_parents );
				while ( $my_parent = array_pop( $my_parents ) ) {
					self::_cat_row( $my_parent, $level - $num_parents, $output );
					$num_parents --;
				}
			}

			if ( $count >= $start )
				self::_cat_row( $category, $level, $output );

			//unset($categories[ $i ]); // Prune the working set
			$count ++;

			if ( isset( $children[ $category->term_id ] ) )
				self::_cat_rows( $categories, $count, $output, $category->term_id, $level + 1, $page, $per_page );

		}
	}

	private static function _cat_row( $category, $level, &$output, $name_override = false ) {
		static $row_class = '';

		$cat = get_category( $category, OBJECT, 'display' );

		$default_cat_id = (int) get_option( 'default_category' );
		$pad            = str_repeat( '&#8212; ', $level );
		$name           = ( $name_override ? $name_override : $pad . ' ' . $cat->name );

		$cat->count = number_format_i18n( $cat->count );

		$output .= "
        <tr class='author-self status-inherit' valign='top'>
            <td scope='row' class='check-column'><input id='" . esc_attr( $cat->name ) . "' type='checkbox' class='gfield_category_checkbox' value='" . esc_attr( $cat->term_id ) . "' name='" . esc_attr( $cat->name ) . "' onclick='SetSelectedCategories();' onkeypress='SetSelectedCategories();' /><label for='" . esc_attr( $cat->name ) . "'>$name</label></td>
        </tr>";
	}

	private static function _get_term_hierarchy( $taxonomy ) {
		if ( ! is_taxonomy_hierarchical( $taxonomy ) )
			return array();
		$children = get_option( "{$taxonomy}_children" );
		if ( is_array( $children ) )
			return $children;

		$children = array();
		$terms    = get_terms( $taxonomy, 'get=all' );
		foreach ( $terms as $term ) {
			if ( $term->parent > 0 )
				$children[ $term->parent ][] = $term->term_id;
		}
		update_option( "{$taxonomy}_children", $children );

		return $children;
	}

	private static function insert_variable_prepopulate( $element_id, $callback = '' ) {
		?>
	<select id="<?php echo esc_attr( $element_id ); ?>_variable_select" onchange="InsertVariable('<?php echo esc_js( $element_id ) ?>', '<?php echo esc_js( $callback ); ?>'); ">
		<option value=''><?php esc_html_e( 'Insert Merge Tag', 'gravityforms' ); ?></option>
		<option value='{ip}'><?php esc_html_e( 'User IP Address', 'gravityforms' ); ?></option>
		<option value='{date_mdy}'><?php esc_html_e( 'Date', 'gravityforms' ); ?> (mm/dd/yyyy)</option>
		<option value='{date_dmy}'><?php esc_html_e( 'Date', 'gravityforms' ); ?> (dd/mm/yyyy)</option>
		<option value='{embed_post:ID}'><?php esc_html_e( 'Embed Post/Page Id', 'gravityforms' ); ?></option>
		<option value='{embed_post:post_title}'><?php esc_html_e( 'Embed Post/Page Title', 'gravityforms' ); ?></option>
		<option value='{embed_url}'><?php esc_html_e( 'Embed URL', 'gravityforms' ); ?></option>
		<option value='{user_agent}'><?php esc_html_e( 'HTTP User Agent', 'gravityforms' ); ?></option>
		<option value='{referer}'><?php esc_html_e( 'HTTP Referer URL', 'gravityforms' ); ?></option>
		<option value='{user:display_name}'><?php esc_html_e( 'User Display Name', 'gravityforms' ); ?></option>
		<option value='{user:user_email}'><?php esc_html_e( 'User Email', 'gravityforms' ); ?></option>
		<option value='{user:user_login}'><?php esc_html_e( 'User Login', 'gravityforms' ); ?></option>
	<?php
	}

	//Ajax calls
	public static function add_field() {
		check_ajax_referer( 'rg_add_field', 'rg_add_field' );

		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			wp_die( -1, 403 );
		}

		$field_json = stripslashes_deep( $_POST['field'] );

		$field_properties = GFCommon::json_decode( $field_json, true );

		$field = GF_Fields::create( $field_properties );
		$field->sanitize_settings();

		$index = rgpost( 'index' );

		if ( $index != 'undefined' ) {
			$index = absint( $index );
		}

		require_once( GFCommon::get_base_path() . '/form_display.php' );

		$form_id = absint( rgpost( 'form_id' ) );
		$form    = GFFormsModel::get_form_meta( $form_id );

		$field_html      = GFFormDisplay::get_field( $field, '', true, $form );
		$field_html_json = json_encode( $field_html );

		$field_json = json_encode( $field );

		die( "EndAddField($field_json, " . $field_html_json . ", $index);" );
	}

	public static function duplicate_field() {
		check_ajax_referer( 'rg_duplicate_field', 'rg_duplicate_field' );
		$source_field_id  = absint( rgpost( 'source_field_id' ) );
		$field_json       = stripslashes_deep( $_POST['field'] );
		$field_properties = GFCommon::json_decode( $field_json, true );
		$field            = GF_Fields::create( $field_properties );
		$form_id          = absint( rgpost( 'form_id' ) );
		$form             = GFFormsModel::get_form_meta( $form_id );

		require_once( GFCommon::get_base_path() . '/form_display.php' );
		$field_html            = GFFormDisplay::get_field( $field, '', true, $form );
		$args['field']         = $field;
		$args['sourceFieldId'] = $source_field_id;
		$args['fieldString']   = $field_html;
		$args_json             = json_encode( $args );
		die( $args_json );
	}

	public static function change_input_type() {
		check_ajax_referer( 'rg_change_input_type', 'rg_change_input_type' );

		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
			wp_die( -1, 403 );
		}

		$field_json       = stripslashes_deep( $_POST['field'] );
		$field_properties = GFCommon::json_decode( $field_json, true );
		$field            = GF_Fields::create( $field_properties );
		$id               = absint( $field->id );
		$type             = $field->inputType;
		$form_id          = absint( rgpost( 'form_id' ) );
		$form             = GFFormsModel::get_form_meta( $form_id );

		require_once( GFCommon::get_base_path() . '/form_display.php' );
		$field_content       = GFFormDisplay::get_field_content( $field, '', true, $form_id, $form );
		$args['id']          = $id;
		$args['type']        = $type;
		$args['fieldString'] = $field_content;
		$args_json           = json_encode( $args );
		die( "EndChangeInputType($args_json);" );
	}

	public static function refresh_field_preview() {
		check_ajax_referer( 'rg_refresh_field_preview', 'rg_refresh_field_preview' );
		$field_json       = stripslashes_deep( $_POST['field'] );
		$field_properties = GFCommon::json_decode( $field_json, true );
		$field            = GF_Fields::create( $field_properties );
		$field->sanitize_settings();
		$form_id          = absint( $_POST['formId'] );
		$form             = GFFormsModel::get_form_meta( $form_id );
		$form             = GFFormsModel::maybe_sanitize_form_settings( $form );

		require_once( GFCommon::get_base_path() . '/form_display.php' );
		$field_content       = GFFormDisplay::get_field_content( $field, '', true, $form_id, $form );
		$args['fieldString'] = $field_content;
		$args['fieldId']     = absint( $field->id );
		$args_json           = json_encode( $args );
		die( $args_json );
	}

	public static function delete_custom_choice() {
		check_ajax_referer( 'gf_delete_custom_choice', 'gf_delete_custom_choice' );
		RGFormsModel::delete_custom_choice( rgpost( 'name' ) );
		exit();
	}

	public static function save_custom_choice() {
		check_ajax_referer( 'gf_save_custom_choice', 'gf_save_custom_choice' );
		RGFormsModel::save_custom_choice( rgpost( 'previous_name' ), rgpost( 'new_name' ), GFCommon::json_decode( rgpost( 'choices' ) ) );
		exit();
	}

	/**
	 * Saves form meta. Note the special requirements for the meta string.
	 *
	 * @since unknown
	 *
	 * @since 2.6 Use GF_Form_CRUD_Handler To save the form.
	 *
	 * @param int    $id
	 * @param string $form_json A valid JSON string. The JSON is manipulated before decoding and is designed to work together with jQuery.toJSON() rather than json_encode. Avoid using json_encode as it will convert unicode characters into their respective entities with slashes. These slashes get stripped so unicode characters won't survive intact.
	 *
	 * @return array
	 */
	public static function save_form_info( $id, $form_json ) {
		$form_crud_handler = GFForms::get_service_container()->get( GF_Save_Form_Service_Provider::GF_FORM_CRUD_HANDLER );
		$result            = $form_crud_handler->save( $id, $form_json );

		// For backwards compatibility, status used to have the value of the form id if update was successful,
		// and the negative value of the form id if it was a successful insert.
		if ( rgar( $result, 'status' ) === GF_Form_CRUD_Handler::STATUS_SUCCESS ) {
			$saved_form_id    = rgars( $result, 'meta/id', 0 );
			$saved_form_id    = rgar( $result, 'is_new' ) ? $saved_form_id * -1 : $saved_form_id;
			$result['status'] = $saved_form_id;
		}

		$save_form_helper = GFForms::get_service_container()->get( GF_Save_Form_Service_Provider::GF_SAVE_FROM_HELPER );
		if ( $save_form_helper->is_ajax_save_action() === false ) {

			foreach ( $result['actions_markup'] as $action_name => $action_markup ) {
				if  ( ! empty ( $action_markup ) ) {
					echo $action_markup;
				}
			}

			unset( $result['actions_markup'] );
			unset( $result['is_new'] );
		}

		return $result;
	}

	public static function save_form() {

		check_ajax_referer( 'rg_save_form', 'rg_save_form' );
		$id        = absint( $_POST['id'] );
		$form_json = absint( $_POST['form'] );

		$result = self::save_form_info( $id, $form_json );

		switch ( rgar( $result, 'status' ) ) {
			case 'invalid_json' :
				die( 'EndUpdateForm(0);' );
				break;

			case 'duplicate_title' :
				die( 'DuplicateTitleMessage();' );
				break;

			default :
				$form_id = absint( $result['status'] );
				if ( $form_id < 0 ) {
					die( 'EndInsertForm(' . $form_id . ');' );
				} else {
					die( "EndUpdateForm({$form_id});" );
				}

				break;

		}
	}

	public static function get_post_category_values() {
		$has_input_name = strtolower( rgpost( 'inputName' ) ) !== 'false';

		$id       = ! $has_input_name ? rgpost( 'objectType' ) . '_rule_value_' . rgpost( 'ruleIndex' ) : rgpost( 'inputName' );
		$selected = rgempty( 'selectedValue' ) ? 0 : rgpost( 'selectedValue' );

		$dropdown = wp_dropdown_categories( array( 'class' => 'gfield_rule_select gfield_rule_value_dropdown gfield_category_dropdown', 'orderby' => 'name', 'id' => $id, 'name' => $id, 'selected' => $selected, 'hierarchical' => true, 'hide_empty' => 0, 'echo' => false ) );
		die( $dropdown );
	}

	public static function inline_scripts( $echo = true ) {
		$script_str = '';
		$conditional_logic_fields = array();
		$field_settings = array();
		foreach ( GF_Fields::get_all() as $gf_field ) {
			$settings_arr = $gf_field->get_form_editor_field_settings();
			if ( ! is_array( $settings_arr ) || empty( $settings_arr ) ) {
				continue;
			}

			$settings = join( ', .', $settings_arr );
			$settings = '.' . $settings;
			$field_settings[ $gf_field->type ] = $settings;

			if ( $gf_field->is_conditional_logic_supported() ) {
				$conditional_logic_fields[] = $gf_field->type;
			}

			$field_script = $gf_field->get_form_editor_inline_script_on_page_render();
			if ( ! empty( $field_script ) ){
				$script_str .= $field_script . PHP_EOL;
			}
		}

		$script_str .= sprintf( 'fieldSettings = %s;', json_encode( $field_settings ) ) . PHP_EOL;

		$script_str .= sprintf( 'function GetConditionalLogicFields(){return %s;}', json_encode( $conditional_logic_fields ) ) . PHP_EOL;


		if ( ! empty( $script_str ) ) {
			$script_str = sprintf( '<script data-js-reload="gforms-fields-inline-scripts" type="text/javascript">%s</script>', $script_str );
			if ( $echo ) {
				echo $script_str;
			}
		}

		return $script_str;
	}

	/**
	 * Adds the form ID to the beginning of the list of recently opened forms and stores the array for the current user.
	 *
	 * @since 2.0
	 * @param $form_id
	 */
	public static function update_recent_forms( $form_id ) {
		GFFormsModel::update_recent_forms( $form_id );
	}

	/**
	 * Display notices at the top of the form editor.
	 *
	 * @since 2.5
	 *
	 * @param array $form
	 */
	public static function editor_notices( $form ) {
		GFFormDetail::editor_notice_for_legacy_form( $form );
		GFFormDetail::editor_notice_for_ajax_save_failure( $form );
		GFFormDetail::editor_notice_for_deprecated_ready_classes( $form );
	}

	/**
	 * Display editor notice for forms that failed AJAX save.
	 *
	 * @since 2.6.2
	 *
	 * @param array $form
	 */
	public static function editor_notice_for_ajax_save_failure( $form ) {
		if ( ! rgar( $_POST, 'gform_export', false ) ) {
			return '';
		}

		?>
		<div class="gform-alert" data-js="gform-alert" data-gform-alert-cookie="gform-alert-editor-deprecated-classes">
			<span class="gform-alert__icon gform-icon gform-icon--campaign" aria-hidden="true"></span>
			<div class="gform-alert__message-wrap">
				<p class="gform-alert__message" tabindex="0">
					<?php
						echo sprintf(
							// Translators: 1. Opening <a> tag with link to the form export page, 2. closing <a> tag, 3. Opening <a> tag for documentation link, 4. Closing <a> tag.
							esc_html__( 'If you continue to encounter this error, you can %1$sexport your form%2$s to include in your support request. You can also disable AJAX saving for this form. %3$sLearn more%4$s.', 'gravityforms' ),
							'<a target="_blank" href="' . admin_url( 'admin.php?page=gf_export&subview=export_form&export_form_ids=' . rgget( 'id' ) ) . '" rel="noopener noreferrer" class="gform-export-form">',
							'</a>',
							'<a target="_blank" href="https://docs.gravityforms.com/gform_disable_ajax_save/" rel="noopener noreferrer">',
							'</a>'
						);
					?>
				</p>
			</div>
			<button
				class="gform-alert__dismiss"
				aria-label="<?php esc_attr_e( 'Dismiss notification', 'gravityforms' ); ?>"
				title="<?php esc_attr_e( 'Dismiss notification', 'gravityforms' ); ?>"
				data-js="gform-alert-dismiss-trigger"
			>
				<span class="gform-icon gform-icon--delete"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Display editor notice for form with legacy mode enabled.
	 *
	 * @since 2.6
	 *
	 * @param array $form
	 */
	public static function editor_notice_for_legacy_form( $form ) {
		if ( ! GFCommon::is_legacy_markup_enabled( $form['id'] ) ) {
			return '';
		}

		?>
		<div class="gform-alert" data-js="gform-alert" role="status">
		    <span
		        class="gform-alert__icon gform-icon gform-icon--campaign"
		        aria-hidden="true"
		    ></span>
		    <div class="gform-alert__message-wrap">
		        <p class="gform-alert__message"><?php echo esc_html_e( 'This form has legacy markup enabled, which may prevent some new features from functioning.', 'gravityforms' ); ?></p>
			    <a
		            class="gform-alert__cta gform-button gform-button--white gform-button--size-xs"
			        href="https://docs.gravityforms.com/about-legacy-markup"
			        target="_blank"
			        aria-label="<?php echo esc_html_e( 'Learn more about form legacy markup', 'gravityforms' ); ?>"
			    >
			        <?php echo esc_html_e( 'Learn More', 'gravityforms' ); ?>
			    </a>
		    </div>
		</div>
		<?php
	}

	/**
	 * Check whether we need to display a message about deprecated ready classes.
	 *
	 * @since 2.5
	 *
	 * @param array $form
	 */
	public static function need_deprecated_class_message( $form ) {
		if ( GFCommon::is_legacy_markup_enabled( $form['id'] ) ) {
			return false;
		}

		// If user has dismissed the notice, don't show it.
		if ( rgar( $_COOKIE, 'gform-alert-editor-deprecated-classes' ) ) {
			$forms = explode( ',', $_COOKIE['gform-alert-editor-deprecated-classes'] );
			if ( in_array( $form['id'], $forms ) ) {
				return false;
			}
		}

		$deprecated_classes = array(
			'gf_left_half',
			'gf_right_half',
			'gf_left_third',
			'gf_middle_third',
			'gf_right_third',
			'gf_first_quarter',
			'gf_second_quarter',
			'gf_third_quarter',
			'gf_fourth_quarter',
		);

		foreach ( $form['fields'] as $field ) {
			if ( rgar( $field, 'cssClass' ) ) {
				$field_classes = explode( ' ', $field['cssClass'] );
				foreach ( $field_classes as $class ) {
					if ( in_array( $class, $deprecated_classes ) ) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Display editor notice for deprecated ready classes.
	 *
	 * @since 2.6
	 *
	 * @param array $form
	 */
	public static function editor_notice_for_deprecated_ready_classes( $form ) {
		if ( ! GFFormDetail::need_deprecated_class_message( $form ) ) {
			return '';
		}

		?>
		<div class="gform-alert" data-js="gform-alert" data-gform-alert-cookie="gform-alert-editor-deprecated-classes">
			<span class="gform-alert__icon gform-icon gform-icon--campaign" aria-hidden="true"></span>
			<div class="gform-alert__message-wrap">
				<p class="gform-alert__message" tabindex="0">
					<?php echo esc_html_e( 'This form uses deprecated Ready Classes. Adding columns is easier than ever with the new Drag and Drop Layout Editor.', 'gravityforms' ); ?>
				</p>
				<a
					class="gform-alert__cta gform-button gform-button--white gform-button--size-xs"
					href="https://docs.gravityforms.com/working-with-columns/"
					target="_blank"
					title="<?php esc_attr_e( 'Working with Columns in the Form Editor in Gravity Forms 2.5', 'gravityforms' ); ?>"
				>
					<?php esc_html_e( 'Learn More', 'gravityforms' ); ?>
				</a>
			</div>
			<button
				class="gform-alert__dismiss"
				aria-label="<?php esc_html_e( 'Dismiss notification', 'gravityforms' ); ?>"
				title="<?php esc_html_e( 'Dismiss notification', 'gravityforms' ); ?>"
				data-js="gform-alert-dismiss-trigger"
			>
				<span class="gform-icon gform-icon--delete"></span>
			</button>
		</div>
		<?php
	}

	/**
 	 * Adds the submit button field to the form if it's not already there.
 	 *
 	 * @since 2.6.0
	 *
 	 * @param array $form The form object.
 	 * @returns array $form The form object.
	 */
	private static function maybe_add_submit_button( &$form ) {
		// If we already have a submit button, don't add it again.
		if ( empty( $form['fields'] ) ) {
			return $form;
		}
		foreach ( $form['fields'] as $field ) {
			if ( $field instanceof GF_Field_Submit ) {
				return $form;
			}
		}
		$submit_button_props = array(
			'type'  => 'submit',
			'id'    => GFFormsModel::get_next_field_id( $form['fields'] ),
		);

		array_push( $form['fields'], GF_Fields::create( $submit_button_props ) );

		return $form;
	}
}
