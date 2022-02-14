<?php

/**
 * Functions.
 */
function line( $text = '' ) {
	echo $text, PHP_EOL;
}

function run( $command, &$result_code = null ) {
	line( $command );

	$last_line = system( $command, $result_code );

	line();

	return $last_line;
}

/**
 * Gravity Forms license key.
 */
$gravityforms_license_key = getenv( 'GRAVITYFORMS_LICENSE_KEY' );

if ( empty( $gravityforms_license_key ) ) {
	echo 'Gravity Forms license key not defined in `GRAVITYFORMS_LICENSE_KEY` environment variable.';

	exit( 1 );
}

/**
 * Request info.
 */
line( '::group::Check Gravity Forms' );

$url = 'https://gravityapi.com/wp-content/plugins/gravitymanager/version.php';

$data = run(
	sprintf(
		'curl --data %s --request POST %s',
		escapeshellarg( 'key=' . $gravityforms_license_key ),
		escapeshellarg( $url )
	)
);

list( $result, $version, $url ) = explode( '||', $data );

line( 'Version: ' . $version );

line( '::endgroup::' );

/**
 * Download ZIP.
 */
line( '::group::Download Gravity Forms' );

$zip_file = tempnam( sys_get_temp_dir(), '' );

run(
	sprintf(
		'curl %s --output %s',
		escapeshellarg( $url ),
		$zip_file
	)
);

line( '::endgroup::' );

/**
 * Plugin directory.
 */
$plugins_dir = tempnam( sys_get_temp_dir(), '' );

$plugin_dir = $plugins_dir . '/gravityforms';

unlink( $plugins_dir );

/**
 * Unzip.
 */
line( '::group::Unzip Gravity Forms' );

run(
	sprintf(
		'unzip %s -d %s',
		escapeshellarg( $zip_file ),
		escapeshellarg( $plugins_dir )
	)
);

line( '::endgroup::' );

/**
 * Synchronize.
 * 
 * @link http://stackoverflow.com/a/14789400
 * @link http://askubuntu.com/a/476048
 */
line( '::group::Synchronize Gravity Forms' );

run(
	sprintf(
		'rsync --archive --delete-before --exclude=%s --exclude=%s --exclude=%s --verbose %s %s',
		escapeshellarg( '.git' ),
		escapeshellarg( '.github' ),
		escapeshellarg( 'composer.json' ),
		escapeshellarg( $plugin_dir . '/' ),
		escapeshellarg( '.' )
	)
);

line( '::endgroup::' );
