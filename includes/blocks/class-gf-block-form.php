<?php

// If Gravity Forms Block Manager is not available, do not run.
if ( ! class_exists( 'GF_Blocks' ) || ! defined( 'ABSPATH' ) ) {
	exit;
}

class GF_Block_Form extends GF_Block {

	/**
	 * Contains an instance of this block, if available.
	 *
	 * @since  2.4.10
	 * @var    GF_Block $_instance If available, contains an instance of this block.
	 */
	private static $_instance = null;

	/**
	 * Block type.
	 *
	 * @since 2.4.10
	 * @var   string
	 */
	public $type = 'gravityforms/form';

	/**
	 * Handle of primary block script.
	 *
	 * @since 2.4.10
	 * @var   string
	 */
	public $script_handle = 'gform_editor_block_form';

	/**
	 * Handle of primary block style.
	 *
	 * @since 2.5.6
	 * @var   string
	 */
	public $style_handle = 'gform_editor_block_form';

	/**
	 * Block attributes.
	 *
	 * @since 2.4.10
	 * @var   array
	 */
	public $attributes = array(
		'formId'      => array( 'type' => 'integer' ),
		'title'       => array( 'type' => 'boolean' ),
		'description' => array( 'type' => 'boolean' ),
		'ajax'        => array( 'type' => 'boolean' ),
		'tabindex'    => array( 'type' => 'string' ),
		'fieldValues' => array( 'type' => 'string' ),
		'formPreview' => array( 'type' => 'boolean' ),
		'imgPreview'  => array( 'type' => 'boolean' ),
	);

	/**
	 * Get instance of this class.
	 *
	 * @since  2.4.10
	 *
	 * @return GF_Block_Form
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}




	// # SCRIPT / STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Register scripts for block.
	 *
	 * @since  2.4.10
	 *
	 * @return array
	 */
	public function scripts() {

		$min  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';
		$deps = array(
			'wp-blocks',
			'wp-element',
			'wp-components',
			'wp-i18n',
		);

		global $pagenow;
		if ( $pagenow !== 'widgets.php' ) {
			$deps[] = 'wp-editor';
		}

		return array(
			array(
				'handle'    => $this->script_handle,
				'in_footer' => true,
				'src'       => GFCommon::get_base_url() . "/js/blocks{$min}.js",
				'deps'      => $deps,
				'version'   => $min ? GFForms::$version : filemtime( GFCommon::get_base_path() . '/js/blocks.js' ),
				'callback'  => array( $this, 'localize_script' ),
			),
		);

	}

	/**
	 * Localize Form block script.
	 *
	 * @since  2.4.10
	 *
	 * @param array $script Script arguments.
	 */
	public function localize_script( $script = array() ) {

		wp_localize_script(
			$script['handle'],
			'gform_block_form',
			array(
				'adminURL' => admin_url( 'admin.php' ),
				'forms'    => $this->get_forms(),
				'preview'  => GFCommon::get_base_url() . '/images/gf_block_preview.svg',
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( $script['handle'], 'gravityforms', GFCommon::get_base_path() . '/languages' );
		}

	}

	/**
	 * Register styles for block.
	 *
	 * @since  2.4.10
	 *
	 * @return array
	 */
	public function styles() {

		// Prepare styling dependencies.
		$deps = array( 'wp-edit-blocks' );

		// Add Gravity Forms styling if CSS is enabled.
		if ( '1' !== get_option( 'rg_gforms_disable_css', false ) ) {
			$deps = array_merge( $deps, array( 'gform_basic', 'gforms_formsmain_css', 'gforms_ready_class_css', 'gforms_browsers_css', 'gform_theme' ) );

			/**
			 * Allows users to disable the main theme.css file from being loaded on the Front End.
			 *
			 * @since 2.5-beta-3
			 *
			 * @param boolean Whether to disable the theme css.
			 */
			$disable_theme_css = apply_filters( 'gform_disable_form_theme_css', false );

			if ( ! $disable_theme_css ) {
				$deps[] = 'gform_theme';
			}
		}

		return array(
			array(
				'handle'  => $this->style_handle,
				'src'     => GFCommon::get_base_url() . '/css/blocks.min.css',
				'deps'    => $deps,
				'version' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? filemtime( GFCommon::get_base_path() . '/css/blocks.min.css' ) : GFForms::$version,
			),
		);

	}


	// # BLOCK RENDER -------------------------------------------------------------------------------------------------

	/**
	 * Display block contents on frontend.
	 *
	 * @since  2.4.10
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string
	 */
	public function render_block( $attributes = array() ) {

		// Prepare variables.
		$form_id      = rgar( $attributes, 'formId' ) ? $attributes['formId'] : false;
		$title        = isset( $attributes['title'] ) ? $attributes['title'] : true;
		$description  = isset( $attributes['description'] ) ? $attributes['description'] : true;
		$ajax         = isset( $attributes['ajax'] ) ? $attributes['ajax'] : false;
		$tabindex     = isset( $attributes['tabindex'] ) ? intval( $attributes['tabindex'] ) : 0;
		$field_values = isset( $attributes['fieldValues'] ) ? $attributes['fieldValues'] : null;

		// If form ID was not provided or form does not exist, return.
		if ( ! $form_id || ( $form_id && ! GFAPI::get_form( $form_id ) ) ) {
			return '';
		}

		// Use Gravity Forms function for REST API requests.
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {

			// Start output buffering.
			ob_start();

			// Prepare field values.
			if ( ! empty( $field_values ) ) {
				$field_values = str_replace( '&#038;', '&', $field_values );
				parse_str( $field_values, $field_value_array );
				$field_values = stripslashes_deep( $field_value_array );
			}

			// Get form output string.
			$form_string = gravity_form( $form_id, $title, $description, false, $field_values, $ajax, $tabindex, false );

			// Get output buffer contents.
			$buffer_contents = ob_get_contents();
			ob_end_clean();

			// Return buffer contents with form string.
			return $buffer_contents . $form_string;

		}

		// Encode field values.
		$field_values = htmlspecialchars( $field_values );
		$field_values = str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), $field_values );

		// If no field values are set, set field values to null.
		parse_str( $field_values, $field_value_array );
		if ( empty( $field_value_array ) ) {
			$field_values = null;
		}

		return sprintf( '[gravityforms id="%d" title="%s" description="%s" ajax="%s" tabindex="%d" field_values="%s"]', $form_id, ( $title ? 'true' : 'false' ), ( $description ? 'true' : 'false' ), ( $ajax ? 'true' : 'false' ), $tabindex, $field_values );

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Get list of forms for Block control.
	 *
	 * @since 2.4.10
	 *
	 * @return array
	 */
	public function get_forms() {

		// Initialize forms array.
		$forms = array();

		// Load GFFormDisplay class.
		if ( ! class_exists( 'GFFormDisplay' ) ) {
			require_once GFCommon::get_base_path() . '/form_display.php';
		}

		// Get form objects.
		$form_objects = GFAPI::get_forms( true, false, 'title', 'ASC' );

		// Loop through forms, add conditional logic check.
		foreach ( $form_objects as $form ) {
			$forms[] = array(
				'id'                  => $form['id'],
				'title'               => $form['title'],
				'hasConditionalLogic' => GFFormDisplay::has_conditional_logic( $form ),
			);
		}

		/**
		 * Modify the list of available forms displayed in the Form block.
		 *
		 * @since 2.4.23
		 *
		 * @param array $forms A collection of active forms on site.
		 */
		return apply_filters( 'gform_block_form_forms', $forms );

	}

}

// Register block.
if ( true !== ( $registered = GF_Blocks::register( GF_Block_Form::get_instance() ) ) && is_wp_error( $registered ) ) {

	// Log that block could not be registered.
	GFCommon::log_error( 'Unable to register block; ' . $registered->get_error_message() );

}
