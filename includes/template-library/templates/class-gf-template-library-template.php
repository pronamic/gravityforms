<?php


namespace Gravity_Forms\Gravity_Forms\Template_Library\Templates;

/**
 * Class GF_Template_Library_Template
 *
 * Represents Template library.
 *
 * @package Gravity_Forms\Gravity_Forms\Template_Library;
 */
class GF_Template_Library_Template {
	/**
	 * The template data.
	 *
	 * @since 2.7
	 *
	 * @var array $data The template raw data.
	 */
	protected $data;

	/**
	 * The template ID.
	 *
	 * @since 2.7
	 *
	 * @var string $id The id of the template.
	 */
	protected $id;

	/**
	 * The template name.
	 *
	 * @since 2.7
	 *
	 * @var string $name The name of the template.
	 */
	protected $title;

	/**
	 * The template description.
	 *
	 * @since 2.7
	 *
	 * @var string $description The description of the template.
	 */
	protected $description;

	/**
	 * The template form meta data.
	 *
	 * @since 2.7
	 *
	 * @var array $form_meta The form meta data.
	 */
	protected $form_meta;

	/**
	 * Template constructor.
	 *
	 * @sine 2.7
	 *
	 * @param array $data The template data.
	 */
	public function __construct( $data ) {
		$this->data = $data;
	}

	/**
	 * Returns the template title.
	 *
	 * @sine 2.7
	 *
	 * @return string
	 */
	public function get_title() {
		if ( ! isset( $this->title ) ) {
			$this->title = rgar( $this->data, 'title' );
		}

		return $this->title;
	}

	/**
	 * Returns the template description.
	 *
	 * @sine 2.7
	 *
	 * @return string
	 */
	public function get_description() {
		if ( ! isset( $this->description ) ) {
			$this->description = rgar( $this->data, 'description' );
		}

		return $this->description;
	}

	/**
	 * Returns the template form meta.
	 *
	 * @sine 2.7
	 *
	 * @return array
	 */
	public function get_form_meta() {
		if ( ! isset( $this->form_meta ) ) {
			$this->form_meta = $this->cleanup_form_meta();
		}

		return $this->form_meta;
	}

	/**
	 * Returns the template ID.
	 *
	 * @sine 2.7
	 *
	 * @return string
	 */
	public function get_id() {
		if ( ! isset( $this->id ) ) {
			$this->id = rgar( $this->data, 'id' );
		}

		return $this->id;
	}

	/**
	 * Cleans up the form meta JSON.
	 *
	 * Some form exports will have the form id as one of the keys of the form fields, also some escaped characters cause some issues.
	 *
	 * @since 2.7
	 *
	 * @return array
	 */
	protected function cleanup_form_meta() {
		$form_meta = rgar( $this->data, 'form_meta' );
		if ( isset( $form_meta['id'] ) ) {
			unset( $form_meta['id'] );
		}
		// Unset form IDs left from exporting a form.
		$fields = rgar( $form_meta, 'fields' );
		if ( is_array( $fields ) && count( $fields ) > 0 ) {
			foreach ( $fields as &$field ) {
				if ( isset( $field['formId'] ) ) {
					unset( $field['formId'] );
				}
			}
			$form_meta['fields'] = $fields;
		}

		// Some forms don't have this set, which causes some notices.
		if ( ! isset( $form_meta['button'] ) ) {
			$form_meta['button'] = array(
				'type'     => 'text',
				'text'     => '',
				'imageUrl' => '',
			);
		}

		// escaping double quotes this way messes things up.
		$meta_json = str_replace( '\"', "'", wp_json_encode( $form_meta ) );

		return json_decode( $meta_json, true );
	}

}
