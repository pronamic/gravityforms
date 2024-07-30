<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Output_Engines;

/**
 * Handles outputting CSS blocks composed of custom CSS Properties.
 *
 * @since 2.7
 */
class Form_CSS_Properties_Output_Engine extends Output_Engine {

	private static $processed = 0;
	private static $processed_tracker = array();

	protected $type = 'form_css_properties';
	protected $properties_cb;

	public static function get_processed_num() {
		return self::$processed;
	}

	/**
	 * Set the callback for parsing form properties.
	 *
	 * @since 2.7
	 *
	 * @param array $properties_cb
	 */
	public function set_form_css_properties_cb( array $properties_cb ) {
		$this->properties_cb = $properties_cb;
	}

	/**
	 * Handle outputting the CSS blocks.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function output() {
		$self = $this;

		add_filter( 'gform_form_after_open', function ( $markup, $form ) use ( $self ) {
			$props_block = $self->generate_props_block( $form['id'], $form );

			$processed_hash = md5( json_encode( $form ) );

			if ( ! in_array( $processed_hash, self::$processed_tracker ) ) {
				self::$processed++;
				self::$processed_tracker[] = $processed_hash;
			}

			return $markup . $props_block;
		}, 999, 2 );

		// Confirmations get processed too early to inject the script tag; inject via regex after render instead.
		add_filter( 'gform_get_form_confirmation_filter', function ( $markup, $form ) use ( $self ) {
			$form_id         = (int) rgar( $form, 'id' );
			$custom_selector = sprintf( '<style>#gform_confirmation_wrapper_%d.gform-theme{', $form_id );
			$props_block     = $self->generate_props_block( $form_id, $form, $custom_selector );

			$processed_hash = md5( json_encode( $form ) );

			if ( ! in_array( $processed_hash, self::$processed_tracker ) ) {
				self::$processed++;
				self::$processed_tracker[] = $processed_hash;
			}

			$target = sprintf( "<div id='gform_confirmation_message_%d", $form_id );

			return str_replace( $target, $props_block . $target, $markup );
		}, 999, 2 );
	}

	/**
	 * Generate the properties block for the given form ID>
	 *
	 * @since 2.7
	 *
	 * @param $form_id
	 * @param $form
	 *
	 * @return string
	 */
	public function generate_props_block( $form_id, $form, $custom_selector = false ) {
		$settings           = $this->get_settings( $form_id );
		$page_instance      = isset( $form['page_instance'] ) ? $form['page_instance'] : 0;

		// Get the settings from the block, if they exist.
		$all_block_settings = apply_filters( 'gform_form_block_attribute_values', array() );
		$block_settings     = isset( $all_block_settings[ $form_id ][ $page_instance ] ) ? $all_block_settings[ $form_id ][ $page_instance ] : array();

		// Get the settings from the shortcode attribute or form properties, if they exist.
		$form_style = $this->parse_form_style( $form );

		// Merge the settings - block styles get priority.
		$style_settings = ! empty( $block_settings ) ? array_merge( $form_style, $block_settings ) : $form_style;
		if ( ! rgar( $style_settings, 'theme' ) || '' == $style_settings['theme'] ) {
			$style_settings['theme'] = get_option( 'rg_gforms_default_theme', 'orbital' );
		}

		$properties = call_user_func_array( $this->properties_cb, array( $form_id, $settings, $style_settings, $form ) );

		$properties = array_filter( $properties, function ( $property ) {
			if ( ! empty( $property ) ) {
				return true;
			}

			if ( $property === 0 || $property === '0' ) {
				return true;
			}

			return false;
		} );

		if ( empty( $properties ) ) {
			return '';
		}

		if ( $custom_selector ) {
			$props_block = $custom_selector;
		} else {
			$props_block = sprintf( '<style>#gform_wrapper_%d[data-form-index="%d"].gform-theme,[data-parent-form="%d_%d"]{', $form_id, $page_instance, $form_id, $page_instance );
		}

		foreach ( $properties as $rule => $property ) {
			if ( is_null( $property ) ) {
				continue;
			}
			$props_block .= sprintf( '--%s: %s;', $rule, $property );
		}

		$props_block .= '}</style>';

		return $props_block;
	}


}
