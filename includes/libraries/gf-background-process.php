<?php
/**
 * @depecated 2.9.8
 * @remove-in 3.1
 */
_deprecated_file( __FILE__, '2.9.8', GF_PLUGIN_DIR_PATH . 'includes/async/class-gf-background-process.php (Gravity_Forms\Gravity_Forms\Async\GF_Background_Process)' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
if ( ! class_exists( 'GF_Background_Process' ) ) {
	require_once GF_PLUGIN_DIR_PATH . 'includes/async/class-gf-background-process.php';
	class_alias( Gravity_Forms\Gravity_Forms\Async\GF_Background_Process::class, 'GF_Background_Process', false );
}
