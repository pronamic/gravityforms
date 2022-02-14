<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

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
		$this->inputs['date']         = $props;
		$this->inputs['date']['type'] = 'text';
		$this->inputs['date']['name'] .= '[date]';

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

		// Display Date input, Time drop downs.
		$html .= sprintf(
			'%s %s<span class="gform-settings-input__separator">:</span>%s %s',
			$this->inputs['date']->markup(),
			$this->inputs['hour']->markup(),
			$this->inputs['minute']->markup(),
			$this->inputs['ampm']->markup()
		);

		// Insert jQuery Datepicker script.
		$html .= sprintf(
			"<script type='text/javascript'>
				jQuery( function() {
					jQuery( 'input[name=\"%s_%s\"]' ).datepicker(
						{
							showOn: 'both',
							changeMonth: true,
							changeYear: true,
							buttonText: '<span class=\"screen-reader-text\">%s</span><svg width=\"18\" height=\"18\" fill=\"#9092B2\" xmlns=\"http://www.w3.org/2000/svg\"><path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M13.0909 1.6364V1.231C13.0909.5513 13.6357 0 14.3182 0c.6778 0 1.2273.5468 1.2273 1.2311v.4053h.8254c.8997 0 1.6291.7349 1.6291 1.6288v13.106C18 17.2707 17.2721 18 16.3709 18H1.6291C.7294 18 0 17.2651 0 16.3712V3.2652c0-.8996.728-1.6288 1.6291-1.6288h.8254V1.231C2.4545.5513 2.9993 0 3.6818 0c.6778 0 1.2273.5468 1.2273 1.2311v.4053h2.4545V1.231C7.3636.5513 7.9084 0 8.591 0c.6778 0 1.2273.5468 1.2273 1.2311v.4053h3.2727zM1.6364 7.3636v9h14.7272v-9H1.6364z\" /></svg>',
							dateFormat: 'mm/dd/yy'
						}
					);
				} )
			</script>",
			$this->settings->get_input_name_prefix(),
			$this->inputs['date']->name,
			esc_html__( 'Open Date Picker', 'gravityforms' )
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
