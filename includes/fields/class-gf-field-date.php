<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Date extends GF_Field {

	public $type = 'date';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Date', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to enter a date.', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a gform-icon class.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return 'gform-icon--date';
	}

	/**
	 * Defines the IDs of required inputs.
	 *
	 * @since 2.5
	 *
	 * @return string[]
	 */
	public function get_required_inputs_ids() {
		return array( '1', '2', '3' );
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'prepopulate_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'sub_label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'date_input_type_setting',
			'visibility_setting',
			'duplicate_setting',
			'date_format_setting',
			'default_value_setting',
			'placeholder_setting',
			'description_setting',
			'css_class_setting',
		);
	}

	/**
	 * Whether this field expects an array during submission.
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	public function is_value_submission_array() {
		return in_array( $this->dateType, array( 'datefield', 'datedropdown' ) );
	}

	public function validate( $value, $form ) {
		if ( is_array( $value ) && rgempty( 0, $value ) && rgempty( 1, $value ) && rgempty( 2, $value ) ) {
			$value = null;
		}

		if ( is_array( $value ) && $this->isRequired ) {
			$required_inputs = array( 0, 1, 2 );

			$message = $this->complex_validation_message( $value, $required_inputs );

			if ( $message ) {
				$this->failed_validation  = true;
				$message_intro            = empty( $this->errorMessage ) ? __( 'This field is required.', 'gravityforms' ) : $this->errorMessage;
				$this->validation_message = $message_intro . ' ' . $message;
			}
		}

		if ( ! empty( $value ) ) {
			$format = empty( $this->dateFormat ) ? 'mdy' : $this->dateFormat;
			$date   = GFCommon::parse_date( $value, $format );

			if ( empty( $date ) || ! $this->checkdate( $date['month'], $date['day'], $date['year'] ) ) {
				$this->failed_validation = true;
				$format_name             = '';
				switch ( $format ) {
					case 'mdy' :
						$format_name = 'mm/dd/yyyy';
						break;
					case 'dmy' :
						$format_name = 'dd/mm/yyyy';
						break;
					case 'dmy_dash' :
						$format_name = 'dd-mm-yyyy';
						break;
					case 'dmy_dot' :
						$format_name = 'dd.mm.yyyy';
						break;
					case 'ymd_slash' :
						$format_name = 'yyyy/mm/dd';
						break;
					case 'ymd_dash' :
						$format_name = 'yyyy-mm-dd';
						break;
					case 'ymd_dot' :
						$format_name = 'yyyy.mm.dd';
						break;
				}
				$message                  = $this->dateType == 'datepicker' ? sprintf( esc_html__( 'Please enter a valid date in the format (%s).', 'gravityforms' ), $format_name ) : esc_html__( 'Please enter a valid date.', 'gravityforms' );
				$this->validation_message = empty( $this->errorMessage ) ? $message : $this->errorMessage;
			}
		}
	}

	/**
	 * Create a validation message for a required field with multiple inputs.
	 *
	 * The validation message will specify which inputs need to be filled out.
	 *
	 * @since 2.5
	 *
	 * @param array $value            The value entered by the user.
	 * @param array $required_inputs  The required inputs to validate.
	 *
	 * @return string|void
	 */
	public function complex_validation_message( $value, $required_inputs ) {
		$error_inputs = array();

		foreach ( $required_inputs as $input ) {
			if ( '' == $value[ $input ] ) {
				$input_id       = $input + 1;
				$custom_label   = $this->get_input_property( $input_id, 'customLabel' );
				$label          = $custom_label ? $custom_label : $this->get_input_property( $input_id, 'label' );
				$error_inputs[] = $label;
			}
		}

		if ( ! empty( $error_inputs ) ) {
			$field_list = implode( ', ', $error_inputs );
			// Translators: comma-separated list of the labels of missing fields.
			$message = sprintf( __( 'This field is required. Please complete the following fields: %s.', 'gravityforms' ), $field_list );
			return $message;
		}

		return false;
	}

	public function is_value_submission_empty( $form_id ) {
		$value = rgpost( 'input_' . $this->id );
		if ( is_array( $value ) ) {
 			// Date field and date drop-downs
			// If some but not all inputs are empty, return false so that this field's validation method will be triggered.
			return empty( array_filter( $value ) );
		} else {
			// Date picker
			return strlen( trim( $value ) ) <= 0;
		}
	}

	/**
	 * Returns the HTML tag for the field container.
	 *
	 * @since 2.5
	 *
	 * @param array $form The current Form object.
	 *
	 * @return string
	 */
	public function get_field_container_tag( $form ) {

		if ( GFCommon::is_legacy_markup_enabled( $form ) || $this->dateType === 'datepicker' ) {
			return parent::get_field_container_tag( $form );
		}

		return 'fieldset';

	}

	/**
	 * Returns the field inner markup.
	 *
	 * @since unknown
	 * @since 2.5     Added accessibility improvements.
	 *
	 * @param array        $form  The Form Object currently being processed.
	 * @param string|array $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array   $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {

		$picker_value = '';
		if ( is_array( $value ) ) {
			// GFCommon::parse_date() takes a numeric array.
			$value = array_values( $value );
		} else {
			$picker_value = esc_attr( $value );
		}

		$format                 = empty( $this->dateFormat ) ? 'mdy' : esc_attr( $this->dateFormat );
		$date_info              = GFCommon::parse_date( $value, $format );

		$day_value   = esc_attr( rgget( 'day', $date_info ) );
		$month_value = esc_attr( rgget( 'month', $date_info ) );
		$year_value  = esc_attr( rgget( 'year', $date_info ) );

		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();

		$form_id  = $form['id'];
		$id       = intval( $this->id );
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$disabled_text = $is_form_editor ? "disabled='disabled'" : '';

		$form_sub_label_placement  = rgar( $form, 'subLabelPlacement' );
		$field_sub_label_placement = $this->subLabelPlacement;
		$is_sub_label_above        = $field_sub_label_placement == 'above' || ( empty( $field_sub_label_placement ) && $form_sub_label_placement == 'above' );
		$sub_label_class = $field_sub_label_placement == 'hidden_label' ? array( 'hidden_sub_label', 'screen-reader-text' ) : array();

		$month_input = GFFormsModel::get_input( $this, $this->id . '.1' );
		$day_input   = GFFormsModel::get_input( $this, $this->id . '.2' );
		$year_input  = GFFormsModel::get_input( $this, $this->id . '.3' );

		$month_placeholder_attribute = $this->get_input_placeholder_attribute( $month_input );
		$day_placeholder_attribute   = $this->get_input_placeholder_attribute( $day_input );
		$year_placeholder_attribute  = $this->get_input_placeholder_attribute( $year_input );

		$month_placeholder_value = $this->get_input_placeholder_value( $month_input );
		$day_placeholder_value   = $this->get_input_placeholder_value( $day_input );
		$year_placeholder_value  = $this->get_input_placeholder_value( $year_input );

		$date_picker_placeholder = $this->get_field_placeholder_attribute();

		// Get the month sub-label and update the sub-label class if needed.
		$month_sub_label       = $this->get_input_label( $month_input );
		$month_sub_label_class = $this->get_input_label_class( $month_input, $sub_label_class );

		// Get the day sub-label and update the sub-label class if needed.
		$day_sub_label       = $this->get_input_label( $day_input );
		$day_sub_label_class = $this->get_input_label_class( $day_input, $sub_label_class );

		// Get the year sub-label and update the sub-label class if needed.
		$year_sub_label       = $this->get_input_label( $year_input );
		$year_sub_label_class = $this->get_input_label_class( $year_input, $sub_label_class );

		$is_html5        = RGFormsModel::is_html5_enabled();
		$date_input_type = $is_html5 ? 'number' : 'text';

		$month_html5_attributes = $is_html5 ? "min='1' max='12' step='1'" : '';
		$day_html5_attributes   = $is_html5 ? "min='1' max='31' step='1'" : '';

		$year_min = apply_filters( 'gform_date_min_year', '1920', $form, $this );
		$year_max = apply_filters( 'gform_date_max_year', date( 'Y' ) + 1, $form, $this );

		$year_min_attribute  = $is_html5 && is_numeric( $year_min ) ? "min='{$year_min}'" : '';
		$year_max_attribute  = $is_html5 && is_numeric( $year_max ) ? "max='{$year_max}'" : '';
		$year_step_attribute = $is_html5 ? "step='1'" : '';

		$month_maxlength = $is_html5 ? '' : "maxlength='2'";
		$day_maxlength   = $is_html5 ? '' : "maxlength='2'";
		$year_maxlength  = $is_html5 ? '' : "maxlength='4'";

		// A11y improvements for the date picker field.
		$date_format_sr_text = $this->get_date_format( 'screen_reader_text' );

		$clear_multi_div_open = GFCommon::is_legacy_markup_enabled( $form ) ? '<div class="clear-multi">' : '';
		$clear_multi_div_close = GFCommon::is_legacy_markup_enabled( $form ) ? '</div>' : '';

		$field_position = substr( $format, 0, 3 );
		if ( $is_form_editor ) {

			$datepicker_display = in_array( $this->dateType, array( 'datefield', 'datedropdown' ) ) ? 'none' : 'block';
			$datefield_display  = $this->dateType == 'datefield' ? 'inline' : 'none';
			$dropdown_display   = $this->dateType == 'datedropdown' ? 'inline' : 'none';
			$icon_display       = $this->calendarIconType == 'calendar' ? 'inline' : 'none';

			// Create pseudo values for date field inputs.
			if ( $this->dateType === 'datepicker' ) {
				$month_sub_label             = $this->get_input_label( $this->id . '.1' );
				$month_sub_label_class       .= ' screen-reader-text';
				$month_placeholder_attribute = ' placeholder="MM"';
				$day_sub_label               = $this->get_input_label( $this->id . '.2' );
				$day_sub_label_class         .= ' screen-reader-text';
				$day_placeholder_attribute   = ' placeholder="DD"';
				$year_sub_label              = $this->get_input_label( $this->id . '.3' );
				$year_sub_label_class        .= ' screen-reader-text';
				$year_placeholder_attribute  = ' placeholder="YYYY"';
			}

			if ( $is_sub_label_above ) {
				$month_field = "<div class='gfield_date_month ginput_date ginput_container ginput_container_date' id='gfield_input_date_month' style='display:$datefield_display'>
                                    <label for='{$field_id}_1' class='{$month_sub_label_class}'>{$month_sub_label}</label>
                                    <input id='{$field_id}_1' name='ginput_month' type='text' {$month_placeholder_attribute} {$disabled_text} value='{$month_value}'/>
                                </div>";
				$day_field   = "<div class='gfield_date_day ginput_date ginput_container ginput_container_date' id='gfield_input_date_day' style='display:$datefield_display'>
                                    <label for='{$field_id}_2' class='{$day_sub_label_class}'>{$day_sub_label}</label>
                                    <input id='{$field_id}_2' name='ginput_day' type='text' {$day_placeholder_attribute} {$disabled_text} value='{$day_value}'/>
                               </div>";
				$year_field  = "<div class='gfield_date_year ginput_date ginput_container ginput_container_date' id='gfield_input_date_year' style='display:$datefield_display'>
                                    <label for='{$field_id}_3' class='{$year_sub_label_class}'>{$year_sub_label}</label>
                                    <input id='{$field_id}_3' type='text' name='text' {$year_placeholder_attribute} {$disabled_text} value='{$year_value}'/>
                               </div>";
			} else {
				$month_field = "<div class='gfield_date_month ginput_date ginput_container ginput_container_date' id='gfield_input_date_month' style='display:$datefield_display'>
                                    <input id='{$field_id}_1' name='ginput_month' type='text' {$month_placeholder_attribute} {$disabled_text} value='{$month_value}'/>
                                    <label for='{$field_id}_1' class='{$month_sub_label_class}'>{$month_sub_label}</label>
                                </div>";
				$day_field   = "<div class='gfield_date_day ginput_date ginput_container ginput_container_date' id='gfield_input_date_day' style='display:$datefield_display'>
                                    <input id='{$field_id}_2' name='ginput_day' type='text' {$day_placeholder_attribute} {$disabled_text} value='{$day_value}'/>
                                    <label for='{$field_id}_2' class='{$day_sub_label_class}'>{$day_sub_label}</label>
                              </div>";
				$year_field  = "<div class='gfield_date_year ginput_date ginput_container ginput_container_date' id='gfield_input_date_year' style='display:$datefield_display'>
                                    <input type='text' id='{$field_id}_3' name='ginput_year' {$year_placeholder_attribute} {$disabled_text} value='{$year_value}'/>
                                    <label for='{$field_id}_3' class='{$year_sub_label_class}'>{$year_sub_label}</label>
                               </div>";
			}

			$month_dropdown = "<div class='gfield_date_dropdown_month ginput_date_dropdown ginput_container ginput_container_date' id='gfield_dropdown_date_month' style='display:$dropdown_display'>" . $this->get_month_dropdown( '', "{$field_id}_1", rgar( $date_info, 'month' ), '', $disabled_text, $month_placeholder_value ) . '</div>';
			$day_dropdown   = "<div class='gfield_date_dropdown_day ginput_date_dropdown ginput_container ginput_container_date' id='gfield_dropdown_date_day' style='display:$dropdown_display'>" . $this->get_day_dropdown( '', "{$field_id}_2", rgar( $date_info, 'day' ), '', $disabled_text, $day_placeholder_value ) . '</div>';
			$year_dropdown  = "<div class='gfield_date_dropdown_year ginput_date_dropdown ginput_container ginput_container_date' id='gfield_dropdown_date_year' style='display:$dropdown_display'>" . $this->get_year_dropdown( '', "{$field_id}_3", rgar( $date_info, 'year' ), '', $disabled_text, $year_placeholder_value, $form ) . '</div>';

			$field_string = "<div class='ginput_container ginput_container_date' id='gfield_input_datepicker' style='display:$datepicker_display'><input name='ginput_datepicker' type='text' {$date_picker_placeholder} {$disabled_text} value='{$picker_value}'/><img src='" . GFCommon::get_base_url() . "/images/datepicker/datepicker.svg' id='gfield_input_datepicker_icon' style='display:$icon_display'/></div>";

			switch ( $field_position ) {
				case 'dmy' :
					$date_inputs = $day_field . $month_field . $year_field . $day_dropdown . $month_dropdown . $year_dropdown;
					break;

				case 'ymd' :
					$date_inputs = $year_field . $month_field . $day_field . $year_dropdown . $month_dropdown . $day_dropdown;
					break;

				default :
					$date_inputs = $month_field . $day_field . $year_field . $month_dropdown . $day_dropdown . $year_dropdown;
					break;
			}

			$field_string .= "<div id='{$field_id}' class='ginput_container ginput_complex'>{$date_inputs}</div>";

			return $field_string;
		} else {

			$date_type = $this->dateType;
			if ( in_array( $date_type, array( 'datefield', 'datedropdown' ) ) ) {

				$input_key_values = array(
					'month' => $this->id . '.1',
					'day' => $this->id . '.2',
					'year' => $this->id . '.3',
				);
				$input_values = array_combine( array_merge( $date_info, $input_key_values ), $date_info );
				$month_aria_attributes = $this->get_aria_attributes( $input_values, '1' );
				$year_aria_attributes = $this->get_aria_attributes( $input_values, '3' );
				$day_aria_attributes = $this->get_aria_attributes( $input_values, '2' );

				switch ( $field_position ) {

					case 'dmy' :

						$tabindex = $this->get_tabindex();

						if ( $date_type == 'datedropdown' ) {

							$field_str = "{$clear_multi_div_open}<div class='gfield_date_dropdown_day ginput_container ginput_container_date' id='{$field_id}_2_container'>" . $this->get_day_dropdown( "input_{$id}[]", "{$field_id}_2", rgar( $date_info, 'day' ), $tabindex, $disabled_text, $day_placeholder_value, $day_aria_attributes ) . '</div>';

							$tabindex = $this->get_tabindex();
							$field_str .= "<div class='gfield_date_dropdown_month ginput_container ginput_container_date' id='{$field_id}_1_container'>" . $this->get_month_dropdown( "input_{$id}[]", "{$field_id}_1", rgar( $date_info, 'month' ), $tabindex, $disabled_text, $month_placeholder_value, $month_aria_attributes ) . '</div>';

							$tabindex = $this->get_tabindex();

							$field_str .= "<div class='gfield_date_dropdown_year ginput_container ginput_container_date' id='{$field_id}_3_container'>" . $this->get_year_dropdown( "input_{$id}[]", "{$field_id}_3", rgar( $date_info, 'year' ), $tabindex, $disabled_text, $year_placeholder_value, $form, $year_aria_attributes ) ."</div>{$clear_multi_div_close}";
						} else {

							$field_str = $is_sub_label_above
								? "{$clear_multi_div_open}
                                        <div class='gfield_date_day ginput_container ginput_container_date' id='{$field_id}_2_container'>
                                            <label for='{$field_id}_2' class='{$day_sub_label_class}'>{$day_sub_label}</label>
                                            <input type='{$date_input_type}' {$day_maxlength} name='input_{$id}[]' id='{$field_id}_2' value='$day_value' {$tabindex} {$disabled_text} {$day_aria_attributes} {$day_placeholder_attribute} {$day_html5_attributes}/>
                                        </div>"
								: "{$clear_multi_div_open}
                                        <div class='gfield_date_day ginput_container ginput_container_date' id='{$field_id}_2_container'>
                                            <input type='{$date_input_type}' {$day_maxlength} name='input_{$id}[]' id='{$field_id}_2' value='$day_value' {$tabindex} {$disabled_text} {$day_aria_attributes} {$day_placeholder_attribute} {$day_html5_attributes}/>
                                            <label for='{$field_id}_2' class='{$day_sub_label_class}'>{$day_sub_label}</label>
                                        </div>";

							$tabindex = $this->get_tabindex();

							$field_str .= $is_sub_label_above
								? "<div class='gfield_date_month ginput_container ginput_container_date' id='{$field_id}_1_container'>
                                        <label for='{$field_id}_1' class='{$month_sub_label_class}'>{$month_sub_label}</label>
                                        <input type='{$date_input_type}' {$month_maxlength} name='input_{$id}[]' id='{$field_id}_1' value='{$month_value}' {$tabindex} {$disabled_text} {$month_aria_attributes} {$month_placeholder_attribute} {$month_html5_attributes}/>
                                   </div>"
								: "<div class='gfield_date_month ginput_container ginput_container_date' id='{$field_id}_1_container'>
                                        <input type='{$date_input_type}' {$month_maxlength} name='input_{$id}[]' id='{$field_id}_1' value='{$month_value}' {$tabindex} {$disabled_text} {$month_aria_attributes} {$month_placeholder_attribute} {$month_html5_attributes}/>
                                        <label for='{$field_id}_1' class='{$month_sub_label_class}'>{$month_sub_label}</label>
                                   </div>";

							$tabindex = $this->get_tabindex();

							$field_str .= $is_sub_label_above
								? "<div class='gfield_date_year ginput_container ginput_container_date' id='{$field_id}_3_container'>
                                            <label for='{$field_id}_3' class='{$year_sub_label_class}'>{$year_sub_label}</label>
                                            <input type='{$date_input_type}' {$year_maxlength} name='input_{$id}[]' id='{$field_id}_3' value='{$year_value}' {$tabindex} {$disabled_text} {$year_aria_attributes} {$year_placeholder_attribute} {$year_min_attribute} {$year_max_attribute} {$year_step_attribute}/>
                                       </div>
                                    {$clear_multi_div_close}"
								: "<div class='gfield_date_year ginput_container ginput_container_date' id='{$field_id}_3_container'>
                                        <input type='{$date_input_type}' {$year_maxlength} name='input_{$id}[]' id='{$field_id}_3' value='{$year_value}' {$tabindex} {$disabled_text} {$year_aria_attributes} {$year_placeholder_attribute} {$year_min_attribute} {$year_max_attribute} {$year_step_attribute}/>
                                        <label for='{$field_id}_3' class='{$year_sub_label_class}'>{$year_sub_label}</label>
                                   </div>
                                {$clear_multi_div_close}";

						}

						break;

					case 'ymd' :

						$tabindex = $this->get_tabindex();

						if ( $date_type == 'datedropdown' ) {

							$field_str = "{$clear_multi_div_open}<div class='gfield_date_dropdown_year ginput_container ginput_container_date' id='{$field_id}_3_container'>" . $this->get_year_dropdown( "input_{$id}[]", "{$field_id}_3", rgar( $date_info, 'year' ), $tabindex, $disabled_text, $year_placeholder_value, $form, $year_aria_attributes ) . '</div>';

							$tabindex = $this->get_tabindex();

							$field_str .= "<div class='gfield_date_dropdown_month ginput_container ginput_container_date' id='{$field_id}_1_container'>" . $this->get_month_dropdown( "input_{$id}[]", "{$field_id}_1", rgar( $date_info, 'month' ), $tabindex, $disabled_text, $month_placeholder_value, $month_aria_attributes ) . '</div>';

							$tabindex = $this->get_tabindex();

							$field_str .= "<div class='gfield_date_dropdown_day ginput_container ginput_container_date' id='{$field_id}_2_container'>" . $this->get_day_dropdown( "input_{$id}[]", "{$field_id}_2", rgar( $date_info, 'day' ), $tabindex, $disabled_text, $day_placeholder_value, $day_aria_attributes ) . "</div>{$clear_multi_div_close}";
						} else {

							$field_str = $is_sub_label_above
								? "{$clear_multi_div_open}
                                            <div class='gfield_date_year ginput_container ginput_container_date' id='{$field_id}_3_container'>
                                                <label for='{$field_id}_3' class='{$year_sub_label_class}'>{$year_sub_label}</label>
                                                <input type='{$date_input_type}' {$year_maxlength} name='input_{$id}[]' id='{$field_id}_3' value='{$year_value}' {$tabindex} {$disabled_text} {$year_aria_attributes} {$year_placeholder_attribute} {$year_min_attribute} {$year_max_attribute} {$year_step_attribute}/>
                                            </div>"
								: "{$clear_multi_div_open}
                                            <div class='gfield_date_year ginput_container ginput_container_date' id='{$field_id}_3_container'>
                                                <input type='{$date_input_type}' {$year_maxlength} name='input_{$id}[]' id='{$field_id}_3' value='{$year_value}' {$tabindex} {$disabled_text} {$year_aria_attributes} {$year_placeholder_attribute} {$year_min_attribute} {$year_max_attribute} {$year_step_attribute}/>
                                                <label for='{$field_id}_3' class='{$year_sub_label_class}'>{$year_sub_label}</label>
                                            </div>";

							$tabindex = $this->get_tabindex();

							$field_str .= $is_sub_label_above
								? "<div class='gfield_date_month ginput_container ginput_container_date' id='{$field_id}_1_container'>
                                                <label for='{$field_id}_1' class='{$month_sub_label_class}'>{$month_sub_label}</label>
                                                <input type='{$date_input_type}' {$month_maxlength} name='input_{$id}[]' id='{$field_id}_1' value='{$month_value}' {$tabindex} {$disabled_text} {$month_aria_attributes} {$month_placeholder_attribute} {$month_html5_attributes}/>
                                            </div>"
								: "<div class='gfield_date_month ginput_container ginput_container_date' id='{$field_id}_1_container'>
                                                <input type='{$date_input_type}' {$month_maxlength} name='input_{$id}[]' id='{$field_id}_1' value='{$month_value}' {$tabindex} {$disabled_text} {$month_aria_attributes} {$month_placeholder_attribute} {$month_html5_attributes}/>
                                                <label for='{$field_id}_1' class='{$month_sub_label_class}'>{$month_sub_label}</label>
                                            </div>";

							$tabindex = $this->get_tabindex();

							$field_str .= $is_sub_label_above
								? "<div class='gfield_date_day ginput_container ginput_container_date' id='{$field_id}_2_container'>
                                                <label for='{$field_id}_2' class='{$day_sub_label_class}'>{$day_sub_label}</label>
                                                <input type='{$date_input_type}' {$day_maxlength} name='input_{$id}[]' id='{$field_id}_2' value='{$day_value}' {$tabindex} {$disabled_text} {$day_aria_attributes} {$day_placeholder_attribute} {$day_html5_attributes}/>
                                           </div>
                                        {$clear_multi_div_close}"
								: "<div class='gfield_date_day ginput_container ginput_container_date' id='{$field_id}_2_container'>
                                                <input type='{$date_input_type}' {$day_maxlength} name='input_{$id}[]' id='{$field_id}_2' value='{$day_value}' {$tabindex} {$disabled_text} {$day_aria_attributes} {$day_placeholder_attribute} {$day_html5_attributes}/>
                                                <label for='{$field_id}_2' class='{$day_sub_label_class}'>{$day_sub_label}</label>
                                           </div>
                                        {$clear_multi_div_close}";
						}

						break;

					default :
						$tabindex = $this->get_tabindex();

						if ( $date_type == 'datedropdown' ) {

							$field_str = "{$clear_multi_div_open}<div class='gfield_date_dropdown_month ginput_container ginput_container_date' id='{$field_id}_1_container'>" . $this->get_month_dropdown( "input_{$id}[]", "{$field_id}_1", rgar( $date_info, 'month' ), $tabindex, $disabled_text, $month_placeholder_value, $month_aria_attributes ) . '</div>';

							$tabindex = $this->get_tabindex();

							$field_str .= "<div class='gfield_date_dropdown_day ginput_container ginput_container_date' id='{$field_id}_2_container'>" . $this->get_day_dropdown( "input_{$id}[]", "{$field_id}_2", rgar( $date_info, 'day' ), $tabindex, $disabled_text, $day_placeholder_value, $day_aria_attributes ) . '</div>';

							$tabindex = $this->get_tabindex();

							$field_str .= "<div class='gfield_date_dropdown_year ginput_container ginput_container_date' id='{$field_id}_3_container'>" . $this->get_year_dropdown( "input_{$id}[]", "{$field_id}_3", rgar( $date_info, 'year' ), $tabindex, $disabled_text, $year_placeholder_value, $form, $year_aria_attributes ) . "</div>{$clear_multi_div_close}";
						} else {

							$field_str = $is_sub_label_above
								? "{$clear_multi_div_open}<div class='gfield_date_month ginput_container ginput_container_date' id='{$field_id}_1_container'>
                                            <label for='{$field_id}_1' class='{$month_sub_label_class}'>{$month_sub_label}</label>
                                            <input type='{$date_input_type}' {$month_maxlength} name='input_{$id}[]' id='{$field_id}_1' value='{$month_value}' {$tabindex} {$disabled_text} {$month_aria_attributes} {$month_placeholder_attribute} {$month_html5_attributes}/>
                                        </div>"
								: "{$clear_multi_div_open}<div class='gfield_date_month ginput_container ginput_container_date' id='{$field_id}_1_container'>
                                            <input type='{$date_input_type}' {$month_maxlength} name='input_{$id}[]' id='{$field_id}_1' value='{$month_value}' {$tabindex} {$disabled_text} {$month_aria_attributes} {$month_placeholder_attribute} {$month_html5_attributes}/>
                                            <label for='{$field_id}_1' class='{$month_sub_label_class}'>{$month_sub_label}</label>
                                        </div>";

							$tabindex = $this->get_tabindex();

							$field_str .= $is_sub_label_above
								? "<div class='gfield_date_day ginput_container ginput_container_date' id='{$field_id}_2_container'>
                                            <label for='{$field_id}_2' class='{$day_sub_label_class}'>{$day_sub_label}</label>
                                            <input type='{$date_input_type}' {$day_maxlength} name='input_{$id}[]' id='{$field_id}_2' value='{$day_value}' {$tabindex} {$disabled_text} {$day_aria_attributes} {$day_placeholder_attribute} {$day_html5_attributes}/>
                                        </div>"
								: "<div class='gfield_date_day ginput_container ginput_container_date' id='{$field_id}_2_container'>
                                            <input type='{$date_input_type}' {$day_maxlength} name='input_{$id}[]' id='{$field_id}_2' value='{$day_value}' {$tabindex} {$disabled_text} {$day_aria_attributes} {$day_placeholder_attribute} {$day_html5_attributes}/>
                                            <label for='{$field_id}_2' class='{$day_sub_label_class}'>{$day_sub_label}</label>
                                        </div>";

							$tabindex = $this->get_tabindex();

							$field_str .= $is_sub_label_above
								? "<div class='gfield_date_year ginput_container ginput_container_date' id='{$field_id}_3_container'>
                                            <label for='{$field_id}_3' class='{$year_sub_label_class}'>{$year_sub_label}</label>
                                            <input type='{$date_input_type}' {$year_maxlength} name='input_{$id}[]' id='{$field_id}_3' value='{$year_value}' {$tabindex} {$disabled_text} {$year_aria_attributes} {$year_placeholder_attribute} {$year_min_attribute} {$year_max_attribute} {$year_step_attribute}/>
                                       </div>
                                   {$clear_multi_div_close}"
								: "<div class='gfield_date_year ginput_container ginput_container_date' id='{$field_id}_3_container'>
                                            <input type='{$date_input_type}' {$year_maxlength} name='input_{$id}[]' id='{$field_id}_3' value='{$year_value}' {$tabindex} {$disabled_text} {$year_aria_attributes} {$year_placeholder_attribute} {$year_min_attribute} {$year_max_attribute} {$year_step_attribute}/>
                                            <label for='{$field_id}_3' class='{$year_sub_label_class}'>{$year_sub_label}</label>
                                       </div>
                                   {$clear_multi_div_close}";
						}

						break;
				}

				return "<div id='{$field_id}' class='ginput_container ginput_complex'>$field_str</div>";
			} else {
				$picker_value = esc_attr( GFCommon::date_display( $picker_value, $format ) );
				$icon_class   = $this->calendarIconType == 'none' ? 'datepicker_no_icon gdatepicker-no-icon' : 'datepicker_with_icon gdatepicker_with_icon';
				$icon_url     = empty( $this->calendarIconUrl ) ? GFCommon::get_base_url() . '/images/datepicker/datepicker.svg' : $this->calendarIconUrl;
				$icon_url     = esc_url( $icon_url );
				$tabindex     = $this->get_tabindex();

				$required_attribute     = $this->isRequired ? 'aria-required="true"' : '';
				$invalid_attribute      = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
				$describedby_attribute  = $this->get_aria_describedby( array( "{$field_id}_date_format" ) );

				return "<div class='ginput_container ginput_container_date'>
                            <input name='input_{$id}' id='{$field_id}' type='text' value='{$picker_value}' class='datepicker {$format} {$icon_class}' {$tabindex} {$disabled_text} {$date_picker_placeholder} {$describedby_attribute} {$invalid_attribute} {$required_attribute}/>
                            <span id='{$field_id}_date_format' class='screen-reader-text'>{$date_format_sr_text}</span>
                        </div>
                        <input type='hidden' id='gforms_calendar_icon_$field_id' class='gform_hidden' value='$icon_url'/>";
			}
		}
	}

	/**
	 * Get field label class.
	 *
	 * @since unknown
	 * @since 2.5     Added `screen-reader-text` if the label hasn't been set; added `gfield_label_before_complex` if it is datefield.
	 *
	 * @return string
	 */
	public function get_field_label_class() {
		$class = 'gfield_label';

		// Added `screen-reader-text` if the label hasn't been set.
		$class .= ( rgblank( $this->label ) ) ? ' screen-reader-text' : '';

		// Added `gfield_label_before_complex` if it is datefield.
		$class .= $this->dateType === 'datefield' ? ' gfield_label_before_complex' : '';

		return $class;
	}

	public function get_value_default() {

		$value = parent::get_value_default();

		if ( is_array( $this->inputs ) ) {
			$value = $this->get_date_array_by_format( $value );
		}

		return $value;
	}

	/**
	 * The default value for mulit-input date fields will always be an array in mdy order
	 * this code will alter the order of the values to the date format of the field
	 */
	public function get_date_array_by_format( $value ) {
		$format   = empty( $this->dateFormat ) ? 'mdy' : esc_attr( $this->dateFormat );
		$position = substr( $format, 0, 3 );
		$date     = array_combine( array( 'm', 'd', 'y' ), $value );            // takes our numerical array and converts it to an associative array
		$value    = array_merge( array_flip( str_split( $position ) ), $date ); // uses the mdy position as the array keys and creates a new array in the desired order

		return $value;
	}

	public function checkdate( $month, $day, $year ) {
		if ( empty( $month ) || ! is_numeric( $month ) || empty( $day ) || ! is_numeric( $day ) || empty( $year ) || ! is_numeric( $year ) || strlen( $year ) != 4 ) {
			return false;
		}

		return checkdate( $month, $day, $year );
	}

	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		return GFCommon::date_display( $value, $this->dateFormat );
	}


	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {

		return GFCommon::date_display( $value, $this->dateFormat, $this->get_output_date_format() );
	}

	/**
	 * Gets merge tag values.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFCommon::date_display()
	 * @uses GF_Field_Date::$dateFormat
	 *
	 * @param array|string $value      The value of the input.
	 * @param string       $input_id   The input ID to use.
	 * @param array        $entry      The Entry Object.
	 * @param array        $form       The Form Object
	 * @param string       $modifier   The modifier passed.
	 * @param array|string $raw_value  The raw value of the input.
	 * @param bool         $url_encode If the result should be URL encoded.
	 * @param bool         $esc_html   If the HTML should be escaped.
	 * @param string       $format     The format that the value should be.
	 * @param bool         $nl2br      If the nl2br function should be used.
	 *
	 * @return string The processed merge tag.
	 */
	public function get_value_merge_tag( $value, $input_id, $entry, $form, $modifier, $raw_value, $url_encode, $esc_html, $format, $nl2br ) {

		return GFCommon::date_display( $value, $this->dateFormat, $this->get_output_date_format() );
	}

	/**
	 * Returns the date format to use when outputting the entry value on the detail page and when merge tags are processed.
	 *
	 * @since 2.4
	 *
	 * @return string
	 */
	public function get_output_date_format() {
		$modifiers = $this->get_modifiers();
		if ( ! empty( $modifiers ) ) {
			$valid_modifiers = array(
				'year',
				'month',
				'day',
				'ymd',
				'ymd_dash',
				'ymd_dot',
				'ymd_slash',
				'mdy',
				'mdy_dash',
				'mdy_dot',
				'mdy_slash',
				'dmy',
				'dmy_dash',
				'dmy_dot',
				'dmy_slash',
			);

			foreach ( $modifiers as $modifier ) {
				if ( in_array( $modifier, $valid_modifiers ) ) {
					return $modifier;
				}
			}
		}

		return $this->dateFormat;
	}

	/**
	 * Returns a JS script to be rendered in the front end of the form.
	 *
	 * @param array $form The Form Object
	 *
	 * @return string Returns a JS script to be processed in the front end.
	 */
	public function get_form_inline_script_on_page_render( $form ) {

		//Only return merge tag script if form supports JS merge tags
		if ( ! GFFormDisplay::has_js_merge_tag( $form ) ) {
			return '';
		}

		return "gform.addFilter( 'gform_value_merge_tag_{$form['id']}_{$this->id}', function( value, input, modifier ) { if( modifier === 'label' ) { return false; } return input.length == 1 ? input.val() : jQuery(input[0]).val() + '/' + jQuery(input[1]).val() + '/' + jQuery(input[2]).val(); } );";

	}

	/**
	 * Generates month dropdown markup.
	 *
	 * @since unknown
	 * @since 2.5                     Add param $aria_attributes.
	 *
	 * @param string $name            Field name.
	 * @param string $id              Field ID.
	 * @param string $selected_value  Selected month.
	 * @param string $tabindex        Tabindex attribute.
	 * @param string $disabled_text   Disabled attribute.
	 * @param string $placeholder     Placeholder value.
	 * @param string $aria_attributes Aria-describedby, aria-required and aria-invalid attributes.
	 *
	 * @return string
	 */
	private function get_month_dropdown( $name = '', $id = '', $selected_value = '', $tabindex = '', $disabled_text = '', $placeholder = '', $aria_attributes = '' ) {
		if ( $placeholder == '' ) {
			$placeholder = esc_html__( 'Month', 'gravityforms' );
		}

		return $this->get_number_dropdown( $name, $id, $selected_value, $tabindex, $disabled_text, $placeholder, 1, 12, $aria_attributes );
	}

	/**
	 * Generates day dropdown markup.
	 *
	 * @since unknown
	 * @since 2.5                     Add param $aria_attributes.
	 *
	 * @param string $name            Field name.
	 * @param string $id              Field ID.
	 * @param string $selected_value  Selected day.
	 * @param string $tabindex        Tabindex attribute.
	 * @param string $disabled_text   Disabled attribute.
	 * @param string $placeholder     Placeholder value.
	 * @param string $aria_attributes Aria-describedby, aria-required and aria-invalid attributes.
	 *
	 * @return string
	 */
	private function get_day_dropdown( $name = '', $id = '', $selected_value = '', $tabindex = '', $disabled_text = '', $placeholder = '', $aria_attributes = '' ) {
		if ( $placeholder == '' ) {
			$placeholder = esc_html__( 'Day', 'gravityforms' );
		}

		return $this->get_number_dropdown( $name, $id, $selected_value, $tabindex, $disabled_text, $placeholder, 1, 31, $aria_attributes );
	}

	/**
	 * Generates year dropdown markup.
	 *
	 * @since unknown
	 * @since 2.5                     Add param $aria_attributes.
	 *
	 * @param string $name            Field name.
	 * @param string $id              Field ID.
	 * @param string $selected_value  Selected year.
	 * @param string $tabindex        Tabindex attribute.
	 * @param string $disabled_text   Disabled attribute.
	 * @param string $placeholder     Placeholder value.
	 * @param string $aria_attributes Aria-describedby, aria-required and aria-invalid attributes.
	 *
	 * @return string
	 */
	private function get_year_dropdown( $name, $id, $selected_value, $tabindex, $disabled_text, $placeholder, $form, $aria_attributes = '' ) {
		$name           = ( is_string( $name ) ) ? $name : '';
		$id             = ( is_string( $id ) ) ? $id : '';
		$selected_value = ( is_string( $selected_value ) ) ? $selected_value : '';
		$tabindex       = ( is_string( $tabindex ) ) ? $tabindex : '';
		$disabled_text  = ( is_string( $disabled_text ) ) ? $disabled_text : '';
		$placeholder    = ( is_string( $placeholder ) ) ? $placeholder : '';

		if ( $placeholder == '' ) {
			$placeholder = esc_html__( 'Year', 'gravityforms' );
		}
		$year_min = apply_filters( 'gform_date_min_year', '1920', $form, $this );
		$year_max = apply_filters( 'gform_date_max_year', date( 'Y' ) + 1, $form, $this );

		return $this->get_number_dropdown( $name, $id, $selected_value, $tabindex, $disabled_text, $placeholder, $year_max, $year_min, $aria_attributes );
	}

	/**
	 * Generates the markup for a dropdown field that has a range of numbers as values.
	 *
	 * @since unknown
	 * @since 2.5                     Add param $aria_attributes.
	 *
	 * @param string $name            Field name.
	 * @param string $id              Field ID.
	 * @param string $selected_value  Selected value.
	 * @param string $tabindex        Tabindex attribute.
	 * @param string $disabled_text   Disabled attribute.
	 * @param string $placeholder     Placeholder value.
	 * @param string $aria_attributes Aria-describedby, aria-required and aria-invalid attributes.
	 *
	 * @return string
	 */
	private function get_number_dropdown( $name, $id, $selected_value, $tabindex, $disabled_text, $placeholder, $start_number, $end_number, $aria_attributes = '' ) {
		$str = "<select name='{$name}' id='{$id}' {$tabindex} {$disabled_text} {$aria_attributes}>";
		if ( $placeholder !== false ) {
			$str .= "<option value=''>{$placeholder}</option>";
		}

		$increment = $start_number < $end_number ? 1 : - 1;

		for ( $i = $start_number; $i != ( $end_number + $increment ); $i += $increment ) {
			$selected = intval( $i ) == intval( $selected_value ) ? "selected='selected'" : '';
			$str .= "<option value='{$i}' {$selected}>{$i}</option>";
		}
		$str .= '</select>';

		return $str;
	}

	/**
	 * Helper method to get the date format by type.
	 *
	 * @since 2.5
	 *
	 * @param string $type  The returned value type. Can be 'label' or 'screen_reader_text'.
	 *
	 * @return string
	 */
	private function get_date_format( $type = 'label' ) {
		$format = empty( $this->dateFormat ) ? 'mdy' : $this->dateFormat;

		switch ( $format ) {
			case 'mdy':
				if ( $type === 'label' ) {
					$format = esc_attr__( 'mm/dd/yyyy', 'gravityforms' );
				} else {
					$format = esc_attr__( 'MM slash DD slash YYYY', 'gravityforms' );
				}
				break;
			case 'dmy':
				if ( $type === 'label' ) {
					$format = esc_attr__( 'dd/mm/yyyy', 'gravityforms' );
				} else {
					$format = esc_attr__( 'DD slash MM slash YYYY', 'gravityforms' );
				}
				break;
			case 'dmy_dash':
				if ( $type === 'label' ) {
					$format = esc_attr__( 'dd-mm-yyyy', 'gravityforms' );
				} else {
					$format = esc_attr__( 'DD dash MM dash YYYY', 'gravityforms' );
				}
				break;
			case 'dmy_dot':
				if ( $type === 'label' ) {
					$format = esc_attr__( 'dd.mm.yyyy', 'gravityforms' );
				} else {
					$format = esc_attr__( 'DD dot MM dot YYYY', 'gravityforms' );
				}
				break;
			case 'ymd_slash':
				if ( $type === 'label' ) {
					$format = esc_attr__( 'yyyy/mm/dd', 'gravityforms' );
				} else {
					$format = esc_attr__( 'YYYY slash MM slash DD', 'gravityforms' );
				}
				break;
			case 'ymd_dash':
				if ( $type === 'label' ) {
					$format = esc_attr__( 'yyyy-mm-dd', 'gravityforms' );
				} else {
					$format = esc_attr__( 'YYYY dash MM dash DD', 'gravityforms' );
				}
				break;
			case 'ymd_dot':
				if ( $type === 'label' ) {
					$format = esc_attr__( 'yyyy.mm.dd', 'gravityforms' );
				} else {
					$format = esc_attr__( 'YYYY dot MM dot DD', 'gravityforms' );
				}
				break;
		}

		return $this->is_form_editor() ? '<span>' . $format . '</span>' : $format;
	}

	/**
	 * Helper method to get the default date format for an input.
	 *
	 * @since 2.5
	 *
	 * @param array|string $input The input object or the input id.
	 *
	 * @return string
	 */
	private function get_input_date_format( $input ) {
		// If it's a datepicker, in the layout editor we still render a hidden date field.
		if ( ! rgar( $input, 'id' ) ) {
			$input_id = $input;
		} else {
			$input_id = $input['id'];
		}

		switch ( $input_id ) {
			case $this->id . '.1':
				$format = esc_html( _x( 'MM', 'Abbreviation: Month', 'gravityforms' ) );
				break;
			case $this->id . '.2':
				$format = esc_html( _x( 'DD', 'Abbreviation: Day', 'gravityforms' ) );
				break;
			default:
				$format = esc_html( _x( 'YYYY', 'Abbreviation: Year', 'gravityforms' ) );
		}

		return $format;
	}

	/**
	 * Return the custom label for an input.
	 *
	 * Theoretically the label is for what to fill out and the placeholder is for how to fill it out.
	 *
	 * @since 2.5
	 *
	 * @param array|string $input The input object or the input id.
	 *
	 * @return string
	 */
	public function get_input_label( $input ) {

		$sub_label = parent::get_input_label( $input );

		// Return the custom label if it's set.
		if ( ! empty( $sub_label ) ) {
			return $sub_label;
		}

		$placeholder_value = $this->get_input_placeholder_value( $input );
		$format            = $this->get_input_date_format( $input );

		if ( rgar( $input, 'placeholder' ) && $placeholder_value !== $format ) {
			// The placeholder is date format by default.
			// Only update sub-label to the format if placeholder is something else.
			$sub_label = $format;
		} else {
			// If it's a datepicker, in the layout editor we still render a hidden date field.
			if ( ! rgar( $input, 'id' ) ) {
				$input_id = $input;
			} else {
				$input_id = $input['id'];
			}

			switch ( $input_id ) {
				case $this->id . '.1':
					$sub_label = esc_html__( 'Month', 'gravityforms' );
					break;
				case $this->id . '.2':
					$sub_label = esc_html__( 'Day', 'gravityforms' );
					break;
				default:
					$sub_label = esc_html__( 'Year', 'gravityforms' );
			}
		}

		return $sub_label;

	}

	/**
	 * When no placeholder is set, use the date format as the placeholder.
	 *
	 * @since 2.5
	 *
	 * @param array $input The input object.
	 *
	 * @return string
	 */
	public function get_input_placeholder_value( $input ) {
		if ( rgar( $input, 'placeholder' ) === '' ) {
			return $this->get_input_date_format( $input );
		}

		return parent::get_input_placeholder_value( $input );
	}

	/**
	 * If the field placeholder property has a value return the input placeholder attribute.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_field_placeholder_attribute() {
		if ( $this->dateType === 'datepicker' && empty( $this->placeholder ) ) {
			$format = $this->is_form_editor() ? wp_strip_all_tags( $this->get_date_format() ) : $this->get_date_format();

			return sprintf( "placeholder='%s'", esc_attr( $format ) );
		}

		return parent::get_field_placeholder_attribute();
	}

	/**
	 * Returns the value to save in the entry.
	 *
	 * @param string $value
	 * @param array $form
	 * @param string $input_name
	 * @param int $lead_id
	 * @param array $lead
	 *
	 * @return string
	 */
	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		// if $value is a default value and also an array, it will be an associative array; to be safe, let's convert all array $value to numeric
		if ( is_array( $value ) ) {
			$value = array_values( $value );
		}

		$value = GFFormsModel::prepare_date( $this->dateFormat, $value );
		$value = $this->sanitize_entry_value( $value, $form['id'] );

		return $value;
	}

	public function get_entry_inputs() {
		return null;
	}

	public function sanitize_settings() {
		parent::sanitize_settings();
		$this->calendarIconType = wp_strip_all_tags( $this->calendarIconType );
		$this->calendarIconUrl  = wp_strip_all_tags( $this->calendarIconUrl );
		if ( $this->dateFormat && ! in_array( $this->dateFormat, array(	'mdy', 'dmy', 'dmy_dash', 'dmy_dot', 'ymd_slash', 'ymd_dash', 'ymd_dot' ) ) ) {
			$this->dateFormat = 'mdy';
		}
	}

	/**
	 * Removes the "for" attribute in the field label. Inputs are only allowed one label (a11y) and the inputs already have labels.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @param array $form The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_first_input_id( $form ) {
		return in_array( $this->dateType, array( 'datefield', 'datedropdown' ) ) ? '' : parent::get_first_input_id( $form ) ;
	}

	// # FIELD FILTER UI HELPERS ---------------------------------------------------------------------------------------

	/**
	 * Returns the filter settings for the current field.
	 *
	 * @since 2.4
	 *
	 * @return array
	 */
	public function get_filter_settings() {
		$filter_settings                = parent::get_filter_settings();
		$filter_settings['placeholder'] = esc_html__( 'yyyy-mm-dd', 'gravityforms' );
		$filter_settings['cssClass']    = 'datepicker ymd_dash';

		return $filter_settings;
	}

	/**
	 * Upgrades inputs, if needed.
	 *
	 * @since  2.5.7
	 * @access public
	 * @see    GF_Field::post_convert_field()
	 *
	 * @uses GF_Field::post_convert_field()
	 * @uses GF_Field_Date::maybe_upgrade_inputs()
	 *
	 * @return void
	 */
	public function post_convert_field() {
		parent::post_convert_field();
		$this->maybe_update_inputs();
	}

	/**
	 * The datefield and datedropdown date field input types can wind up
	 * in a state where the field's inputs are not set.
	 * This performs a check for the existence of the necessary inputs
	 * and updates the field to have them if they do not.
	 *
	 * @since 2.5.7
	 */
	public function maybe_update_inputs() {
		$inputs = $this->inputs;

		if ( ! $this->is_value_submission_array() ) {
			return;
		}

		if ( ! empty( $inputs ) && is_array( $inputs ) ) {
			return;
		}

		$inputs = array(
			array(
				'id' => "{$this->id}.1",
				'label' => esc_html__( 'Month', 'gravityforms' ),
				'name' => ''
			),
			array(
				'id' => "{$this->id}.2",
				'label' => esc_html__( 'Day', 'gravityforms' ),
				'name' => ''
			),
			array(
				'id' => "{$this->id}.3",
				'label' => esc_html__( 'Year', 'gravityforms' ),
				'name' => ''
			)
		);

		$this->inputs = $inputs;
	}
}

GF_Fields::register( new GF_Field_Date() );
