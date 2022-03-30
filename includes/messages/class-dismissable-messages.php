<?php

namespace Gravity_Forms\Gravity_Forms\Messages;

class Dismissable_Messages {

	private static $dismissible_messages = array();

	/**
	 * Add an internal (one we manage within the codebase) dismissible message to the array of dismissible messages.
	 *
	 * @param string $key
	 * @param string $type
	 * @param string|array|bool $capabilities A string containing a capability. Or an array or capabilities. Or FALSE for no capability check.
	 * @param bool $sticky Whether to keep displaying the message until it's dismissed.
	 * @param string|null $page The page on which to display the sticky message. NULL will display on all pages available.
	 *
	 * @since 2.5.7
	 */
	public function add_internal( $key, $type = 'warning', $capabilities = false, $sticky = false, $page = null ) {
		$text = $this->get_internal_message( $key );

		if ( empty( $text ) ) {
			return;
		}

		$this->add( $text, $key, $type, $capabilities, $sticky, $page );
	}

	/**
	 * Add a dismissible message to the array of dismissible messages.
	 *
	 * @param string $text
	 * @param string $key
	 * @param string $type
	 * @param string|array|bool $capabilities A string containing a capability. Or an array or capabilities. Or FALSE for no capability check.
	 * @param bool $sticky Whether to keep displaying the message until it's dismissed.
	 * @param string|null $page The page on which to display the sticky message. NULL will display on all pages available.
	 *
	 * @since 2.5.7
	 */
	public function add( $text, $key, $type = 'warning', $capabilities = false, $sticky = false, $page = null ) {
		$message['type']         = $type;
		$message['text']         = $text;
		$message['key']          = sanitize_key( $key );
		$message['capabilities'] = $capabilities;
		$message['page']         = $page;

		if ( $sticky ) {
			$sticky_messages         = get_option( 'gform_sticky_admin_messages', array() );
			$sticky_messages[ $key ] = $message;
			update_option( 'gform_sticky_admin_messages', $sticky_messages );
		} else {
			self::$dismissible_messages[] = $message;
		}
	}

	/**
	 * Remove a dismissible message from the array of sticky dismissible messages.
	 *
	 * @param string $key
	 *
	 * @since 2.5.7
	 */
	public function remove( $key ) {
		$key             = sanitize_key( $key );
		$sticky_messages = get_option( 'gform_sticky_admin_messages', array() );
		foreach ( $sticky_messages as $sticky_key => $sticky_message ) {
			if ( $key == sanitize_key( $sticky_message['key'] ) ) {
				unset( $sticky_messages[ $sticky_key ] );
				update_option( 'gform_sticky_admin_messages', $sticky_messages );
				break;
			}
		}
	}

	/**
	 * Get all the stored sticky messages from the DB.
	 *
	 * @since 2.5.7
	 *
	 * @return array
	 */
	private function get_sticky_messages() {
		$messages = get_option( 'gform_sticky_admin_messages', array() );
		$map      = $this->internal_messages_map();

		if ( empty( $messages ) ) {
			return $messages;
		}

		return array_map( function ( $message ) use ( $map ) {
			$key = $message['key'];

			if ( ! array_key_exists( $key, $map ) ) {
				return $message;
			}

			$message['text'] = call_user_func( $map[ $key ] );

			return $message;
		}, $messages );
	}

	/**
	 * Outputs dismissible messages on the page.
	 *
	 * @param array|bool $messages A predetermined set of messages to display instead of retrieving them.
	 * @param string|null $page Defaults to current Gravity Forms page from \GFForms::get_page().
	 *
	 * @since 2.5.7
	 */
	public function display( $messages = false, $page = null ) {

		if ( ! $messages ) {
			$messages        = self::$dismissible_messages;
			$sticky_messages = $this->get_sticky_messages();
			$messages        = array_merge( $messages, $sticky_messages );
			$messages        = array_values( $messages );
		}

		if ( empty( $page ) ) {
			$page = \GFForms::get_page();
		}

		if ( ! empty( $messages ) ) {
			$need_script = false;
			foreach ( $messages as $message ) {

				if ( isset( $sticky_messages[ $message['key'] ] ) && isset( $message['page'] ) && $message['page'] && $page !== $message['page'] ) {
					continue;
				}

				if ( empty( $message['page'] ) && $page == 'site-wide' ) {
					// Prevent double display on GF pages
					continue;
				}

				if ( empty( $message['key'] ) || $this->is_dismissed( $message['key'] ) ) {
					continue;
				}

				if ( isset( $message['capabilities'] ) && $message['capabilities'] && ! \GFCommon::current_user_can_any( $message['capabilities'] ) ) {
					continue;
				}

				$class = in_array( $message['type'], array(
					'warning',
					'error',
					'updated',
					'success',
				) ) ? $message['type'] : 'error';

				$need_script = true;
				?>
				<div class="notice below-h1 notice-<?php echo $class; ?> is-dismissible gf-notice"
				     data-gf_dismissible_key="<?php echo $message['key'] ?>"
				     data-gf_dismissible_nonce="<?php echo wp_create_nonce( 'gf_dismissible_nonce' ) ?>">
					<p>
						<?php echo $message['text']; ?>
					</p>
				</div>
				<?php
			}
			if ( $need_script ) {
				?>
				<script>
					jQuery( document ).ready( function( $ ) {
						$( document ).on( 'click', '.notice-dismiss', function() {
							var $div = $( this ).closest( 'div.notice' );
							if ( $div.length > 0 ) {
								var messageKey = $div.data( 'gf_dismissible_key' );
								var nonce      = $div.data( 'gf_dismissible_nonce' );
								if ( messageKey ) {
									jQuery.ajax( {
										url: ajaxurl,
										data: {
											action: 'gf_dismiss_message',
											message_key: messageKey,
											nonce: nonce,
										},
									} );
								}
							}
						} );
					} );
				</script>
				<?php
			}
		}
	}

	/**
	 * Adds a dismissible message to the user meta of the current user so it's not displayed again.
	 *
	 * @param string $key The message key.
	 *
	 * @since 2.5.7
	 */
	public function dismiss( $key ) {
		$db_key = $this->get_db_key( $key );

		update_user_meta( get_current_user_id(), $db_key, true, true );
	}

	/**
	 * Has the dismissible message been dismissed by the current user?
	 *
	 * @since 2.5.7
	 *
	 * @param string $key The message key.
	 *
	 * @return bool
	 */
	private function is_dismissed( $key ) {
		$db_key = $this->get_db_key( $key );
		return (bool) get_user_meta( get_current_user_id(), $db_key, true );
	}

	/**
	 * Returns the database key for the message.
	 *
	 * @since 2.5.7
	 *
	 * @param string $key The message key.
	 *
	 * @return string
	 */
	private function get_db_key( $key ) {
		$key = sanitize_key( $key );

		return 'gf_dimissed_' . substr( md5( $key ), 0, 40 );
	}

	/**
	 * Target for the wp_ajax_gf_dismiss_message ajax action requested from the Gravity Forms admin pages.
	 *
	 * @since  2.5.7
	 *
	 * @return void
	 */
	public function ajax_dismiss() {
		check_admin_referer( 'gf_dismissible_nonce', 'nonce' );

		$key = rgget( 'message_key' );
		$key = sanitize_key( $key );

		$this->dismiss( $key );
	}

	/**
	 * Get an array representing a map of internal messages to the callbacks which render them.
	 *
	 * @since 2.5.7
	 *
	 * @return array[]
	 */
	private function internal_messages_map() {
		return array();
	}

	/**
	 * Get an internal message (generally one we control in core that requires translating).
	 *
	 * @since 2.5.7
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	private function get_internal_message( $key ) {
		$map = $this->internal_messages_map();

		if ( ! isset( $map[ $key ] ) || ! is_callable( $map[ $key ] ) ) {
			return '';
		}

		return call_user_func( $map[ $key ] );
	}
}
