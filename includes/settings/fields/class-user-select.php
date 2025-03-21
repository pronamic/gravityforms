<?php

namespace Gravity_Forms\Gravity_Forms\Settings\Fields;

use Gravity_Forms\Gravity_Forms\Settings\Fields;

defined( 'ABSPATH' ) || die();

class User_Select extends Select {

	/**
	 * Field type.
	 *
	 * @since 2.9.5
	 *
	 * @var string
	 */
	public $type = 'user_select';


	// # RENDER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Render field.
	 *
	 * @since 2.9.5
	 *
	 * @return string
	 */
	public function markup() {

		// Display description.
		$html = $this->get_description();

		$html .= '<span class="' . esc_attr( $this->get_container_classes() ) . '">';

		$html .= sprintf(
			'<article class="gform-dropdown" data-js="gform-settings-field-user-select">
			    <span class="gform-visually-hidden" id="gform-%1$s-label">
					%2$s
			    </span>
			
			    <button
					type="button"
					aria-expanded="false"
					aria-haspopup="listbox"
					aria-labelledby="gform-%1$s-label gform-%1$s-control"
					class="gform-dropdown__control %1$s"
					data-js="gform-dropdown-control"
					id="gform-%1$s-control"
			    >
					<span
						class="gform-dropdown__control-text"
						data-js="gform-dropdown-control-text"
					>
			            %2$s
			        </span>
					<span class="gform-spinner gform-dropdown__spinner"></span>
					<span class="gform-icon gform-icon--chevron gform-dropdown__chevron"></span>
			    </button>
			    <div
					aria-labelledby="gform-%1$s-label"
					class="gform-dropdown__container"
					role="listbox"
					data-js="gform-dropdown-container"
					tabindex="-1"
			    >
					<div class="gform-dropdown__search">
						<label for="gform-settings-field__%1$s-search" class="gform-visually-hidden">
							%3$s
						</label>
						<input
							id="gform-settings-field__%1$s-search"
							type="text"
							class="gform-input gform-dropdown__search-input"
							placeholder="%2$s"
							data-js="gform-dropdown-search"
						/>
						<span class="gform-icon gform-icon--search gform-dropdown__search-icon"></span>
					</div>
			
			      <div class="gform-dropdown__list-container">
			        <ul class="gform-dropdown__list" data-js="gform-dropdown-list"></ul>
			      </div>
			    </div>
			    <input type="hidden" data-js="gf-user-select-input" name="_gform_setting_%1$s" id="%1$s" value="%4$s"/>
			</article>',
			esc_attr( $this->name ), // field name, used in HTML attributes
			esc_html( $this->get_dropdown_label() ), // form switcher label
			esc_html__( 'Search users', 'gravityforms' ), // label for search field
			esc_attr( $this->get_value() )
		);


		// If field failed validation, add error icon.
		$html .= $this->get_error_icon();

		$html .= '</span>';

		return $html;

	}

	/**
	 * Get the label for the dropdown.
	 *
	 * @since 2.9.5
	 *
	 * @return string
	 */
	public function get_dropdown_label() {
		if ( empty( $this->get_value() ) ) {
			return __( 'Select a user', 'gravityforms' );
		}

		if ( 'logged-in-user' === $this->get_value() ) {
			return __( 'Logged In User', 'gravityforms' );
		}

		$user_id = $this->get_value();
		$user = get_user_by( 'id', $user_id );

		return esc_attr( $user->display_name );
	}

}

Fields::register( 'user_select', '\Gravity_Forms\Gravity_Forms\Settings\Fields\User_Select' );
