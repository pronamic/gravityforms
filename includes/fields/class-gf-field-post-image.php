<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Post_Image extends GF_Field_Fileupload {

	public $type = 'post_image';

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

		if ( GFCommon::is_legacy_markup_enabled( $form ) ) {
			return parent::get_field_container_tag( $form );
		}

		return 'fieldset';

	}

	public function get_form_editor_field_title() {
		return esc_attr__( 'Post Image', 'gravityforms' );
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows users to upload an image that is added to the Media Library and Gallery for the post that is created.', 'gravityforms' );
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
		return 'gform-icon--post-image';
	}

	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'sub_label_placement_setting',
			'admin_label_setting',
			'post_image_setting',
			'rules_setting',
			'description_setting',
			'css_class_setting',
			'post_image_featured_image',
		);
	}

	public function get_field_input( $form, $value = '', $entry = null ) {

		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin = $is_entry_detail || $is_form_editor;

		$id       = (int) $this->id;
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$size         = $this->size;
		$class_suffix = $is_entry_detail ? '_admin' : '';
		$class        = $size . $class_suffix;
		$class        = esc_attr( $class );

		$disabled_text = $is_form_editor ? 'disabled="disabled"' : '';

		$alt         = esc_attr( rgget( $this->id . '.2', $value ) );
		$title       = esc_attr( rgget( $this->id . '.1', $value ) );
		$caption     = esc_attr( rgget( $this->id . '.4', $value ) );
		$description = esc_attr( rgget( $this->id . '.7', $value ) );

		//hiding meta fields for admin
		$hidden_style      = "style='display:none;'";
		$alt_style         = ! $this->displayAlt && $is_admin ? $hidden_style : '';
		$title_style       = ! $this->displayTitle && $is_admin ? $hidden_style : '';
		$caption_style     = ! $this->displayCaption && $is_admin ? $hidden_style : '';
		$description_style = ! $this->displayDescription && $is_admin ? $hidden_style : '';
		$file_label_style  = $is_admin && ! ( $this->displayAlt || $this->displayTitle || $this->displayCaption || $this->displayDescription ) ? $hidden_style : '';

		$form_sub_label_placement  = rgar( $form, 'subLabelPlacement' );
		$field_sub_label_placement = $this->subLabelPlacement;
		$is_sub_label_above        = $field_sub_label_placement == 'above' || ( empty( $field_sub_label_placement ) && $form_sub_label_placement == 'above' );

		// Prepare accepted extensions message.
		$allowed_extensions    = join( ',', GFCommon::clean_extensions( explode( ',', strtolower( $this->allowedExtensions ) ) ) );
		$extensions_message_id = 'extensions_message_' . $form_id . '_' . $id;
		$extensions_message    = sprintf(
			"<span id='%s'>%s</span>",
			$extensions_message_id,
			esc_attr( sprintf( __( 'Accepted file types: %s.', 'gravityforms' ), str_replace( ',', ', ', $allowed_extensions ) ) )
		);

		// Aria attributes.
		$required_attribute = $this->isRequired ? 'aria-required="true"' : '';
		$invalid_attribute  = $this->failed_validation ? 'aria-invalid="true"' : 'aria-invalid="false"';
		$aria_describedby   = $this->get_aria_describedby( array( $extensions_message_id ) );

		$hidden_class = $preview = '';
		$file_info    = RGFormsModel::get_temp_filename( $form_id, "input_{$id}" );
		if ( $file_info ) {
			$hidden_class     = ' gform_hidden';
			$file_label_style = $hidden_style;
			$preview          = "<span class='ginput_preview'><strong>" . esc_html( $file_info['uploaded_filename'] ) . "</strong> | <a href='javascript:;' onclick='gformDeleteUploadedFile({$form_id}, {$id});' onkeypress='gformDeleteUploadedFile({$form_id}, {$id});'>" . __( 'delete', 'gravityforms' ) . '</a></span>';
		}

		//in admin, render all meta fields to allow for immediate feedback, but hide the ones not selected
		$file_label = ( $is_admin || $this->displayAlt || $this->displayTitle || $this->displayCaption || $this->displayDescription ) ? "<label for='$field_id' class='ginput_post_image_file' $file_label_style>" . gf_apply_filters( array( 'gform_postimage_file', $form_id ), __( 'File', 'gravityforms' ), $form_id ) . '</label>' : '';

		$tabindex = $this->get_tabindex();

		if( $is_sub_label_above ){
			$upload = sprintf( "<span class='ginput_full$class_suffix'>$file_label{$preview}<input name='input_%d' id='%s' type='file' class='%s' $tabindex $required_attribute $invalid_attribute $aria_describedby %s/>{$extensions_message}</span>", $id, $field_id, esc_attr( $class . $hidden_class ), $disabled_text );
		} else {
			$upload = sprintf( "<span class='ginput_full$class_suffix'>{$preview}<input name='input_%d' id='%s' type='file' class='%s' $tabindex $required_attribute $invalid_attribute $aria_describedby %s/>{$extensions_message}$file_label</span>", $id, $field_id, esc_attr( $class . $hidden_class ), $disabled_text );
		}

		$tabindex = $this->get_tabindex();

		if( $is_sub_label_above ){
			$alt_field = $this->displayAlt || $is_admin ? sprintf( "<span class='ginput_full$class_suffix ginput_post_image_alt' $alt_style><label for='%s_2'>" . gf_apply_filters( array( 'gform_postimage_alt', $form_id ), __( 'Alternative Text', 'gravityforms' ), $form_id ) . "</label><input type='text' name='input_%d.2' id='%s_2' value='%s' $tabindex %s/></span>", $field_id, $id, $field_id, $alt, $disabled_text ) : '';
		} else {
			$alt_field = $this->displayAlt || $is_admin ? sprintf( "<span class='ginput_full$class_suffix ginput_post_image_alt' $alt_style><input type='text' name='input_%d.2' id='%s_2' value='%s' $tabindex %s/><label for='%s_2'>" . gf_apply_filters( array( 'gform_postimage_alt', $form_id ), __( 'Alternative Text', 'gravityforms' ), $form_id ) . '</label></span>', $id, $field_id, $alt, $disabled_text, $field_id ) : '';
		}

		$tabindex = $this->get_tabindex();

		if( $is_sub_label_above ){
			$title_field = $this->displayTitle || $is_admin ? sprintf( "<span class='ginput_full$class_suffix ginput_post_image_title' $title_style><label for='%s_1'>" . gf_apply_filters( array( 'gform_postimage_title', $form_id ), __( 'Title', 'gravityforms' ), $form_id ) . "</label><input type='text' name='input_%d.1' id='%s_1' value='%s' $tabindex %s/></span>", $field_id, $id, $field_id, $title, $disabled_text ) : '';
		} else {
			$title_field = $this->displayTitle || $is_admin ? sprintf( "<span class='ginput_full$class_suffix ginput_post_image_title' $title_style><input type='text' name='input_%d.1' id='%s_1' value='%s' $tabindex %s/><label for='%s_1'>" . gf_apply_filters( array( 'gform_postimage_title', $form_id ), __( 'Title', 'gravityforms' ), $form_id ) . '</label></span>', $id, $field_id, $title, $disabled_text, $field_id ) : '';
		}

		$tabindex = $this->get_tabindex();

		if( $is_sub_label_above ){
			$caption_field = $this->displayCaption || $is_admin ? sprintf( "<span class='ginput_full$class_suffix ginput_post_image_caption' $caption_style><label for='%s_4'>" . gf_apply_filters( array( 'gform_postimage_caption', $form_id ), __( 'Caption', 'gravityforms' ), $form_id ) . "</label><input type='text' name='input_%d.4' id='%s_4' value='%s' $tabindex %s/></span>", $field_id, $id, $field_id, $caption, $disabled_text ) : '';
		} else {
			$caption_field = $this->displayCaption || $is_admin ? sprintf( "<span class='ginput_full$class_suffix ginput_post_image_caption' $caption_style><input type='text' name='input_%d.4' id='%s_4' value='%s' $tabindex %s/><label for='%s_4'>" . gf_apply_filters( array( 'gform_postimage_caption', $form_id ), __( 'Caption', 'gravityforms' ), $form_id ) . '</label></span>', $id, $field_id, $caption, $disabled_text, $field_id ) : '';
		}

		$tabindex = $this->get_tabindex();

		if( $is_sub_label_above ){
			$description_field = $this->displayDescription || $is_admin ? sprintf( "<span class='ginput_full$class_suffix ginput_post_image_description' $description_style><label for='%s_7'>" . gf_apply_filters( array( 'gform_postimage_description', $form_id ), __( 'Description', 'gravityforms' ), $form_id ) . "</label><input type='text' name='input_%d.7' id='%s_7' value='%s' $tabindex %s/></span>", $field_id, $id, $field_id, $description, $disabled_text ) : '';
		} else {
			$description_field = $this->displayDescription || $is_admin ? sprintf( "<span class='ginput_full$class_suffix ginput_post_image_description' $description_style><input type='text' name='input_%d.7' id='%s_7' value='%s' $tabindex %s/><label for='%s_7'>" . gf_apply_filters( array( 'gform_postimage_description', $form_id ), __( 'Description', 'gravityforms' ), $form_id ) . '</label></span>', $id, $field_id, $description, $disabled_text, $field_id ) : '';
		}

		return "<div class='ginput_complex$class_suffix ginput_container ginput_container_post_image'>" . $upload . $alt_field . $title_field . $caption_field . $description_field . '</div>';
	}

	public function get_value_save_entry( $value, $form, $input_name, $lead_id, $lead ) {
		$form_id = $form['id'];
		$url     = $this->get_single_file_value( $form_id, $input_name );

		if ( empty( $url ) ) {
			return '';
		}

		if ( ! GFCommon::is_valid_url( $url ) ) {
			GFCommon::log_debug( __METHOD__ . '(): aborting; File URL invalid.' );

			return '';
		}

		$image_alt         = isset( $_POST["{$input_name}_2"] ) ? wp_strip_all_tags( $_POST["{$input_name}_2"] ) : '';
		$image_title       = isset( $_POST["{$input_name}_1"] ) ? wp_strip_all_tags( $_POST["{$input_name}_1"] ) : '';
		$image_caption     = isset( $_POST["{$input_name}_4"] ) ? wp_strip_all_tags( $_POST["{$input_name}_4"] ) : '';
		$image_description = isset( $_POST["{$input_name}_7"] ) ? wp_strip_all_tags( $_POST["{$input_name}_7"] ) : '';

		return $url . '|:|' . $image_title . '|:|' . $image_caption . '|:|' . $image_description . '|:|' . $image_alt;
	}

	public function get_value_entry_list( $value, $entry, $field_id, $columns, $form ) {
		list( $url, $title, $caption, $description, $alt ) = rgexplode( '|:|', $value, 5 );
		if ( ! empty( $url ) ) {
			// displaying thumbnail (if file is an image) or an icon based on the extension.
			$thumb = GFEntryList::get_icon_url( $url );
			$value = "<a href='" . esc_attr( $url ) . "' target='_blank' aria-label='" . esc_attr__( 'View the image', 'gravityforms' ) . "'><img src='$thumb' alt='$alt' /></a>";
		}
		return $value;
	}

	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		$ary         = explode( '|:|', $value );
		$url         = count( $ary ) > 0 ? $ary[0] : '';
		$title       = count( $ary ) > 1 ? $ary[1] : '';
		$caption     = count( $ary ) > 2 ? $ary[2] : '';
		$description = count( $ary ) > 3 ? $ary[3] : '';
		$alt         = count( $ary ) > 4 ? $ary[4] : '';

		if ( ! empty( $url ) ) {
			$url = str_replace( ' ', '%20', $url );

			switch ( $format ) {
				case 'text' :
					$value = $url;
					$value .= ! empty( $alt ) ? "\n\n" . $this->label . ' (' . __( 'Alternative Text', 'gravityforms' ) . '): ' . $description : '';
					$value .= ! empty( $title ) ? "\n\n" . $this->label . ' (' . __( 'Title', 'gravityforms' ) . '): ' . $title : '';
					$value .= ! empty( $caption ) ? "\n\n" . $this->label . ' (' . __( 'Caption', 'gravityforms' ) . '): ' . $caption : '';
					$value .= ! empty( $description ) ? "\n\n" . $this->label . ' (' . __( 'Description', 'gravityforms' ) . '): ' . $description : '';
					break;

				default :
					$value = "<a href='$url' target='_blank' aria-label='" . esc_attr__( 'View the image', 'gravityforms' ) . "'><img src='$url' width='100' alt='$alt' /></a>";
					$value .= ! empty( $alt ) ? '<div>' . esc_html__( 'Alternative Text', 'gravityforms' ) . ": $alt</div>" : '';
					$value .= ! empty( $title ) ? '<div>' . esc_html__( 'Title', 'gravityforms' ) . ": $title</div>" : '';
					$value .= ! empty( $caption ) ? '<div>' . esc_html__( 'Caption', 'gravityforms' ) . ": $caption</div>" : '';
					$value .= ! empty( $description ) ? '<div>' . esc_html__( 'Description', 'gravityforms' ) . ": $description</div>" : '';

					break;
			}
		}

		return $value;
	}

	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		$value[ $this->id . '.2' ] = $this->get_input_value_submission( 'input_' . $this->id . '_2', $get_from_post_global_var );
		$value[ $this->id . '.1' ] = $this->get_input_value_submission( 'input_' . $this->id . '_1', $get_from_post_global_var );
		$value[ $this->id . '.4' ] = $this->get_input_value_submission( 'input_' . $this->id . '_4', $get_from_post_global_var );
		$value[ $this->id . '.7' ] = $this->get_input_value_submission( 'input_' . $this->id . '_7', $get_from_post_global_var );

		return $value;
	}

	/**
	 * Gets merge tag values.
	 *
	 * @since  Unknown
	 * @since  2.5     Add alt text.
	 * @access public
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
		list( $url, $title, $caption, $description, $alt ) = array_pad( explode( '|:|', $value ), 5, false );
		switch ( $modifier ) {
			case 'alt' :
				return $alt;

			case 'title' :
				return $title;

			case 'caption' :
				return $caption;

			case 'description' :
				return $description;

			default :
				return str_replace( ' ', '%20', $url );
		}
	}

	/**
	 * Stores the physical file paths as extra entry meta data.
	 *
	 * @since 2.5.16
	 *
	 * @param array $form  The form object being saved.
	 * @param array $entry The entry object being saved.
	 *
	 * @return array The array that contains the file URLs and their corresponding physical paths.
	 */
	public function get_extra_entry_metadata( $form, $entry ) {

		// Leave only the file URL in the entry value so when parent saves the file path information the URL is a valid file URL.
		$ary                = explode( '|:|', $entry[ $this->id ] );
		$entry[ $this->id ] = rgar( $ary, 0 );

		return parent::get_extra_entry_metadata( $form, $entry );

	}
}



GF_Fields::register( new GF_Field_Post_Image() );
