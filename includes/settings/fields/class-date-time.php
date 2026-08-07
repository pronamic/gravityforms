<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use GFCommon;
use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

// Load base class.
require_once 'class-select.php';
require_once 'class-text.php';

class Date_Time extends Base {

	/**
	 * Field type.
	 *
	 * @since 2.5
	 *
	 * @var string
	 */
	public $type = 'date_time';

	/**
	 * Child inputs.
	 *
	 * @since 2.5
	 *
	 * @var Base[]
	 */
	public $inputs = array();

	/**
	 * Initialize Date Time field.
	 *
	 * @since 2.5
	 *
	 * @param array                                $props    Field properties.
	 * @param \Gravity_Forms\Gravity_Forms\Settings\Settings $settings Settings instance.
	 */
	public function __construct( $props, $settings ) {

		parent::__construct( $props, $settings );

		// Prevent description from showing up on all sub-fields.
		unset( $props['description'] );

		// Prepare Date input.
		$this->inputs['date']           = $props;
		$this->inputs['date']['type']   = 'text';
		$this->inputs['date']['class']  = 'gform-datepicker mdy';
		$this->inputs['date']['name']  .= '[date]';

		// Prepare hours as choices.
		$hour_choices = array();
		for ( $i = 1; $i <= 12; $i++ ) {
			$hour_choices[] = array( 'label' => $i, 'value' => $i );
		}

		// Prepare hour drop down.
		$this->inputs['hour']            = $props;
		$this->inputs['hour']['type']    = 'select';
		$this->inputs['hour']['name']    .= '[hour]';
		$this->inputs['hour']['choices'] = $hour_choices;

		// Prepare minutes as choices.
		$minute_choices = array();
		for ( $i = 0; $i < 60; $i++ ) {
			$minute_choices[] = array( 'label' => sprintf( '%02d', $i ), 'value' => sprintf( '%d', $i ) );
		}

		// Prepare minute drop down.
		$this->inputs['minute']            = $props;
		$this->inputs['minute']['type']    = 'select';
		$this->inputs['minute']['name']    .= '[minute]';
		$this->inputs['minute']['choices'] = $minute_choices;

		// Prepare AM/PM drop down.
		$this->inputs['ampm']            = $props;
		$this->inputs['ampm']['type']    = 'select';
		$this->inputs['ampm']['name']    .= '[ampm]';
		$this->inputs['ampm']['choices'] = array(
			array( 'label' => 'AM', 'value' => 'am' ),
			array( 'label' => 'PM', 'value' => 'pm' ),
		);

		/**
		 * Prepare input fields.
		 *
		 * @var array $input
		 */
		foreach ( $this->inputs as &$input ) {
			$input = Fields::create( $input, $this->settings );
		}

	}





	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.5
	 *
	 * @return string
	 */
	public function markup() {
		$html = $this->get_description();

		$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

		$date_id   = $this->inputs['date']->name;
		$button    = "<button type='button' id='datepicker_toggle_{$date_id}' class='gform-datepicker-toggle gform-datepicker-toggle--default accCalendar aria-date-picker gform-button gform-button--simple' aria-expanded='false' aria-controls='_gform_setting_{$date_id}'>
							<span class='gform-datepicker-toggle-icon gform-datepicker-toggle-icon--default gform-icon gform-icon--date' aria-hidden='true'></span>
						</button>";

		$date_markup = '<div class="gform-settings-datepicker-wrapper">' . $this->inputs['date']->markup() . $button . "</div>";

		// Display Date input, Time drop downs.
		$html .= sprintf(
			'%s %s<span class="gform-settings-input__separator">:</span>%s %s',
			$date_markup,
			$this->inputs['hour']->markup(),
			$this->inputs['minute']->markup(),
			$this->inputs['ampm']->markup()
		);

		$html .= '</span>';

		// If field failed validation, add error icon.
		$html .= $this->get_error_icon();

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

		// If field is required and date is missing, set field error.
		if ( $this->required && rgempty( 'date', $value ) ) {
			$this->set_error( rgobj( $this, 'error_message' ) );
			return;
		}

		// Test for valid date.
		if ( wp_strip_all_tags( $value['date'] ) !== $value['date'] ) {
			$this->inputs['date']->set_error( esc_html__( 'Date must not include HTML tags.', 'gravityforms' ) );
			return;
		}

		// Test for valid hour.
		if ( (int) $value['hour'] < 1 || (int) $value['hour'] > 12 ) {
			$this->inputs['hour']->set_error( esc_html__( 'You must select a valid hour.', 'gravityforms' ) );
			return;
		}

		// Test for valid minute.
		if ( (int) $value['minute'] < 0 || (int) $value['minute'] > 59 ) {
			$this->inputs['minute']->set_error( esc_html__( 'You must select a valid minute.', 'gravityforms' ) );
			return;
		}

		// Test for valid AM/PM.
		if ( ! in_array( rgar( $value, 'ampm' ), array( 'am', 'pm' ) ) ) {
			$this->inputs['ampm']->set_error( esc_html__( 'You must select either am or pm.', 'gravityforms' ) );
			return;
		}

	}

}

Fields::register( 'date_time', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Date_Time' );
