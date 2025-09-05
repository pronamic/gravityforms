<?php
/**
 * @depecated 2.9.8
 * @remove-in 3.1
 */
_deprecated_file( __FILE__, '2.9.8', GF_PLUGIN_DIR_PATH . 'includes/async/class-wp-async-request.php (Gravity_Forms\Gravity_Forms\Async\WP_Async_Request)' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
if ( ! class_exists( 'WP_Async_Request' ) ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/async/class-wp-async-request.php';
	class_alias( Gravity_Forms\Gravity_Forms\Async\WP_Async_Request::class, 'WP_Async_Request', false );
}
