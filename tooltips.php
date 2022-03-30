<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

global $__gf_tooltips;
$__gf_tooltips = array(
	'notification_send_to_email'                  => '<strong>' . __( 'Send To Email Address', 'gravityforms' ) . '</strong>' . __( 'Enter the email address you would like the notification email sent to.', 'gravityforms' ),
	'notification_autoformat'                     => '<strong>' . __( 'Disable Auto-Formatting', 'gravityforms' ) . '</strong>' . __( 'When enabled, auto-formatting will insert paragraph breaks automatically. Disable auto-formatting when using HTML to create email notification content.', 'gravityforms' ),
	'notification_send_to_routing'                => '<strong>' . __( 'Routing', 'gravityforms' ) . '</strong>' . __( 'Allows notification to be sent to different email addresses depending on values selected in the form.', 'gravityforms' ),
	'notification_from_email'                     => '<strong>' . __( 'From Email Address', 'gravityforms' ) . '</strong>' . __( 'Enter an authorized email address you would like the notification email sent from. To avoid deliverability issues, always use your site domain in the from email.', 'gravityforms' ),
	'notification_from_name'                      => '<strong>' . __( 'From Name', 'gravityforms' ) . '</strong>' . __( 'Enter the name you would like the notification email sent from, or select the name from available name fields.', 'gravityforms' ),
	'notification_reply_to'                       => '<strong>' . __( 'Reply To', 'gravityforms' ) . '</strong>' . __( 'Enter the email address you would like to be used as the reply to address for the notification email.', 'gravityforms' ),
	'notification_cc'                             => '<strong>' . __( 'Carbon Copy Addresses', 'gravityforms' ) . '</strong>' . __( 'Enter a comma separated list of email addresses you would like to receive a CC of the notification email.', 'gravityforms' ),
	'notification_bcc'                            => '<strong>' . __( 'Blind Carbon Copy Addresses', 'gravityforms' ) . '</strong>' . __( 'Enter a comma separated list of email addresses you would like to receive a BCC of the notification email.', 'gravityforms' ),
	'notification_attachments'                    => '<strong>' . __( 'Attachments', 'gravityforms' ) . '</strong>' . __( 'When enabled, any files uploaded to File Upload fields will be attached to the notification email.', 'gravityforms' ),
	'form_activity'                               => '<strong>' . __( 'Limit Form Activity', 'gravityforms' ) . '</strong>' . __( 'Limit the number of entries a form can generate and/or schedule a time period the form is active.', 'gravityforms' ),
	'form_limit_entries'                          => '<strong>' . __( 'Limit Number of Entries', 'gravityforms' ) . '</strong>' . __( 'Enter a number in the input box below to limit the number of entries allowed for this form. The form will become inactive when that number is reached.', 'gravityforms' ),
	'form_schedule_form'                          => '<strong>' . __( 'Schedule Form', 'gravityforms' ) . '</strong>' . __( 'Schedule a time period the form is active.', 'gravityforms' ),
	'form_honeypot'                               => '<strong>' . __( 'Enable Anti-spam honeypot', 'gravityforms' ) . '</strong>' . __( 'Enables the honeypot spam protection technique, which is an alternative to the reCAPTCHA field.', 'gravityforms' ),
	'form_animation'                              => '<strong>' . __( 'Enable Animation', 'gravityforms' ) . '</strong>' . __( 'Check this option to enable a sliding animation when displaying/hiding conditional logic fields.', 'gravityforms' ),
	'form_legacy_markup'                          => '<strong>' . __( 'Legacy Markup', 'gravityforms' ) . '</strong>' . __( 'Check this option to enable Gravity Forms\' legacy markup. This will hinder the accessibility of your form.', 'gravityforms' ),
	'form_title'                                  => '<strong>' . __( 'Form Title', 'gravityforms' ) . '</strong>' . __( 'Enter the title of your form.', 'gravityforms' ),
	'form_description'                            => '<strong>' . __( 'Form Description', 'gravityforms' ) . '</strong>' . __( 'Enter a description for your form. This may be used for user instructions.', 'gravityforms' ),
	'form_label_placement'                        => '<strong>' . __( 'Form Label Placement', 'gravityforms' ) . '</strong>' . __( 'Select the default label placement.  Labels can be top aligned above a field, left aligned to the left of a field, or right aligned to the right of a field. This is a global label placement setting.', 'gravityforms' ),
	'form_description_placement'                  => '<strong>' . __( 'Description Placement', 'gravityforms' ) . '</strong>' . __( 'Select the default description placement.  Descriptions can be placed above the field inputs or below the field inputs. This setting can be overridden in the appearance settings for each field.', 'gravityforms' ),
	'form_sub_label_placement'                    => '<strong>' . __( 'Sub-Label Placement', 'gravityforms' ) . '</strong>' . __( 'Select the default sub-label placement.  Sub-labels can be placed above the field inputs or below the field inputs. This setting can be overridden in the appearance settings for each field.', 'gravityforms' ),
	'form_required_indicator'                     => '<strong>' . __( 'Required Indicator', 'gravityforms' ) . '</strong>' . __( 'Select how you would like to indicate required fields.  You can choose either the default text or asterisk, or enter your own custom text.  If legacy markup is not enabled and you choose an asterisk, a legend will appear at the top of the form to explain the asterisk to users.' ),
	'form_button_text'                            => '<strong>' . __( 'Form Button Text', 'gravityforms' ) . '</strong>' . __( 'Enter the text you would like to appear on the form submit button.', 'gravityforms' ),
	'form_button_image'                           => '<strong>' . __( 'Form Button Image', 'gravityforms' ) . '</strong>' . __( 'Enter the path to an image you would like to use as the form submit button.', 'gravityforms' ),
	'form_css_class'                              => '<strong>' . __( 'Form CSS Class Name', 'gravityforms' ) . '</strong>' . __( 'Enter the CSS class name you would like to use in order to override the default styles for this form.', 'gravityforms' ),
	'form_field_add_icon_url'                     => '<strong>' . __( 'Add Icon URL', 'gravityforms' ) . '</strong>' . __( "Enter the URL of a custom image to replace the default 'add item' icon. A maximum size of 16px by 16px is recommended", 'gravityforms' ),
	'form_field_delete_icon_url'                  => '<strong>' . __( 'Delete Icon URL', 'gravityforms' ) . '</strong>' . __( "Enter the URL of a custom image to replace the default 'delete item' icon. A maximum size of 16px by 16px is recommended", 'gravityforms' ),
	'form_confirmation_message'                   => '<strong>' . __( 'Confirmation Message Text', 'gravityforms' ) . '</strong>' . __( 'Enter the text you would like the user to see on the confirmation page of this form.', 'gravityforms' ),
	'form_confirmation_autoformat'                => '<strong>' . __( 'Disable Auto-Formatting', 'gravityforms' ) . '</strong>' . __( 'When enabled, auto-formatting will insert paragraph breaks automatically. Disable auto-formatting when using HTML to create the confirmation content.', 'gravityforms' ),
	'form_redirect_to_webpage'                    => '<strong>' . __( 'Redirect Form to Page', 'gravityforms' ) . '</strong>' . __( 'Select the page you would like the user to be redirected to after they have submitted the form.', 'gravityforms' ),
	'form_redirect_to_url'                        => '<strong>' . __( 'Redirect Form to URL', 'gravityforms' ) . '</strong>' . __( 'Enter the URL of the webpage you would like the user to be redirected to after they have submitted the form.', 'gravityforms' ),
	                                                 /* Translators: %s: Link to article about query strings. */
	'form_redirect_querystring'                   => '<strong>' . __( 'Pass Data Via Query String', 'gravityforms' ) . '</strong>' . sprintf( __( "To pass field data to the confirmation page, build a Query String using the 'Insert Merge Tag' drop down. %s..more info on querystrings &raquo;%s", 'gravityforms' ), "<a href='https://en.wikipedia.org/wiki/Query_string' target='_blank'>", '</a>' ),
	'form_field_label'                            => '<strong>' . __( 'Field Label', 'gravityforms' ) . '</strong>' . __( 'Enter the label of the form field.  This is the field title the user will see when filling out the form.', 'gravityforms' ),
	'form_field_label_html'                       => '<strong>' . __( 'Field Label', 'gravityforms' ) . '</strong>' . __( 'Enter the label for this HTML block. It will help you identify your HTML blocks in the form editor, but it will not be displayed on the form.', 'gravityforms' ),
	'form_field_disable_margins'                  => '<strong>' . __( 'Disable Default Margins', 'gravityforms' ) . '</strong>' . __( 'When enabled, margins are added to properly align the HTML content with other form fields.', 'gravityforms' ),
	'form_field_recaptcha_theme'                  => '<strong>' . __( 'reCAPTCHA Theme', 'gravityforms' ) . '</strong>' . __( 'Select the visual theme for the reCAPTCHA field from the available options to better match your site design.', 'gravityforms' ),
	'form_field_captcha_type'                     => '<strong>' . __( 'CAPTCHA Type', 'gravityforms' ) . '</strong>' . __( 'Select the type of CAPTCHA you would like to use.', 'gravityforms' ),
	'form_field_recaptcha_badge'                  => '<strong>' . __( 'CAPTCHA Badge Position', 'gravityforms' ) . '</strong>' . __( "Select the position of the badge containing the links to Google's privacy policy and terms.", 'gravityforms' ),
	'form_field_custom_field_name'                => '<strong>' . __( 'Custom Field Name', 'gravityforms' ) . '</strong>' . __( 'Select the custom field name from available existing custom fields, or enter a new custom field name.', 'gravityforms' ),
	'form_field_type'                             => '<strong>' . __( 'Field type', 'gravityforms' ) . '</strong>' . __( 'Select the type of field from the available form fields.', 'gravityforms' ),
	'form_field_maxlength'                        => '<strong>' . __( 'Maximum Characters', 'gravityforms' ) . '</strong>' . __( 'Enter the maximum number of characters that this field is allowed to have.', 'gravityforms' ),
	'form_field_maxrows'                          => '<strong>' . __( 'Maximum Rows', 'gravityforms' ) . '</strong>' . __( 'Enter the maximum number of rows that users are allowed to add.', 'gravityforms' ),
	'form_field_date_input_type'                  => '<strong>' . __( 'Date Input Type', 'gravityforms' ) . '</strong>' . __( 'Select the type of inputs you would like to use for the date field. Date Picker will let users select a date from a calendar. Date Field will let users free type the date.', 'gravityforms' ),
	'form_field_address_type'                     => '<strong>' . __( 'Address Type', 'gravityforms' ) . '</strong>' . __( 'Select the type of address you would like to use.', 'gravityforms' ),
	'form_field_address_default_state_us'         => '<strong>' . __( 'Default State', 'gravityforms' ) . '</strong>' . __( 'Select the state you would like to be selected by default when the form gets displayed.', 'gravityforms' ),
	'form_field_address_default_state_canadian'   => '<strong>' . __( 'Default Province', 'gravityforms' ) . '</strong>' . __( 'Select the province you would like to be selected by default when the form gets displayed.', 'gravityforms' ),
	'form_field_address_default_country'          => '<strong>' . __( 'Default Country', 'gravityforms' ) . '</strong>' . __( 'Select the country you would like to be selected by default when the form gets displayed.', 'gravityforms' ),
	'form_field_address_hide_country'             => '<strong>' . __( 'Hide Country', 'gravityforms' ) . '</strong>' . __( 'For addresses that only apply to one country, you can choose to not display the country drop down. Entries will still be recorded with the selected country.', 'gravityforms' ),
	'form_field_address_hide_address2'            => '<strong>' . __( 'Hide Address Line 2', 'gravityforms' ) . '</strong>' . __( 'Check this box to prevent the extra address input (Address Line 2) from being displayed in the form.', 'gravityforms' ),
	'form_field_address_hide_state_us'            => '<strong>' . __( 'Hide State Field', 'gravityforms' ) . '</strong>' . __( 'Check this box to prevent the State field from being displayed in the form.', 'gravityforms' ),
	'form_field_address_hide_state_canadian'      => '<strong>' . __( 'Hide Province Field', 'gravityforms' ) . '</strong>' . __( 'Check this box to prevent Province field from being displayed in the form.', 'gravityforms' ),
	'form_field_address_hide_state_international' => '<strong>' . __( 'Hide State/Province/Region', 'gravityforms' ) . '</strong>' . __( 'Check this box to prevent the State/Province/Region from being displayed in the form.', 'gravityforms' ),
	'form_field_name_format'                      => '<strong>' . __( 'Field Name Format', 'gravityforms' ) . '</strong>' . __( 'Select the format you would like to use for the Name field.  There are 3 options, Normal which includes First and Last Name, Extended which adds Prefix and Suffix, or Simple which is a single input field.', 'gravityforms' ),
	'form_field_number_format'                    => '<strong>' . __( 'Number Format', 'gravityforms' ) . '</strong>' . __( 'Select the format of numbers that are allowed in this field. You have the option to use a comma or a dot as the decimal separator.', 'gravityforms' ),
	'form_field_force_ssl'                        => '<strong>' . __( 'Force SSL', 'gravityforms' ) . '</strong>' . __( 'Check this box to prevent this field from being displayed in a non-secure page (i.e. not https://). It will redirect the page to the same URL, but starting with https:// instead. This option requires a properly configured SSL certificate.', 'gravityforms' ),
	'form_field_date_format'                      => '<strong>' . __( 'Field Date Format', 'gravityforms' ) . '</strong>' . __( 'Select the format you would like to use for the date input.', 'gravityforms' ),
	'form_field_time_format'                      => '<strong>' . __( 'Time Format', 'gravityforms' ) . '</strong>' . __( 'Select the format you would like to use for the time field.  Available options are 12 hour (i.e. 8:30 pm) and 24 hour (i.e. 20:30).', 'gravityforms' ),
	'form_field_fileupload_allowed_extensions'    => '<strong>' . __( 'Allowed File Extensions', 'gravityforms' ) . '</strong>' . __( 'Enter the allowed file extensions for file uploads.  This will limit the type of files a user may upload.', 'gravityforms' ),
	'form_field_multiple_files'                   => '<strong>' . __( 'Enable Multi-File Upload', 'gravityforms' ) . '</strong>' . __( 'Select this option to enable multiple files to be uploaded for this field.', 'gravityforms' ),
	'form_field_max_files'                        => '<strong>' . __( 'Maximum Number of Files', 'gravityforms' ) . '</strong>' . __( "Specify the maximum number of files that can be uploaded using this field. Leave blank for unlimited. Note that the actual number of files permitted may be limited by this server's specifications and configuration.", 'gravityforms' ),
	'form_field_max_file_size'                    => '<strong>' . __( 'Maximum File Size', 'gravityforms' ) . '</strong>' . __( 'Specify the maximum file size in megabytes allowed for each of the files.', 'gravityforms' ),
	'form_field_phone_format'                     => '<strong>' . __( 'Phone Number Format', 'gravityforms' ) . '</strong>' . __( 'Select the format you would like to use for the phone input.  Available options are domestic US/CANADA style phone number and international long format phone number.', 'gravityforms' ),
	'form_field_description'                      => '<strong>' . __( 'Field Description', 'gravityforms' ) . '</strong>' . __( 'Enter the description for the form field.  This will be displayed to the user and provide some direction on how the field should be filled out or selected.', 'gravityforms' ),
	'form_field_required'                         => '<strong>' . __( 'Required Field', 'gravityforms' ) . '</strong>' . __( 'Select this option to make the form field required.  A required field will prevent the form from being submitted if it is not filled out or selected.', 'gravityforms' ),
	'form_field_no_duplicate'                     => '<strong>' . __( 'No Duplicates', 'gravityforms' ) . '</strong>' . __( 'Select this option to limit user input to unique values only.  This will require that a value entered in a field does not currently exist in the entry database for that field.', 'gravityforms' ),
	'form_field_hide_label'                       => '<strong>' . __( 'Hide Field Label', 'gravityforms' ) . '</strong>' . __( 'Select this option to hide the field label in the form.', 'gravityforms' ),
	'form_field_number_range'                     => '<strong>' . __( 'Number Range', 'gravityforms' ) . '</strong>' . __( 'Enter the minimum and maximum values for this form field.  This will require that the value entered by the user must fall within this range.', 'gravityforms' ),
	'form_field_enable_calculation'               => '<strong>' . __( 'Enable Calculation', 'gravityforms' ) . '</strong>' . __( 'Enabling calculations will allow the value of this field to be dynamically calculated based on a mathematical formula.', 'gravityforms' ),
	'form_field_calculation_formula'              => '<strong>' . __( 'Formula', 'gravityforms' ) . '</strong>' . __( 'Specify a mathematical formula. The result of this formula will be dynamically populated as the value for this field.', 'gravityforms' ),
	'form_field_calculation_rounding'             => '<strong>' . __( 'Rounding', 'gravityforms' ) . '</strong>' . __( 'Specify how many decimal places the number should be rounded to.', 'gravityforms' ),
	'form_field_admin_label'                      => '<strong>' . __( 'Admin Label', 'gravityforms' ) . '</strong>' . __( 'Enter the admin label of the form field.  Entering a value in this field will override the Field Label when displayed in the Gravity Forms administration tool.', 'gravityforms' ),
	'form_field_sub_labels'                       => '<strong>' . __( 'Sub-Labels', 'gravityforms' ) . '</strong>' . __( 'Enter values in this setting to override the Sub-Label for each field.', 'gravityforms' ),
	'form_field_label_placement'                  => '<strong>' . __( 'Label Visibility', 'gravityforms' ) . '</strong>' . __( 'Select the label visibility for this field.  Labels can either inherit the form setting or be hidden.', 'gravityforms' ),
	'form_field_description_placement'            => '<strong>' . __( 'Description Placement', 'gravityforms' ) . '</strong>' . __( 'Select the description placement.  Descriptions can be placed above the field inputs or below the field inputs.', 'gravityforms' ),
	'form_field_sub_label_placement'              => '<strong>' . __( 'Sub-Label Placement', 'gravityforms' ) . '</strong>' . __( 'Select the sub-label placement.  Sub-labels can be placed above the field inputs or below the field inputs.', 'gravityforms' ),
	'form_field_size'                             => '<strong>' . __( 'Field Size', 'gravityforms' ) . '</strong>' . __( 'Select a form field size from the available options. This will set the width of the field. Please note: if using a paragraph field, the size applies only to the height of the field.', 'gravityforms' ),
	'form_field_name_fields'                      => '<strong>' . __( 'Name Fields', 'gravityforms' ) . '</strong>' . __( "Select the fields you'd like to use in this Name field and customize the Sub-Labels by entering new ones.", 'gravityforms' ),
	'form_field_name_prefix_choices'              => '<strong>' . __( 'Name Prefix Choices', 'gravityforms' ) . '</strong>' . __( 'Add Choices to this field. You can mark a choice as selected by default by using the radio buttons on the left.', 'gravityforms' ),
	'form_field_address_fields'                   => '<strong>' . __( 'Address Fields', 'gravityforms' ) . '</strong>' . __( "Select the fields you'd like to use in this Address Field and customize the Sub-Labels by entering new ones.", 'gravityforms' ),
	'form_field_default_value'                    => '<strong>' . __( 'Default Value', 'gravityforms' ) . '</strong>' . __( 'If you would like to pre-populate the value of a field, enter it here.', 'gravityforms' ),
	'form_field_default_input_values'             => '<strong>' . __( 'Default Values', 'gravityforms' ) . '</strong>' . __( 'If you would like to pre-populate the value of a field, enter it here.', 'gravityforms' ),
	'form_field_placeholder'                      => '<strong>' . __( 'Placeholder', 'gravityforms' ) . '</strong>' . __( 'The Placeholder will not be submitted along with the form. Use the Placeholder to give a hint at the expected value or format.', 'gravityforms' ),
	'form_field_input_placeholders'               => '<strong>' . __( 'Placeholders', 'gravityforms' ) . '</strong>' . __( 'Placeholders will not be submitted along with the form. Use Placeholders to give a hint at the expected value or format.', 'gravityforms' ),
	'form_field_enable_copy_values_option'        => '<strong>' . __( 'Use Values Submitted in a Different Field', 'gravityforms' ) . '</strong>' . __( 'Activate this option to allow users to skip this field and submit the values entered in the associated field. For example, this is useful for shipping and billing address fields.', 'gravityforms' ),
	'form_field_copy_values_option_label'         => '<strong>' . __( 'Option Label', 'gravityforms' ) . '</strong>' . __( 'Enter the label to be displayed next to the check box. For example, &quot;same as shipping address&quot;.', 'gravityforms' ),
	'form_field_copy_values_option_field'         => '<strong>' . __( 'Source Field', 'gravityforms' ) . '</strong>' . __( 'Select the field to be used as the source for the values for this field.', 'gravityforms' ),
	'form_field_copy_values_option_default'       => '<strong>' . __( 'Activated by Default', 'gravityforms' ) . '</strong>' . __( 'Select this setting to display the option as activated by default when the form first loads.', 'gravityforms' ),
	'form_field_autocomplete'                     => '<strong>' . __( 'Autocomplete Attribute', 'gravityforms' ) . '</strong>' . sprintf( __( 'Select this setting to let browsers help a user fill in a field with autocomplete.  You can enter a single autocomplete attribute or multiple attributes separated with a space.  Learn more about autocomplete in the %s accessibility documentation %s.', 'gravityforms' ), '<a href="https://docs.gravityforms.com/accessibility-for-developers/#autocomplete" target="_blank">', '</a>' ),
	'form_field_validation_message'               => '<strong>' . __( 'Validation Message', 'gravityforms' ) . '</strong>' . __( 'If you would like to override the default error validation for a field, enter it here.  This message will be displayed if there is an error with this field when the user submits the form.', 'gravityforms' ),
	'form_field_recaptcha_language'               => '<strong>' . __( 'reCAPTCHA Language', 'gravityforms' ) . '</strong>' . __( 'Select the language you would like to use for the reCAPTCHA display from the available options.', 'gravityforms' ),
	'form_field_css_class'                        => '<strong>' . __( 'CSS Class Name', 'gravityforms' ) . '</strong>' . __( 'Enter the CSS class name you would like to use in order to override the default styles for this field.', 'gravityforms' ),
	'form_field_visibility'                       => GFCommon::get_visibility_tooltip(),
	'form_field_choices'                          => '<strong>' . __( 'Field Choices', 'gravityforms' ) . '</strong>' . __( 'Define the choices for this field. If the field type supports it you will also be able to select the default choice(s) using a radio or checkbox located to the left of the choice.', 'gravityforms' ),
	'form_field_choice_values'                    => '<strong>' . __( 'Enable Choice Values', 'gravityforms' ) . '</strong>' . __( 'Check this option to specify a value for each choice. Choice values are not displayed to the user viewing the form, but are accessible to administrators when viewing the entry.', 'gravityforms' ),
	'form_field_conditional_logic'                => '<strong>' . __( 'Conditional Logic', 'gravityforms' ) . '</strong>' . __( 'Create rules to dynamically display or hide this field based on values from another field.', 'gravityforms' ),
	                                                 /* Translators: %s: Link to Chosen jQuery framework. */
	'form_field_enable_enhanced_ui'               => '<strong>' . __( 'Enable Enhanced UI', 'gravityforms' ) . '</strong>' . sprintf( __( "By selecting this option, the %s jQuery script will be applied to this field, enabling search capabilities to Drop Down fields and a more user-friendly interface for Multi Select fields.", 'gravityforms' ), "<a href='https://harvesthq.github.com/chosen/' target='_blank' title='Chosen'>Chosen</a>" ),
	'form_field_checkbox_label'                   => '<strong>' . __( 'Checkbox Text', 'gravityforms' ) . '</strong>' . __( 'Text of the consent checkbox.', 'gravityforms' ),
	'form_field_select_all_choices'               => '<strong>' . __( '"Select All" Choice', 'gravityforms' ) . '</strong>' . __( 'Check this option to add a "Select All" checkbox before the checkbox choices to allow users to check all the checkboxes with one click.', 'gravityforms' ),
	'form_field_other_choice'                     => '<strong>' . __( '"Other" Choice', 'gravityforms' ) . '</strong>' . __( 'Check this option to add a text input as the final choice of your radio button field. This allows the user to specify a value that is not a predefined choice.', 'gravityforms' ),
	'form_require_login'                         => '<strong>' . __( 'Require user to be logged in', 'gravityforms' ) . '</strong>' . __( 'Check this option to require a user to be logged in to view this form.', 'gravityforms' ),
	'form_require_login_message'                 => '<strong>' . __( 'Require Login Message', 'gravityforms' ) . '</strong>' . __( 'Enter a message to be displayed to users who are not logged in (shortcodes and HTML are supported).', 'gravityforms' ),
	'form_page_conditional_logic'                => '<strong>' . __( 'Page Conditional Logic', 'gravityforms' ) . '</strong>' . __( 'Create rules to dynamically display or hide this page based on values from another field.', 'gravityforms' ),
	'form_progress_indicator'                    => '<strong>' . __( 'Progress Indicator', 'gravityforms' ) . '</strong>' . __( 'Select which type of visual progress indicator you would like to display.  Progress Bar, Steps or None.', 'gravityforms' ),
	'form_percentage_style'                      => '<strong>' . __( 'Progress Bar Style', 'gravityforms' ) . '</strong>' . __( 'Select which progress bar style you would like to use.  Select custom to choose your own text and background color.', 'gravityforms' ),
	'form_page_names'                            => '<strong>' . __( 'Page Names', 'gravityforms' ) . '</strong>' . __( 'Name each of the pages on your form.  Page names are displayed with the selected progress indicator.', 'gravityforms' ),
	'next_button_text'                           => '<strong>' . __( 'Next Button Text', 'gravityforms' ) . '</strong>' . __( 'Enter the text you would like to appear on the page next button.', 'gravityforms' ),
	'next_button_image'                          => '<strong>' . __( 'Next Button Image', 'gravityforms' ) . '</strong>' . __( 'Enter the path to an image you would like to use as the page next button.', 'gravityforms' ),
	'previous_button_text'                       => '<strong>' . __( 'Previous Button Text', 'gravityforms' ) . '</strong>' . __( 'Enter the text you would like to appear on the page previous button.', 'gravityforms' ),
	'previous_button_image'                      => '<strong>' . __( 'Previous Button Image', 'gravityforms' ) . '</strong>' . __( 'Enter the path to an image you would like to use as the page previous button.', 'gravityforms' ),
	'form_nextbutton_conditional_logic'          => '<strong>' . __( 'Next Button Conditional Logic', 'gravityforms' ) . '</strong>' . __( "Create rules to dynamically display or hide the page's Next Button based on values from another field.", 'gravityforms' ),
	'form_button_conditional_logic'              => '<strong>' . __( 'Conditional Logic', 'gravityforms' ) . '</strong>' . __( 'Create rules to dynamically display or hide the submit button based on values from another field.', 'gravityforms' ),
	'form_field_post_category_selection'          => '<strong>' . __( 'Post Category', 'gravityforms' ) . '</strong>' . __( 'Select which categories are displayed. You can choose to display all of them or select individual ones.', 'gravityforms' ),
	'form_field_post_status'                      => '<strong>' . __( 'Post Status', 'gravityforms' ) . '</strong>' . __( 'Select the post status that will be used for the post that is created by the form entry.', 'gravityforms' ),
	'form_field_post_author'                      => '<strong>' . __( 'Post Author', 'gravityforms' ) . '</strong>' . __( 'Select the author that will be used for the post that is created by the form entry.', 'gravityforms' ),
	'form_field_post_format'                      => '<strong>' . __( 'Post Format', 'gravityforms' ) . '</strong>' . __( 'Select the post format that will be used for the post that is created by the form entry.', 'gravityforms' ),
	'form_field_post_content_template_enable'     => '<strong>' . __( 'Post Content Template', 'gravityforms' ) . '</strong>' . __( 'Check this option to format and insert merge tags into the Post Content.', 'gravityforms' ),
	'form_field_post_title_template_enable'       => '<strong>' . __( 'Post Title Template', 'gravityforms' ) . '</strong>' . __( 'Check this option to format and insert merge tags into the Post Title.', 'gravityforms' ),
	'form_field_post_category'                    => '<strong>' . __( 'Post Category', 'gravityforms' ) . '</strong>' . __( 'Select the category that will be used for the post that is created by the form entry.', 'gravityforms' ),
	'form_field_current_user_as_author'           => '<strong>' . __( 'Use Current User as Author', 'gravityforms' ) . '</strong>' . __( 'Selecting this option will set the post author to the WordPress user that submitted the form.', 'gravityforms' ),
	'form_field_image_meta'                       => '<strong>' . __( 'Image Meta', 'gravityforms' ) . '</strong>' . __( 'Select one or more image metadata field to be displayed along with the image upload field. They enable users to enter additional information about the uploaded image.', 'gravityforms' ),
	'form_field_featured_image'                   => '<strong>' . __( 'Set as Featured Image', 'gravityforms' ) . '</strong>' . __( "Check this option to set this image as the post's Featured Image.", 'gravityforms' ),
	'form_field_prepopulate'                      => '<strong>' . __( 'Incoming Field Data', 'gravityforms' ) . '</strong>' . __( 'Check this option to enable data to be passed to the form and pre-populate this field dynamically. Data can be passed via Query Strings, Shortcode and/or Hooks.', 'gravityforms' ),
	'form_field_content'                          => '<strong>' . __( 'Content', 'gravityforms' ) . '</strong>' . __( 'Enter the content (Text or HTML) to be displayed on the form.', 'gravityforms' ),
	'form_field_base_price'                       => '<strong>' . __( 'Base Price', 'gravityforms' ) . '</strong>' . __( 'Enter the base price for this product.', 'gravityforms' ),
	'form_field_disable_quantity'                 => '<strong>' . __( 'Disable Quantity', 'gravityforms' ) . '</strong>' . __( 'Disables the quantity field.  A quantity of 1 will be assumed or you can add a Quantity field to your form from the Pricing Fields.', 'gravityforms' ),
	'form_field_product'                          => '<strong>' . __( 'Product Field', 'gravityforms' ) . '</strong>' . __( 'Select which Product this field is tied to.', 'gravityforms' ),
	'form_field_mask'                             => '<strong>' . __( 'Input Mask', 'gravityforms' ) . '</strong>' . __( 'Input masks provide a visual guide allowing users to more easily enter data in a specific format such as dates and phone numbers.', 'gravityforms' ),
	'form_standard_fields'                        => '<strong>' . __( 'Standard Fields', 'gravityforms' ) . '</strong>' . __( 'Standard Fields provide basic form functionality.', 'gravityforms' ),
	'form_advanced_fields'                        => '<strong>' . __( 'Advanced Fields', 'gravityforms' ) . '</strong>' . __( 'Advanced Fields are for specific uses.  They enable advanced formatting of regularly used fields such as Name, Email, Address, etc.', 'gravityforms' ),
	'form_post_fields'                            => '<strong>' . __( 'Post Fields', 'gravityforms' ) . '</strong>' . __( 'Post Fields allow you to add fields to your form that create Post Drafts in WordPress from the submitted data.', 'gravityforms' ),
	'form_pricing_fields'                         => '<strong>' . __( 'Pricing Fields', 'gravityforms' ) . '</strong>' . __( 'Pricing fields allow you to add fields to your form that calculate pricing for selling goods and services.', 'gravityforms' ),
	'export_select_form'                          => '<strong>' . __( 'Export Selected Form', 'gravityforms' ) . '</strong>' . __( 'Select the form you would like to export entry data from. You may only export data from one form at a time.', 'gravityforms' ),
	'export_select_forms'                         => '<strong>' . __( 'Export Selected Forms', 'gravityforms' ) . '</strong>' . __( 'Select the forms you would like to export.', 'gravityforms' ),
	'export_conditional_logic'                    => '<strong>' . __( 'Conditional Logic', 'gravityforms' ) . '</strong>' . __( 'Filter the entries by adding conditions.', 'gravityforms' ),
	'export_select_fields'                        => '<strong>' . __( 'Export Selected Fields', 'gravityforms' ) . '</strong>' . __( 'Select the fields you would like to include in the export.', 'gravityforms' ),
	'export_date_range'                           => '<strong>' . __( 'Export Date Range', 'gravityforms' ) . '</strong>' . __( 'Select a date range. Setting a range will limit the export to entries submitted during that date range. If no range is set, all entries will be exported.', 'gravityforms' ),
	'import_select_file'                          => '<strong>' . __( 'Select Files', 'gravityforms' ) . '</strong>' . __( 'Click the file selection button to upload a Gravity Forms export file from your computer. Please make sure your file has the .json extension, and that it was generated by the Gravity Forms Export tool.', 'gravityforms' ),
	'settings_license_key'                        => '<strong>' . __( 'Settings License Key', 'gravityforms' ) . '</strong>' . __( 'Your Gravity Forms support license key is used to verify your support package, enable automatic updates and receive support.', 'gravityforms' ),
	'settings_output_css'                         => '<strong>' . __( 'Output CSS', 'gravityforms' ) . '</strong>' . __( 'Select yes or no to enable or disable CSS output.  Setting this to no will disable the standard Gravity Forms CSS from being included in your theme.', 'gravityforms' ),
	'settings_html5'                              => '<strong>' . __( 'Output HTML5', 'gravityforms' ) . '</strong>' . __( 'Select yes or no to enable or disable HTML5 output. Setting this to no will disable the standard Gravity Forms HTML5 form field output.', 'gravityforms' ),
	'settings_noconflict'                         => '<strong>' . __( 'No-Conflict Mode', 'gravityforms' ) . '</strong>' . __( 'Select On or Off to enable or disable no-conflict mode. Setting this to On will prevent extraneous scripts and styles from being printed on Gravity Forms admin pages, reducing conflicts with other plugins and themes.', 'gravityforms' ),
	'settings_recaptcha_public'                   => '<strong>' . __( 'reCAPTCHA Site Key', 'gravityforms' ) . '</strong>' . __( 'Enter your reCAPTCHA Site Key, if you do not have a key you can register for one at the provided link.  reCAPTCHA is a free service.', 'gravityforms' ),
	'settings_recaptcha_private'                  => '<strong>' . __( 'reCAPTCHA Secret Key', 'gravityforms' ) . '</strong>' . __( 'Enter your reCAPTCHA Secret Key, if you do not have a key you can register for one at the provided link.  reCAPTCHA is a free service.', 'gravityforms' ),
	'settings_recaptcha_type'                     => '<strong>' . __( 'reCAPTCHA Type', 'gravityforms' ) . '</strong>' . __( 'Select the type of reCAPTCHA you would like to use.', 'gravityforms' ),
	'settings_currency'                           => '<strong>' . __( 'Currency', 'gravityforms' ) . '</strong>' . __( 'Please select the currency for your location.  Currency is used for pricing fields and price calculations.', 'gravityforms' ),
	'settings_akismet'                            => '<strong>' . __( 'Akismet Integration', 'gravityforms' ) . '</strong>' . __( 'Protect your form entries from spam using Akismet.', 'gravityforms' ),
	'entries_conversion'                          => '<strong>' . __( 'Entries Conversion', 'gravityforms' ) . '</strong>' . __( 'Conversion is the percentage of form views that generated an entry. If a form was viewed twice, and one entry was generated, the conversion will be 50%.', 'gravityforms' ),
	'widget_tabindex'                             => '<strong>' . __( 'Tab Index Start Value', 'gravityforms' ) . '</strong>' . __( 'If you have other forms on the page (i.e. Comments Form), specify a higher tabindex start value so that your Gravity Form does not end up with the same tabindices as your other forms. To disable the tabindex, enter 0 (zero).', 'gravityforms' ),
	'notification_override_email'                 => '<strong>' . __( 'Override Notifications', 'gravityforms' ) . '</strong>' . __( 'Enter a comma separated list of email addresses you would like to receive the selected notification emails.', 'gravityforms' ),
	'form_percentage_confirmation_display'        => '<strong>' . __( 'Progress Bar Confirmation Display', 'gravityforms' ) . '</strong>' . __( 'Check this box if you would like the progress bar to display with the confirmation text.', 'gravityforms' ),
	'percentage_confirmation_page_name'           => '<strong>' . __( 'Progress Bar Completion Text', 'gravityforms' ) . '</strong>' . __( 'Enter text to display at the top of the progress bar.', 'gravityforms' ),
	'form_field_rich_text_editor'                 => '<strong>' . __( 'Use Rich Text Editor', 'gravityforms' ) . '</strong>' . __( 'Check this box if you would like to use the rich text editor for this field.', 'gravityforms' ),
	'personal_data_enable'                        => '<strong>' . __( 'Enable Personal Data Tools', 'gravityforms' ) . '</strong>' . __( 'Check this box if you would like to include data from this form when exporting or erasing personal data on this site.', 'gravityforms' ),
	'personal_data_identification'                => '<strong>' . __( 'Identification', 'gravityforms' ) . '</strong>' . __( 'Select the field which will be used to identify the owner of the personal data.', 'gravityforms' ),
	'personal_data_field_settings'                => '<strong>' . __( 'Field Settings', 'gravityforms' ) . '</strong>' . __( 'Select the fields which will be included when exporting or erasing personal data.', 'gravityforms' ),
	'personal_data_prevent_ip'                    => '<strong>' . __( 'IP Address', 'gravityforms' ) . '</strong>' . __( 'Check this box if you would like to prevent the IP address from being stored during form submission.', 'gravityforms' ),
	'personal_data_retention_policy'              => '<strong>' . __( 'Retention Policy', 'gravityforms' ) . '</strong>' . __( 'Use these settings to keep entries only as long as they are needed. Trash or delete entries automatically older than the specified number of days. The minimum number of days allowed is one. This is to ensure that all entry processing is complete before deleting/trashing. The number of days setting is a minimum, not an exact period of time. The trashing/deleting occurs during the daily cron task so some entries may appear to remain up to a day longer than expected.', 'gravityforms' ),
    'form_field_password_visibility_enable'       => '<strong>' . __( 'Password Visibility Toggle', 'gravityforms' ) . '</strong>' . __( 'Check this box to add a toggle allowing the user to see the password they are entering in.', 'gravityforms' ),
	'validation_summary'                          => '<strong>' . __( 'Validation Summary', 'gravityforms' ) . '</strong>' . __( 'Enable to show a summary that lists validation errors on top of the form.', 'gravityforms' ),
);

/**
 * Displays the tooltip
 *
 * @global $__gf_tooltips
 *
 * @param string $name      The name of the tooltip to be displayed
 * @param string $css_class Optional. The CSS class to apply toi the element. Defaults to empty string.
 * @param bool   $return    Optional. If the tooltip should be returned instead of output. Defaults to false (output)
 *
 * @return string
 */
function gform_tooltip( $name, $css_class = '', $return = false ) {
	global $__gf_tooltips; //declared as global to improve WPML performance

	$css_class     = empty( $css_class ) ? 'tooltip' : $css_class;
	/**
	 * Filters the tooltips available
	 *
	 * @param array $__gf_tooltips Array containing the available tooltips
	 */
	$__gf_tooltips = apply_filters( 'gform_tooltips', $__gf_tooltips );

	//AC: the $name parameter is a key when it has only one word. Maybe try to improve this later.
	$parameter_is_key = count( explode( ' ', $name ) ) == 1;

	$tooltip_text  = $parameter_is_key ? rgar( $__gf_tooltips, $name ) : $name;
	$tooltip_class = isset( $__gf_tooltips[ $name ] ) ? "tooltip_{$name}" : '';

	if ( empty( $tooltip_text ) ) {
		return '';
	}
	$tooltip = sprintf(
		'<button onclick="return false;" onkeypress="return false;" class="gf_tooltip %s %s" aria-label="%s">
			<i class="gform-icon gform-icon--question-mark" aria-hidden="true"></i>
		</button>',
		esc_attr( $css_class ),
		esc_attr( $tooltip_class ),
		esc_attr( $tooltip_text )
	);

	if ( $return ) {
		return $tooltip;
	} else {
		echo $tooltip;
	}
}
