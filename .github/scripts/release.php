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
 * Files.
 */
$work_dir = tempnam( sys_get_temp_dir(), '' );

unlink( $work_dir );

mkdir( $work_dir );

$archives_dir = $work_dir . '/archives';
$plugins_dir  = $work_dir . '/plugins';

mkdir( $archives_dir );
mkdir( $plugins_dir );

$plugin_dir = $plugins_dir . '/gravityforms';

$zip_file = $archives_dir . '/gravityforms-' . $version . '.zip';

/**
 * Download ZIP.
 */
line( '::group::Download Gravity Forms' );

run(
	sprintf(
		'curl %s --output %s',
		escapeshellarg( $url ),
		$zip_file
	)
);

line( '::endgroup::' );

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

/**
 * Git user.
 * 
 * @link https://github.com/roots/wordpress/blob/13ba8c17c80f5c832f29cf4c2960b11489949d5f/bin/update-repo.php#L62-L67
 */
run(
	sprintf(
		'git config user.email %s',
		escapeshellarg( 'info@gravityforms.com' )
	)
);

run(
	sprintf(
		'git config user.name %s',
		escapeshellarg( 'Gravity Forms' )
	)
);

/**
 * Git commit.
 * 
 * @link https://git-scm.com/docs/git-commit
 */
run( 'git add --all' );

run(
	sprintf(
		'git commit --all -m %s',
		escapeshellarg(
			sprintf(
				'Updates to %s',
				$version
			)
		)
	)
);

run( 'gh auth status' );

run( 'git push origin main' );

/**
 * GitHub release view.
 */
run(
	sprintf(
		'gh release view %s',
		$version
	),
	$result_code
);

$release_not_found = ( 1 === $result_code );

/**
 * Notes.
 */
$notes = '';

$changelog = file_get_contents( 'change_log.txt' );

$heading_position = mb_strpos( $changelog, '###', 1 );

if ( false !== $heading_position ) {
	$notes = mb_substr( $changelog, 0, $heading_position );
}

/**
 * GitHub release.
 * 
 * @todo https://memberpress.com/wp-json/wp/v2/pages?slug=change-log
 * @link https://cli.github.com/manual/gh_release_create
 */
if ( $release_not_found ) {
	run(
		sprintf(
			'gh release create %s %s --notes %s',
			$version,
			$zip_file,
			escapeshellarg( $notes )
		)
	);
}

/**
 * Cleanup.
 */
run(
	sprintf(
		'rm -f -R %s',
		escapeshellarg( $work_dir )
	)
);
