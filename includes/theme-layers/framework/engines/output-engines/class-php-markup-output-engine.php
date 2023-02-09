<?php

namespace Gravity_Forms\Gravity_Forms\Theme_Layers\Framework\Engines\Output_Engines;

/**
 * Engine to provide PHP Markup overrides for given views. Uses gform_field_content and  gform_field_content
 * to filter the markup and apply the values passed from a custom View class.
 *
 * @since 2.7
 */
class PHP_Markup_Output_Engine extends Output_Engine {

	protected $type = 'php_markup';
	protected $views = array();

	/**
	 * Set the views for this engine.
	 *
	 * @since 2.7
	 *
	 * @param array $views
	 *
	 * @return void
	 */
	public function set_views( array $views ) {
		$this->views = $views;
	}

	/**
	 * Handle the PHP Markup output by adding filters where necessary.
	 *
	 * @since 2.7
	 *
	 * @return void
	 */
	public function output() {
		$views = $this->views;

		// Form is a special case, add the filter for it here.
		if ( isset( $views['form'] ) ) {
			add_filter( 'gform_get_form_filter', array( $this, 'handle_form_override' ), 999, 2 );
			add_filter( 'gform_get_form_save_confirmation_filter', array( $this, 'handle_form_override' ), 999, 2 );
			add_filter( 'gform_get_form_confirmation_filter', array( $this, 'handle_form_override' ), 999, 2 );
			add_filter( 'gform_get_form_save_email_confirmation_filter', array( $this, 'handle_form_override' ), 999, 2 );
		}

		if ( isset( $views['confirmation'] ) ) {
			add_filter( 'gform_get_form_confirmation_filter', array( $this, 'handle_confirmation_override' ), 999, 2 );
		}

		// Add a filter for field output.
		add_filter( 'gform_field_content', array( $this, 'handle_field_override' ), 999, 5 );
	}

	/**
	 * Handle the PHP Markup output for Forms.
	 *
	 * @since 2.7
	 *
	 * @hook gform_get_form_filter 999 2
	 * @hook gform_get_form_save_confirmation_filter 999 2
	 *
	 * @return string
	 */
	public function handle_form_override( $form_string, $form ) {
		$page_instance  = rgar( $form, 'page_instance', 0 );
		$form_view      = new $this->views['form']( $this );
		$block_settings = $this->get_block_settings( $form['id'], $page_instance );

		if ( ! $form_view->should_override( null, $form['id'], $block_settings ) ) {
			return $form_string;
		}

		return $form_view->get_markup( $form_string, $form, null, null, $form['id'] );
	}

	/**
	 * Handle the PHP Markup output for Confirmations.
	 *
	 * @since 2.7
	 *
	 * @hook gform_get_form_save_confirmation_filter 999 2
	 *
	 * @return string
	 */
	public function handle_confirmation_override( $confirmation_string, $form ) {
		$page_instance  = rgar( $form, 'page_instance', 0 );
		$conf_view      = new $this->views['confirmation']( $this );
		$block_settings = $this->get_block_settings( $form['id'], $page_instance );

		if ( ! $conf_view->should_override( null, $form['id'], $block_settings ) ) {
			return $confirmation_string;
		}

		return $conf_view->get_markup( $confirmation_string, $form, null, null, $form['id'] );
	}

	/**
	 * Handle the PHP Markup output for specific fields.
	 *
	 * @since 2.7
	 *
	 * @hook gform_field_content 999 5
	 *
	 * @return string
	 */
	public function handle_field_override( $field_content, $field, $value, $lead_id, $form_id ) {

		if ( array_key_exists( 'all', $this->views ) ) {
			$field_content = $this->maybe_override_all_fields( $field_content, $field, $value, $lead_id, $form_id );
		}

		if ( ! array_key_exists( $field->type, $this->views ) ) {
			return $field_content;
		}

		$view = new $this->views[ $field->type ]( $this );

		if ( ! $view->should_override( $field, $form_id ) ) {
			return $field_content;
		}

		return $view->get_markup( $field_content, $field, $value, $lead_id, $form_id );
	}

	/**
	 * Handle the PHP Markup output if markup is being changed for all fields.
	 *
	 * @since 2.7
	 *
	 * @return string
	 */
	private function maybe_override_all_fields( $field_content, $field, $value, $lead_id, $form_id ) {
		$view = new $this->views['all']( $this );

		if ( ! $view->should_override( $field, $form_id ) ) {
			return $field_content;
		}

		return $view->get_markup( $field_content, $field, $value, $lead_id, $form_id );
	}

}
