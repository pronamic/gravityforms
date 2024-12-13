<?php

namespace Gravity_Forms\Gravity_Forms\Form_Display\Block_Styles;

use GFCommon;
use Gravity_Forms\Gravity_Forms\Form_Display\Block_Styles\Views\Confirmation_View;
use Gravity_Forms\Gravity_Forms\Form_Display\Block_Styles\Views\Form_View;
use Gravity_Forms\Gravity_Forms\Theme_Layers\API\Fluent\Theme_Layer_Builder;

class Block_Styles_Handler {

	const NAME = 'block_styles';

	protected $defaults_map;

	public function __construct( $defaults_map ) {
		$this->defaults_map = $defaults_map;
	}

	public function defaults_map( $form ) {
		return call_user_func( $this->defaults_map, $form );
	}

	public function handle() {
		$layer = new Theme_Layer_Builder();
		$layer->set_name( self::NAME )
		      ->set_form_css_properties( array( $this, 'form_css_properties' ) )
		      ->set_overidden_fields( $this->overriden_fields() )
		      ->set_styles( array( $this, 'styles' ) )
		      ->register();
	}

	public function form_css_properties( $form_id, $settings, $block_settings, $form = array() ) {

		if ( rgar( $form, 'styles' ) === false ) {
			return array();
		}

		$applied_settings = wp_parse_args( $block_settings, $this->defaults_map( $form ) );

		// Bail early if orbital isn't applied.
		if ( $applied_settings['theme'] !== 'orbital' ) {
			return array();
		}

		$color_palette = GFCommon::generate_block_styles_palette( $applied_settings );

		return array(
			/* Global CSS API: Theme */
			'gf-color-primary'              => $color_palette['primary']['color'],
			'gf-color-primary-rgb'          => implode( ', ', $color_palette['primary']['color-rgb'] ),
			'gf-color-primary-contrast'     => $color_palette['primary']['color-contrast'],
			'gf-color-primary-contrast-rgb' => implode( ', ', $color_palette['primary']['color-contrast-rgb'] ),
			'gf-color-primary-darker'       => $color_palette['primary']['color-darker'],
			'gf-color-primary-lighter'      => $color_palette['primary']['color-lighter'],

			'gf-color-secondary'              => $color_palette['secondary']['color'],
			'gf-color-secondary-rgb'          => implode( ', ', $color_palette['secondary']['color-rgb'] ),
			'gf-color-secondary-contrast'     => $color_palette['secondary']['color-contrast'],
			'gf-color-secondary-contrast-rgb' => implode( ', ', $color_palette['secondary']['color-contrast-rgb'] ),
			'gf-color-secondary-darker'       => $color_palette['secondary']['color-darker'],
			'gf-color-secondary-lighter'      => $color_palette['secondary']['color-lighter'],

			'gf-color-out-ctrl-light'         => $color_palette['outside-control-light']['color'],
			'gf-color-out-ctrl-light-rgb'     => implode( ', ', $color_palette['outside-control-light']['color-rgb'] ),
			'gf-color-out-ctrl-light-darker'  => $color_palette['outside-control-light']['color-darker'],
			'gf-color-out-ctrl-light-lighter' => $color_palette['outside-control-light']['color-lighter'],

			'gf-color-out-ctrl-dark'         => $color_palette['outside-control-dark']['color'],
			'gf-color-out-ctrl-dark-rgb'     => implode( ', ', $color_palette['outside-control-dark']['color-rgb'] ),
			'gf-color-out-ctrl-dark-darker'  => $color_palette['outside-control-dark']['color-darker'],
			'gf-color-out-ctrl-dark-lighter' => $color_palette['outside-control-dark']['color-lighter'],

			'gf-color-in-ctrl'              => $color_palette['inside-control']['color'],
			'gf-color-in-ctrl-rgb'          => implode( ', ', $color_palette['inside-control']['color-rgb'] ),
			'gf-color-in-ctrl-contrast'     => $color_palette['inside-control']['color-contrast'],
			'gf-color-in-ctrl-contrast-rgb' => implode( ', ', $color_palette['inside-control']['color-contrast-rgb'] ),
			'gf-color-in-ctrl-darker'       => $color_palette['inside-control']['color-darker'],
			'gf-color-in-ctrl-lighter'      => $color_palette['inside-control']['color-lighter'],

			'gf-color-in-ctrl-primary'              => $color_palette['inside-control-primary']['color'],
			'gf-color-in-ctrl-primary-rgb'          => implode( ', ', $color_palette['inside-control-primary']['color-rgb'] ),
			'gf-color-in-ctrl-primary-contrast'     => $color_palette['inside-control-primary']['color-contrast'],
			'gf-color-in-ctrl-primary-contrast-rgb' => implode( ', ', $color_palette['inside-control-primary']['color-contrast-rgb'] ),
			'gf-color-in-ctrl-primary-darker'       => $color_palette['inside-control-primary']['color-darker'],
			'gf-color-in-ctrl-primary-lighter'      => $color_palette['inside-control-primary']['color-lighter'],

			'gf-color-in-ctrl-light'         => $color_palette['inside-control-light']['color'],
			'gf-color-in-ctrl-light-rgb'     => implode( ', ', $color_palette['inside-control-light']['color-rgb'] ),
			'gf-color-in-ctrl-light-darker'  => $color_palette['inside-control-light']['color-darker'],
			'gf-color-in-ctrl-light-lighter' => $color_palette['inside-control-light']['color-lighter'],

			'gf-color-in-ctrl-dark'         => $color_palette['inside-control-dark']['color'],
			'gf-color-in-ctrl-dark-rgb'     => implode( ', ', $color_palette['inside-control-dark']['color-rgb'] ),
			'gf-color-in-ctrl-dark-darker'  => $color_palette['inside-control-dark']['color-darker'],
			'gf-color-in-ctrl-dark-lighter' => $color_palette['inside-control-dark']['color-lighter'],

			'gf-radius' => $applied_settings['inputBorderRadius'] . 'px',

			/* Global CSS API: Typography */
			'gf-font-size-secondary' => $applied_settings['labelFontSize'] . 'px',
			'gf-font-size-tertiary'  => $applied_settings['descriptionFontSize'] . 'px',

			/* Global CSS API: Icons */
			'gf-icon-ctrl-number' => "url(\"data:image/svg+xml,%3Csvg width='8' height='14' viewBox='0 0 8 14' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M4 0C4.26522 5.96046e-08 4.51957 0.105357 4.70711 0.292893L7.70711 3.29289C8.09763 3.68342 8.09763 4.31658 7.70711 4.70711C7.31658 5.09763 6.68342 5.09763 6.29289 4.70711L4 2.41421L1.70711 4.70711C1.31658 5.09763 0.683417 5.09763 0.292893 4.70711C-0.0976311 4.31658 -0.097631 3.68342 0.292893 3.29289L3.29289 0.292893C3.48043 0.105357 3.73478 0 4 0ZM0.292893 9.29289C0.683417 8.90237 1.31658 8.90237 1.70711 9.29289L4 11.5858L6.29289 9.29289C6.68342 8.90237 7.31658 8.90237 7.70711 9.29289C8.09763 9.68342 8.09763 10.3166 7.70711 10.7071L4.70711 13.7071C4.31658 14.0976 3.68342 14.0976 3.29289 13.7071L0.292893 10.7071C-0.0976311 10.3166 -0.0976311 9.68342 0.292893 9.29289Z' fill='{$color_palette['inside-control-dark']['color-lighter']}'/%3E%3C/svg%3E\")",
			'gf-icon-ctrl-select' => "url(\"data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath fill-rule='evenodd' clip-rule='evenodd' d='M0.292893 0.292893C0.683417 -0.097631 1.31658 -0.097631 1.70711 0.292893L5 3.58579L8.29289 0.292893C8.68342 -0.0976311 9.31658 -0.0976311 9.70711 0.292893C10.0976 0.683417 10.0976 1.31658 9.70711 1.70711L5.70711 5.70711C5.31658 6.09763 4.68342 6.09763 4.29289 5.70711L0.292893 1.70711C-0.0976311 1.31658 -0.0976311 0.683418 0.292893 0.292893Z' fill='{$color_palette['inside-control-dark']['color-lighter']}'/%3E%3C/svg%3E\")",
			'gf-icon-ctrl-search' => "url(\"data:image/svg+xml,%3Csvg version='1.1' xmlns='http://www.w3.org/2000/svg' width='640' height='640'%3E%3Cpath d='M256 128c-70.692 0-128 57.308-128 128 0 70.691 57.308 128 128 128 70.691 0 128-57.309 128-128 0-70.692-57.309-128-128-128zM64 256c0-106.039 85.961-192 192-192s192 85.961 192 192c0 41.466-13.146 79.863-35.498 111.248l154.125 154.125c12.496 12.496 12.496 32.758 0 45.254s-32.758 12.496-45.254 0L367.248 412.502C335.862 434.854 297.467 448 256 448c-106.039 0-192-85.962-192-192z' fill='{$color_palette['inside-control-dark']['color-lighter']}'/%3E%3C/svg%3E\")",

			/* Global CSS API: Layout & Spacing */
			'gf-label-space-y-secondary' => 'var(--gf-label-space-y-' . $applied_settings['inputSize'] . '-secondary)',

			/* Global CSS API: Controls - Default For All Types */
			'gf-ctrl-border-color' => $applied_settings['inputBorderColor'],
			'gf-ctrl-size'         => 'var(--gf-ctrl-size-' . $applied_settings['inputSize'] . ')',

			/* Global CSS API: Control - Label */
			'gf-ctrl-label-color-primary'    => $applied_settings['labelColor'],
			'gf-ctrl-label-color-secondary'  => $applied_settings['labelColor'],

			/* Global CSS API: Control - Choice (Checkbox, Radio, & Consent) */
			'gf-ctrl-choice-size'         => 'var(--gf-ctrl-choice-size-' . $applied_settings['inputSize'] . ')',
			'gf-ctrl-checkbox-check-size' => 'var(--gf-ctrl-checkbox-check-size-' . $applied_settings['inputSize'] . ')',
			'gf-ctrl-radio-check-size'    => 'var(--gf-ctrl-radio-check-size-' . $applied_settings['inputSize'] . ')',

			/* Global CSS API: Control - Button */
			'gf-ctrl-btn-font-size'              => 'var(--gf-ctrl-btn-font-size-' . $applied_settings['inputSize'] . ')',
			'gf-ctrl-btn-padding-x'              => 'var(--gf-ctrl-btn-padding-x-' . $applied_settings['inputSize'] . ')',
			'gf-ctrl-btn-size'                   => 'var(--gf-ctrl-btn-size-' . $applied_settings['inputSize'] . ')',
			'gf-ctrl-btn-border-color-secondary' => $applied_settings['inputBorderColor'],

			/* Global CSS API: Control - File */
			'gf-ctrl-file-btn-bg-color-hover' => GFCommon::darken_color( $color_palette['inside-control']['color-darker'], 2 ),

			/* Global CSS API: Field - Choice (Checkbox, Radio, Image, & Consent) */
			'gf-field-img-choice-size'                => 'var(--gf-field-img-choice-size-' . $applied_settings['inputImageChoiceSize'] . ')',
			'gf-field-img-choice-card-space'          => 'var(--gf-field-img-choice-card-space-' . $applied_settings['inputImageChoiceSize'] . ')',
			'gf-field-img-choice-check-ind-size'      => 'var(--gf-field-img-choice-check-ind-size-' . $applied_settings['inputImageChoiceSize'] . ')',
			'gf-field-img-choice-check-ind-icon-size' => 'var(--gf-field-img-choice-check-ind-icon-size-' . $applied_settings['inputImageChoiceSize'] . ')',

			/* Global CSS API: Field - Page */
			'gf-field-pg-steps-number-color' => 'rgba(' . implode( ', ', GFCommon::darken_color( $applied_settings['labelColor'], 0, 'rgb' ) ) . ', 0.8)',
		);
	}

	private function overriden_fields() {
		return array(
			'form'         => Form_View::class,
			'confirmation' => Confirmation_View::class,
		);
	}

	public function styles( $form, $ajax, $settings, $block_settings ) {

		$styles = array( 'theme' => array() );
		if ( GFCommon::output_default_css() === false ) {
			return $styles;
		}

		$themes = \GFFormDisplay::get_themes_to_enqueue( $form );

		if ( in_array( 'orbital', $themes ) ) {
			$styles['theme']      = array( array( 'gravity_forms_orbital_theme' ) );
			$styles['foundation'] = array( array( 'gravity_forms_theme_foundation' ) );
			$styles['framework']  = array( array( 'gravity_forms_theme_framework' ) );
			$styles['reset']      = array( array( 'gravity_forms_theme_reset' ) );

			if ( GFCommon::is_form_editor() ) {
				$styles['framework'][]  = array( 'gravity_forms_theme_framework_admin' );
				$styles['foundation'][] = array( 'gravity_forms_theme_foundation_admin' );
			}
		}

		if ( in_array( 'gravity-theme', $themes ) ) {

			if ( GFCommon::is_entry_detail() ) {
				$styles['theme'][] = array( 'gform_theme_admin' );
			} else {
				$styles['theme'][] = array( 'gform_basic' );

				/**
				 * Allows users to disable the main theme.css file from being loaded on the Front End.
				 *
				 * @param boolean Whether to disable the theme css.
				 * @since 2.5-beta-3
				 *
				 */
				$disable_theme_css = apply_filters( 'gform_disable_form_theme_css', false );
				if ( ! $disable_theme_css ) {
					$styles['theme'][] = array( 'gform_theme' );
				}
			}
		}

		return $styles;
	}
}
