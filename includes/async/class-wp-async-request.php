<?php
/**
 * WP Async Request
 *
 * @package WP-Background-Processing
 */

namespace Gravity_Forms\Gravity_Forms\Async;

/**
 * Abstract WP_Async_Request class.
 *
 * @since 2.2
 * @since 2.9.8 Namespaced.
 *
 * @abstract
 */
abstract class WP_Async_Request {

	/**
	 * Prefix
	 *
	 * (default value: 'wp')
	 *
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'wp';

	/**
	 * Action
	 *
	 * (default value: 'async_request')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'async_request';

	/**
	 * Identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $identifier;

	/**
	 * Data
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	protected $data = array();

	/**
	 * Initiate new async request
	 */
	public function __construct() {
		$this->identifier = $this->prefix . '_' . $this->action;

		add_action( 'wp_ajax_' . $this->identifier, array( $this, 'maybe_handle' ) ); // @phpstan-ignore-line
		add_action( 'wp_ajax_nopriv_' . $this->identifier, array( $this, 'maybe_handle' ) ); // @phpstan-ignore-line
	}

	/**
	 * Set data used during the request
	 *
	 * @param array $data Data.
	 *
	 * @return $this
	 */
	public function data( $data ) {
		$this->data = $data;

		return $this;
	}

	/**
	 * Dispatch the async request
	 *
	 * @return array|\WP_Error
	 */
	public function dispatch() {
		$url  = add_query_arg( $this->get_query_args(), $this->get_query_url() );
		$args = $this->get_post_args();

		return wp_remote_post( esc_url_raw( $url ), $args );
	}

	/**
	 * Get query args
	 *
	 * @return array
	 */
	protected function get_query_args() {
		if ( property_exists( $this, 'query_args' ) ) {
			return $this->query_args;
		}

		$args = array(
			'action' => $this->identifier,
			'nonce'  => wp_create_nonce( $this->identifier ),
		);

		/**
		 * Filters the query arguments used during an async request.
		 *
		 * @since 2.9.7
		 *
		 * @param array $args
		 */
		return apply_filters( $this->identifier . '_query_args', $args );
	}

	/**
	 * Get query URL
	 *
	 * @return string
	 */
	protected function get_query_url() {
		if ( property_exists( $this, 'query_url' ) ) {
			return $this->query_url;
		}

		$url = admin_url( 'admin-ajax.php' );

		/**
		 * Filters the query URL used during an async request.
		 *
		 * @since 2.9.7
		 *
		 * @param string $url
		 */
		return apply_filters( $this->identifier . '_query_url', $url );
	}

	/**
	 * Get post args
	 *
	 * @return array
	 */
	protected function get_post_args() {
		if ( property_exists( $this, 'post_args' ) ) {
			return $this->post_args;
		}

		$args = array(
			'timeout'   => 5,
			'blocking'  => false,
			'body'      => $this->data,
			'cookies'   => $_COOKIE, // Passing cookies ensures request is performed as initiating user.
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // Local requests, fine to pass false.
		);

		/**
		 * Filters the post arguments used during an async request.
		 *
		 * @since 2.9.7
		 *
		 * @param array $args
		 */
		return apply_filters( $this->identifier . '_post_args', $args );
	}

	/**
	 * Maybe handle
	 *
	 * Check for correct nonce and pass to handler.
	 *
	 * @return void|mixed
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing
		session_write_close();

		check_ajax_referer( $this->identifier, 'nonce' );

		$this->handle();

		return $this->maybe_wp_die();
	}

	/**
	 * Should the process exit with wp_die?
	 *
	 * @since 2.9.7
	 *
	 * @param mixed $return What to return if filter says don't die, default is null.
	 *
	 * @return void|mixed
	 */
	protected function maybe_wp_die( $return = null ) {
		/**
		 * Should wp_die be used?
		 *
		 * @return bool
		 */
		if ( apply_filters( $this->identifier . '_wp_die', true ) ) {
			wp_die();
		}

		return $return;
	}

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	abstract protected function handle();

}
