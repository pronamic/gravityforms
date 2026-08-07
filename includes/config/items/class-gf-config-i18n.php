<?php

namespace Gravity_Forms\Gravity_Forms\Config\Items;

use Gravity_Forms\Gravity_Forms\Config\GF_Config_Collection;
use Gravity_Forms\Gravity_Forms\Config\GF_Config;
use Gravity_Forms\Gravity_Forms\Config\GF_Configurator;

/**
 * Config items for Theme I18N
 *
 * @since 2.6
 */
class GF_Config_I18n extends GF_Config {

	protected $name               = 'gform_i18n';
	protected $script_to_localize = 'gform_gravityforms';

	/**
	 * Config data.
	 *
	 * @return array[]
	 */
	public function data() {
		$days = [
			[ 's' => esc_html__( 'S', 'gravityforms' ), 'l' => esc_html__( 'Sunday', 'gravityforms' ) ],
			[ 's' => esc_html__( 'M', 'gravityforms' ), 'l' => esc_html__( 'Monday', 'gravityforms' ) ],
			[ 's' => esc_html__( 'T', 'gravityforms' ), 'l' => esc_html__( 'Tuesday', 'gravityforms' ) ],
			[ 's' => esc_html__( 'W', 'gravityforms' ), 'l' => esc_html__( 'Wednesday', 'gravityforms' ) ],
			[ 's' => esc_html__( 'T', 'gravityforms' ), 'l' => esc_html__( 'Thursday', 'gravityforms' ) ],
			[ 's' => esc_html__( 'F', 'gravityforms' ), 'l' => esc_html__( 'Friday', 'gravityforms' ) ],
			[ 's' => esc_html__( 'S', 'gravityforms' ), 'l' => esc_html__( 'Saturday', 'gravityforms' ) ],
		];
		return [
			'datepicker' => [
				'helpTextShort'       => esc_html__( 'Press F1 for help', 'gravityforms' ),
				'helpText'            => esc_html__( 'Press the arrow keys to navigate by day, PageUp and PageDown to navigate by month, Alt+PageUp and Alt+PageDown to navigate by year, or Escape to cancel.', 'gravityforms' ),
				'openOnFocusHelpText' => esc_html__( 'Press Down arrow to browse the calendar, or Escape to close.', 'gravityforms' ),
				'tooltipTxt'          => esc_html__( 'Press Escape to cancel', 'gravityforms' ),
				'disabledTxt'         => esc_html__( 'Disabled', 'gravityforms' ),
				'markedTxt'           => esc_html__( 'Selected', 'gravityforms' ),
				'prevTxt'             => esc_html__( 'Previous', 'gravityforms' ),
				'nextTxt'             => esc_html__( 'Next', 'gravityforms' ),
				'monthTxt'            => esc_html__( 'Month', 'gravityforms' ),
				'yearTxt'             => esc_html__( 'Year', 'gravityforms' ),
				'months'              => [
						esc_html__( 'January', 'gravityforms' ),
						esc_html__( 'February', 'gravityforms' ),
						esc_html__( 'March', 'gravityforms' ),
						esc_html__( 'April', 'gravityforms' ),
						esc_html__( 'May', 'gravityforms'),
						esc_html__( 'June', 'gravityforms' ),
						esc_html__( 'July', 'gravityforms' ),
						esc_html__( 'August', 'gravityforms' ),
						esc_html__( 'September', 'gravityforms' ),
						esc_html__( 'October', 'gravityforms' ),
						esc_html__( 'November', 'gravityforms' ),
						esc_html__( 'December', 'gravityforms' ),
					],
				'days'                => $days,
			],
		];
	}
}
