<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class Post_Select extends Select {

	/**
	 * Field type.
	 *
	 * @since 2.6.2
	 *
	 * @var string
	 */
	public $type = 'post_select';

	/**
	 * Post type.
	 *
	 * @since 2.6.2
	 *
	 * @var string
	 */
	public $post_type = 'page';


	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.6.2
	 *
	 * @return string
	 */
	public function markup() {

		// Display description.
		$html = $this->get_description();

		$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

		// Get post type details.
		$post_type = get_post_type_object( $this->post_type );

		if ( ! $post_type ) {

			$html .= esc_html( sprintf( __( 'The requested post type %s does not exist.', 'gravityforms' ), $this->post_type ) );

		} else {
			$post_singular = $post_type->labels->singular_name;
			$post_plural   = $post_type->labels->name;

			$html = sprintf(
				'<article class="gform-dropdown" data-js="gform-settings-field-select" data-post-type="%1$s">
				    <span class="gform-visually-hidden" id="gform-%2$s-label">
						%3$s
				    </span>
				
				    <button
						type="button"
						aria-expanded="false"
						aria-haspopup="listbox"
						aria-labelledby="gform-%2$s-label gform-%2$s-control"
						class="gform-dropdown__control %6$s"
						data-js="gform-dropdown-control"
						id="gform-%2$s-control"
				    >
						<span
							class="gform-dropdown__control-text"
							data-js="gform-dropdown-control-text"
						>
				            %3$s
				        </span>
						<span class="gform-spinner gform-dropdown__spinner"></span>
						<span class="gform-icon gform-icon--chevron gform-dropdown__chevron"></span>
				    </button>
				    <div
						aria-labelledby="gform-%2$s-label"
						class="gform-dropdown__container"
						role="listbox"
						data-js="gform-dropdown-container"
						tabindex="-1"
				    >
						<div class="gform-dropdown__search">
							<label for="gform-settings-field__%2$s-search" class="gform-visually-hidden">
								%4$s
							</label>
							<input
								id="gform-settings-field__%2$s-search"
								type="text"
								class="gform-input gform-dropdown__search-input"
								placeholder="%4$s"
								data-js="gform-dropdown-search"
							/>
							<span class="gform-icon gform-icon--search gform-dropdown__search-icon"></span>
						</div>
				
				      <div class="gform-dropdown__list-container">
				        <ul class="gform-dropdown__list" data-js="gform-dropdown-list"></ul>
				      </div>
				    </div>
				    <input type="hidden" data-js="gf-post-select-input" name="_gform_setting_%2$s" id="%2$s" value="%5$s"/>
				</article>',
				$this->post_type,
				esc_attr( $this->name ), // field name, used in HTML attributes
				esc_html( $this->get_dropdown_label( $post_singular ) ), // form switcher label
				esc_html( $this->get_search_label( $post_plural ) ), // label for search field
				esc_attr( $this->get_value() ),
				empty( $this->get_value() ) ? 'gform-dropdown__control--placeholder' : ''
			);

		}

		// If field failed validation, add error icon.
		$html .= $this->get_error_icon();

		$html .= '</span>';

		return $html;

	}

	/**
	 * Get the label for the dropdown.
	 *
	 * @since 2.6.2
	 *
	 * @param string $singular Post type name (singular)
	 *
	 * @return string
	 */
	public function get_dropdown_label( $singular ) {
		if ( empty( $this->get_value() ) ) {
			// Translators: singular post type name (e.g. 'post').
			return sprintf( __( 'Select a %s', 'gravityforms' ), $singular );
		}

		$post_id = $this->get_value();

		return get_the_title( $post_id );
	}

	/**
	 * Get the label for the search field.
	 *
	 * @since 2.6.2
	 *
	 * @param string $plural Post type name (plural)
	 *
	 * @return string
	 */
	public function get_search_label( $plural ) {
		// Translators: plural post type name (e.g. 'post's).
		return sprintf( __( 'Search all %s', 'gravityforms' ), $plural );
	}


}

Fields::register( 'post_select', '\Gravity_Forms\Gravity_Forms\Settings\Fields\Post_Select' );
