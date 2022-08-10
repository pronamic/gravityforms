<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use GFAPI;
use GFCommon;
use GFFormsModel;
use GFNotification;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Notification_Routing extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'notification_routing';





	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {

		// Get notification ID, current form.
		$notification_id = rgempty( 'gform_notification_id' ) ? rgget( 'nid' ) : rgpost( 'gform_notification_id' );
		$form            = $this->settings->get_current_form();
		$form            = gf_apply_filters( array( 'gform_form_notification_page', $form['id'] ), $form, $notification_id );

		// Get routing fields.
		$routing_fields = self::get_routing_fields( $form, '0' );

		// If form does not have routing fields, exit.
		if ( empty( $routing_fields ) ) {

			$html = sprintf(
				'<div id="gform_notification_to_routing_rules"><div class="gold_notice"><p>%s</p></div></div>',
				esc_html__( 'To use notification routing, your form must have a field supported by conditional logic.', 'gravityforms' )
			);

			return $html;

		}

		// Get routing.
		$routing = $this->get_value();
		$routing = empty( $routing ) ? array( array() ) : array_values( $routing );

		// Add hidden input with routing.
		$html = sprintf(
			'<input type="hidden" name="%1$s_%2$s" id="%2$s" value=\'%3$s\' />',
			esc_attr( $this->settings->get_input_name_prefix() ),
			esc_attr( $this->name ),
			esc_attr( json_encode( $routing ) )
		);

		// Open routing container.
		$html .= '<div id="gform_notification_to_routing_rules">';

		// Get routing operators.
		$operators = array(
			'is'          => esc_html__( 'is', 'gravityforms' ),
			'isnot'       => esc_html__( 'is not', 'gravityforms' ),
			'>'           => esc_html__( 'greater than', 'gravityforms' ),
			'<'           => esc_html__( 'less than', 'gravityforms' ),
			'contains'    => esc_html__( 'contains', 'gravityforms' ),
			'starts_with' => esc_html__( 'starts with', 'gravityforms' ),
			'ends_with'   => esc_html__( 'ends with', 'gravityforms' ),
		);

		// Set has invalid rule flag.
		$has_invalid_rule = false;

		// Loop through routing, display inputs.
		foreach ( $routing as $i => $route ) {

			// Determine if rule is valid.
			if ( ! GFNotification::is_valid_notification_email( rgar( $route, 'email' ) ) && $this->settings->is_dependency_met( rgobj( $this, 'dependency' ) ) ) {
				$valid_rule       = false;
				$has_invalid_rule = true;
			} else {
				$valid_rule = true;
			}

			// Prepare email input.
			$email_input = sprintf(
				'<input type="text" id="routing_email_%d" value="%s" class="gfield_routing_email" />',
				$i,
				esc_attr( rgar( $route, 'email' ) )
			);

			// Prepare routing field input.
			$field_input = sprintf(
				'<select id="routing_field_id_%1$d" class="gfield_routing_select" onchange="jQuery( \'#routing_value_%1$d\' ).replaceWith( GetRoutingValues( %1$d, jQuery( this ).val() ) ); SetRouting( %1$d );">%2$s</select>',
				$i,
				self::get_routing_fields( $form, rgar( $route, 'fieldId' ) )
			);

			// Get operators for route.
			$operator_options = '';
			foreach ( $operators as $key => $value ) {
				$operator_options .= sprintf(
					'<option value="%1$s"%3$s>%2$s</option>',
					$key,
					$value,
					selected( rgar( $route, 'operator' ), $key, false )
				);
			}

			// Prepare operator input.
			$operator_input = sprintf( '<select id="routing_operator_%1$d" onchange="SetRouting(%1$d);" class="gform_routing_operator">%2$s</select>', $i, $operator_options );

			// Prepare add button.
			$add_button = sprintf(
				'<button class="gform-settings-field__notification-routing-button gform-settings-field__notification-routing-button--add gform-st-icon gform-st-icon--circle-plus" onclick="SetRouting(%2$d); InsertRouting(%3$d);">
					<span class="screen-reader-text">%1$s</span>
				</button>',
				esc_attr__( 'Add Another Rule', 'gravityforms' ),
				$i,
				$i + 1
			);

			// Prepare delete button.
			if ( count( $routing ) > 1 ) {
				$delete_button = sprintf(
						'<img src="%3$s/images/remove.png" id="routing_delete_%1$d" title="%2%s" class="delete_field_choice" style="cursor:pointer;" onclick="DeleteRouting(%1$d);" onkeypress="DeleteRouting(%1$d);" />',
					$i,
					esc_attr__( 'Remove This Rule', 'gravityforms' ),
					GFCommon::get_base_url()
				);
				$delete_button = sprintf(
					'<button class="gform-settings-field__notification-routing-button gform-settings-field__notification-routing-button--delete gform-st-icon gform-st-icon--circle-minus" onclick="DeleteRouting(%2$d);">
						<span class="screen-reader-text">%1$s</span>
					</button>',
					esc_attr__( 'Remove This Rule', 'gravityforms' ),
					$i
				);

			} else {
				$delete_button = '';
			}

			// Display input.
			$html .= sprintf(
				'<div%s>%s%s%s%s%s%s%s%s</div>',
				$valid_rule ? '' : ' class="gform-settings-field__notification-routing-route--invalid"',
				esc_html__( 'Send to', 'gravityforms' ),
				$email_input,
				esc_html__( 'if', 'gravityforms' ),
				$field_input,
				$operator_input,
				self::get_field_values( $i, $form, rgar( $route, 'fieldId' ), rgar( $route, 'value' ) ),
				$add_button,
				$delete_button
			);

		}

		// Close routing container.
		$html .= '</div>';

		// Display validation error.
		if ( $has_invalid_rule ) {
			$html .= sprintf(
				'<span class="gform-settings-validation__error">%s</span>',
				esc_html__( 'Please enter a valid email address for all highlighted routing rules above.', 'gravityforms' )
			);
		}

		ob_start();

		?>

		<script type="text/javascript">

			jQuery( document ).ready( function () {
				jQuery( document ).on( 'input propertychange', '.gfield_routing_email', function () {
					SetRoutingEmail( jQuery( this ) );
				} );
				jQuery( document ).on( 'change', '.gfield_routing_value_dropdown', function () {
					SetRoutingValueDropDown( jQuery( this ) );
				} );
			} );

			function SetRoutingEmail( element ) {
				// Parsing ID to get routing Index
				var index = element.attr( 'id' ).replace( 'routing_email_', '' );
				SetRouting( index );
			}

			function SetRoutingValueDropDown(element) {
				// Parsing ID to get routing Index
				var index = element.attr("id").replace("routing_value_", '');
				SetRouting(index);
			}

			function CreateRouting( routings ) {
				var str = '';
				for ( var i = 0; i < routings.length; i ++ ) {

					var isSelected = routings[ i ].operator == 'is' ? "selected='selected'" : '';
					var isNotSelected = routings[ i ].operator == 'isnot' ? "selected='selected'" : '';
					var greaterThanSelected = routings[ i ].operator == '>' ? "selected='selected'" : '';
					var lessThanSelected = routings[ i ].operator == '<' ? "selected='selected'" : '';
					var containsSelected = routings[ i ].operator == 'contains' ? "selected='selected'" : '';
					var startsWithSelected = routings[ i ].operator == 'starts_with' ? "selected='selected'" : '';
					var endsWithSelected = routings[ i ].operator == 'ends_with' ? "selected='selected'" : '';
					var email = routings[ i ][ "email" ] ? routings[ i ][ "email" ] : '';

					str += "<div>" + <?php echo json_encode( esc_html__( 'Send to', 'gravityforms' ) ); ?> + " <input type='text' id='routing_email_" + i + "' value='" + email + "' class='gfield_routing_email' />";
					str += " " + <?php echo json_encode( esc_html__( 'if', 'gravityforms' ) ); ?> + " " + GetRoutingFields( i, routings[ i ].fieldId ) + "&nbsp;";
					str += "<select id='routing_operator_" + i + "' onchange='SetRouting(" + i + ");' class='gform_routing_operator'>";
					str += "<option value='is' " + isSelected + ">" + <?php echo json_encode( esc_html__( 'is', 'gravityforms' ) ); ?> + "</option>";
					str += "<option value='isnot' " + isNotSelected + ">" + <?php echo json_encode( esc_html__( 'is not', 'gravityforms' ) ); ?> + "</option>";
					str += "<option value='>' " + greaterThanSelected + ">" + <?php echo json_encode( esc_html__( 'greater than', 'gravityforms' ) ); ?> + "</option>";
					str += "<option value='<' " + lessThanSelected + ">" + <?php echo json_encode( esc_html__( 'less than', 'gravityforms' ) ); ?> + "</option>";
					str += "<option value='contains' " + containsSelected + ">" + <?php echo json_encode( esc_html__( 'contains', 'gravityforms' ) ); ?> + "</option>";
					str += "<option value='starts_with' " + startsWithSelected + ">" + <?php echo json_encode( esc_html__( 'starts with', 'gravityforms' ) ); ?> + "</option>";
					str += "<option value='ends_with' " + endsWithSelected + ">" + <?php echo json_encode( esc_html__( 'ends with', 'gravityforms' ) ); ?> + "</option>";
					str += "</select>&nbsp;";
					str += GetRoutingValues( i, routings[ i ].fieldId, routings[ i ].value ) + "&nbsp;";

					str += "<button class='gform-settings-field__notification-routing-button gform-settings-field__notification-routing-button--add gform-st-icon gform-st-icon--circle-plus' onclick='InsertRouting(" + ( i + 1 ) + ");'>";
					str += "<span class='screen-reader-text'><?php esc_attr_e( 'Add Another Rule', 'gravityforms' ); ?></span>";
					str += "</button>";

					if ( routings.length > 1 ) {
						str += "<button class='gform-settings-field__notification-routing-button gform-settings-field__notification-routing-button--delete gform-st-icon gform-st-icon--circle-minus' onclick='DeleteRouting(" + ( i ) + ");'>";
						str += "<span class='screen-reader-text'><?php esc_attr_e( 'Remove This Rule', 'gravityforms' ); ?></span>";
						str += "</button>";
					}

					str += "</div>";
				}

				jQuery( "#gform_notification_to_routing_rules" ).html( str );
				jQuery( '#routing' ).val( JSON.stringify( current_notification.routing ) );
			}

			function GetRoutingValues( index, fieldId, selectedValue ) {
				var str = GetFieldValues( index, fieldId, selectedValue, 16 );
				return str;
			}

			function GetRoutingFields( index, selectedItem ) {
				var str = "<select id='routing_field_id_" + index + "' class='gfield_routing_select' onchange='jQuery(\"#routing_value_" + index + "\").replaceWith(GetRoutingValues(" + index + ", jQuery(this).val())); SetRouting(" + index + "); '>";
				str += GetSelectableFields( selectedItem, 16 );
				str += "</select>";
				return str;
			}

			//---------------------- generic ---------------
			function GetSelectableFields(selectedFieldId, labelMaxCharacters) {
				var str = "";
				var inputType;
				for (var i = 0; i < form.fields.length; i++) {
					inputType = form.fields[i].inputType ? form.fields[i].inputType : form.fields[i].type;
					// See if this field type can be used for conditionals.
					if (IsNotificationConditionalLogicField(form.fields[i])) {
						var selected = form.fields[i].id == selectedFieldId ? "selected='selected'" : "";
						str += "<option value='" + form.fields[i].id + "' " + selected + ">" + GetLabel(form.fields[i]) + "</option>";
					}
				}
				return str;
			}

			function IsNotificationConditionalLogicField( field ) {
				// This function is a duplicate of IsConditionalLogicField from form_editor.js
				inputType = field.inputType ? field.inputType : field.type;
				var supported_fields = <?php echo json_encode( GFNotification::get_routing_field_types() ); ?>;
				var index = jQuery.inArray( inputType, supported_fields );
				return index >= 0;
			}

			function GetFirstSelectableField() {
				var inputType;
				for (var i = 0; i < form.fields.length; i++) {
					inputType = form.fields[i].inputType ? form.fields[i].inputType : form.fields[i].type;
					if (IsNotificationConditionalLogicField(form.fields[i])) {
						return form.fields[i].id;
					}
				}

				return 0;
			}

			function TruncateMiddle(text, maxCharacters) {
				if (!text)
					return "";

				if (text.length <= maxCharacters)
					return text;
				var middle = parseInt(maxCharacters / 2);
				return text.substr(0, middle) + "..." + text.substr(text.length - middle, middle);

			}

			function GetFieldValues(index, fieldId, selectedValue, labelMaxCharacters) {
				if (!fieldId)
					fieldId = GetFirstSelectableField();

				if (!fieldId)
					return "";

				var str = '';
				var field = GetFieldById(fieldId);
				var isAnySelected = false;

				if (!field)
					return "";

				if (field["type"] == 'post_category' && field["displayAllCategories"]) {
					var dropdown_id = 'routing_value_' + index;
					var dropdown = jQuery('#' + dropdown_id + ".gfield_category_dropdown");

					// Don't load category drop down if it already exists (to avoid unecessary ajax requests).
					if (dropdown.length > 0) {

						var options = dropdown.html();
						options = options.replace("value=\"" + selectedValue + "\"", "value=\"" + selectedValue + "\" selected=\"selected\"");
						str = "<select id='" + dropdown_id + "' class='gfield_routing_select gfield_category_dropdown gfield_routing_value_dropdown'>" + options + "</select>";
					}
					else {
						// Loading categories via AJAX.
						jQuery.post(ajaxurl, {   action: "gf_get_notification_post_categories",
								ruleIndex              : index,
								selectedValue          : selectedValue},
							function (dropdown_string) {
								if (dropdown_string) {
									jQuery('#gfield_ajax_placeholder_' + index).replaceWith(dropdown_string.trim());
								}
							}
						);

						// Will be replaced by real drop down during the ajax callback.
						str = "<select id='gfield_ajax_placeholder_" + index + "' class='gfield_routing_select'><option>" + <?php json_encode( esc_html__( 'Loading...', 'gravityforms' ) ); ?> + "</option></select>";
					}
				}
				else if (field.choices) {
					// Create a drop down for fields that have choices (i.e. drop down, radio, checkboxes, etc...).
					str = "<select class='gfield_routing_select gfield_routing_value_dropdown' id='routing_value_" + index + "'>";

					if (field.placeholder) {
						str += "<option value=''>" + field.placeholder + "</option>";
					}

					for (var i = 0; i < field.choices.length; i++) {
						var choiceValue = field.choices[i].value ? field.choices[i].value : field.choices[i].text;
						var isSelected = choiceValue == selectedValue;
						var selected = isSelected ? "selected='selected'" : '';
						if (isSelected)
							isAnySelected = true;

						str += "<option value='" + choiceValue.replace(/'/g, "&#039;") + "' " + selected + ">" + field.choices[i].text + "</option>";
					}

					if (!isAnySelected && selectedValue) {
						str += "<option value='" + selectedValue.replace(/'/g, "&#039;") + "' selected='selected'>" + selectedValue + "</option>";
					}
					str += "</select>";
				}
				else {
					selectedValue = selectedValue ? selectedValue.replace(/'/g, "&#039;") : "";
					// Create a text field for fields that don't have choices (i.e text, textarea, number, email, etc...).
					str = "<input type='text' placeholder='" + <?php echo json_encode( esc_html__( 'Enter value', 'gravityforms' ) ); ?> +"' class='gfield_routing_select' id='routing_value_" + index + "' value='" + selectedValue.replace(/'/g, "&#039;") + "' onchange='SetRouting(" + index + ");' onkeyup='SetRouting(" + index + ");'>";
				}
				return str;
			}

			//---------------------------------------------------------------------------------

			function InsertRouting(index) {
				var routings = current_notification.routing;
				routings.splice(index, 0, new ConditionalRule());

				CreateRouting(routings);
				SetRouting(index);
				jQuery( '#routing' ).val( JSON.stringify( current_notification.routing ) );
			}

			/**
			 * Set the route array and the hidden field that holds the route JSON.
			 *
			 * @since unknown
			 * @since 2.5 Updated to keep the hidden field and the routing array in sync.
			 *
			 * @param {int} ruleIndex The index of the rule being edited.
			 */
			function SetRouting( ruleIndex ) {

				// Get the current value of the hidden field and set it to a new conditional rule if it is an empty array.
				$currentHiddenValue = JSON.parse( jQuery( '#routing' ).val() );
				if ( $currentHiddenValue[0].length === 0 ) {
					$currentHiddenValue[0] = new ConditionalRule();
					jQuery( '#routing' ).val( JSON.stringify( $currentHiddenValue ) );
				};

				// Set the routing array for the current notification based on the hidden field value, so it populates even if validation fails.
				current_notification.routing = $currentHiddenValue;

				// If the current routing index doesn't exist after failed validation, create a blank conditional rule so we can update the values.
				if ( !current_notification.routing[ruleIndex] ) {
					current_notification.routing.splice( current_notification.routing.length, 0, new ConditionalRule() );
				}

				current_notification.routing[ruleIndex]["email"] = jQuery( "#routing_email_" + ruleIndex ).val();
				current_notification.routing[ruleIndex]["fieldId"] = jQuery( "#routing_field_id_" + ruleIndex ).val();
				current_notification.routing[ruleIndex]["operator"] = jQuery( "#routing_operator_" + ruleIndex ).val();
				current_notification.routing[ruleIndex]["value"] = jQuery( "#routing_value_" + ruleIndex ).val();

				jQuery( '#routing' ).val( JSON.stringify( current_notification.routing ) );
			}

			function DeleteRouting(ruleIndex) {
				// Set the routing array for the current notification based on the hidden field value, so we delete the correct index.
				current_notification.routing = JSON.parse( jQuery( '#routing' ).val() );
				current_notification.routing.splice(ruleIndex, 1);
				CreateRouting(current_notification.routing);
				jQuery( '#routing' ).val( JSON.stringify( current_notification.routing ) );
			}
		</script>

		<?php

		$html .= ob_get_contents();
		ob_end_clean();

		return $html;

	}





	// # VALIDATION METHODS --------------------------------------------------------------------------------------------

	/**
	 * Validate posted field value.
	 *
	 * @since 2.5
	 *
	 * @param array $value Posted field value.
	 */
	public function do_validation( $value ) {

		// If no routes are defined, set field error.
		if ( empty( $value ) || ! is_array( $value ) ) {
			$this->set_error( rgobj( $this, 'error_message' ) );
		}

		// Validate routes.
		foreach ( $value as $route ) {
			if ( ! GFNotification::is_valid_notification_email( rgar( $route, 'email' ) ) ) {
				$this->set_error( rgobj( $this, 'error_message' ) );
			}
		}

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Gets all fields that can be used for notification routing and builds dropdowns.
	 *
	 * @since  2.5
	 *
	 * @param array $form              The Form Object to search through.
	 * @param int   $selected_field_id The currently selected field ID.
	 *
	 * @return string
	 */
	private static function get_routing_fields( $form, $selected_field_id ) {

		$str = '';
		foreach ( $form['fields'] as $field ) {
			$field_label = GFFormsModel::get_label( $field );
			if ( in_array( $field->get_input_type(), GFNotification::get_routing_field_types() ) ) {
				$selected = $field->id == $selected_field_id ? "selected='selected'" : '';
				$str      .= "<option value='" . $field->id . "' " . $selected . '>' . $field_label . '</option>';
			}
		}

		return $str;

	}

	/**
	 * Gets field values to be used with routing
	 *
	 * @since  Unknown
	 *
	 * @param int    $i                The routing rule ID.
	 * @param array  $form             The Form Object.
	 * @param int    $field_id         The field ID.
	 * @param string $selected_value   The field value of the selected item.
	 *
	 * @return string
	 */
	private static function get_field_values( $i, $form, $field_id, $selected_value ) {

		if ( empty( $field_id ) ) {
			$field_id = self::get_first_routing_field( $form );
		}

		if ( empty( $field_id ) ) {
			return null;
		}

		$field = GFAPI::get_field( $form, $field_id );

		if ( ! $field ) {
			return null;
		}

		if ( $field->type == 'post_category' && $field->displayAllCategories == true ) {

			return wp_dropdown_categories(
				array(
					'class'        => 'gfield_routing_select gfield_category_dropdown gfield_routing_value_dropdown',
					'orderby'      => 'name',
					'id'           => 'routing_value_' . $i,
					'selected'     => $selected_value,
					'hierarchical' => true,
					'hide_empty'   => 0,
					'echo'         => false,
				)
			);

		} else if ( $field->choices ) {

			$is_any_selected = false;

			$html = sprintf( '<select id="routing_value_%d" class="gfield_routing_select gfield_routing_value_dropdown">', $i );
			$html .= $field->placeholder ? sprintf( '<option value="">%s</option>', esc_html( $field->placeholder ) ) : null;

			foreach ( $field->choices as $choice ) {

				if ( $choice['value'] == $selected_value ) {
					$is_any_selected = true;
				}

				$html .= sprintf(
					'<option value="%s"%s>%s</option>',
					esc_html( $choice['value'] ),
					selected( $selected_value, $choice['value'], false ),
					esc_html( rgar( $choice, 'text' ) )
				);

			}

			// Adding current selected field value to the list
			if ( ! $is_any_selected && ! empty( $selected_value ) ) {
				$html .= sprintf(
					'<option value="%1$s" selected="selected">%1$s</option>',
					esc_html( $selected_value )
				);
			}

			$html .= '</select>';

			return $html;

		} else {

			return sprintf(
				'<input type="text" placeholder="%1$s" class="gfield_routing_select" id="routing_value_%3$d" value="%2$s" onchange="SetRouting( %3$d );" onkeyup="SetRouting( %3$d );" />',
				esc_html__( 'Enter value', 'gravityforms' ),
				esc_attr( $selected_value ),
				$i
			);

		}

	}

	/**
	 * Gets the first field that can be used for notification routing.
	 *
	 * @since  Unknown
	 *
	 * @param array $form The Form Object to search through.
	 *
	 * @return int
	 */
	private static function get_first_routing_field( $form ) {

		foreach ( $form['fields'] as $field ) {
			if ( in_array( $field->get_input_type(), GFNotification::get_routing_field_types() ) ) {
				return $field->id;
			}
		}

		return 0;

	}

}

Fields::register( 'notification_routing', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Notification_Routing' );
