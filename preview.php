<?php

//For backwards compatibility, load wordpress if it hasn't been loaded yet
//Will be used if this file is being called directly
if ( ! class_exists( 'RGForms' ) ) {
	for ( $i = 0; $i < $depth = 10; $i ++ ) {
		$wp_root_path = str_repeat( '../', $i );

		if ( file_exists( "{$wp_root_path}wp-load.php" ) ) {
			require_once( "{$wp_root_path}wp-load.php" );
			require_once( "{$wp_root_path}wp-admin/includes/admin.php" );
			break;
		}
	}

	//redirect to the login page if user is not authenticated
	auth_redirect();
}

// If user doesn't have appropriate permissions, die.
if ( ! GFCommon::current_user_can_any( array( 'gravityforms_edit_forms', 'gravityforms_create_form', 'gravityforms_preview_forms' ) ) ) {
	die( esc_html__( "You don't have adequate permission to preview forms.", 'gravityforms' ) );
}

/**
 * Fires when a Form Preview is loaded.
 *
 * The hook fires when a Form Preview is initialized and before it is rendered.
 *
 * @since 2.5
 */
do_action( 'gform_preview_init' );

// Load form display class.
require_once( GFCommon::get_base_path() . '/form_display.php' );

// Get form ID.
$form_id = absint( rgget( 'id' ) );

// Get form object.
$form = RGFormsModel::get_form_meta( $_GET['id'] );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta http-equiv="Imagetoolbar" content="No" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php esc_html_e( 'Form Preview', 'gravityforms' ) ?></title>
	<?php

		// If form exists, enqueue its scripts.
		if ( ! empty( $form ) ) {
			GFFormDisplay::enqueue_form_scripts( $form );
		}

		wp_enqueue_script( 'gform_preview' );

		wp_print_head_scripts();

		$styles = array();

		/**
		 * Filters Form Preview Styles.
		 *
		 * This filter modifies the enqueued styles for the Form Preview. Any handles returned in the array
		 * will be loaded in the Preview header (if they've been registered with wp_register_style).
		 *
		 * @since 2.4
		 *
		 * @param array $styles An empty array representing the currently-active styles.
		 * @param array $form An array representing the current Form.
		 *
		 * @return array An array of handles to enqueue in the header.
		 */
		$styles = apply_filters( 'gform_preview_styles', $styles, $form );

		if ( ! empty( $styles ) ) {
			wp_print_styles( $styles );
		}

		/**
		 * Fire before the closing <head> tag of the preview page.
		 *
		 * @since 2.4.19
		 *
		 * @param int $form_id The ID of the form currently being previewed.
		 */
		do_action( 'gform_preview_header', $form_id );

	?>
</head>
<body <?php body_class(); ?>>
<?php
/**
 * Fire after the opening <body> tag of the preview page.
 *
 * @since 2.4.19
 *
 * @param int $form_id The ID of the form currently being previewed.
 */
do_action( 'gform_preview_body_open', $form_id );
?>
<div id="preview_top">
	<div id="preview_hdr">

		<div>

			<span class="toggle_helpers">
				<input type="checkbox" name="showgrid" id="showgrid" value="Y" class="show-grid-input" /><label for="showgrid" class="show-grid-label"><?php esc_html_e( 'display grid', 'gravityforms' ) ?></label>
				<input type="checkbox" name="showme" id="showme" value="Y" class="show-helpers-input" /><label for="showme" class="show-helpers-label"><?php esc_html_e( 'show structure', 'gravityforms' ) ?></label>
			</span>

			<h2><?php esc_html_e( 'Form Preview', 'gravityforms' ) ?> : ID <?php echo $form_id; ?></h2>
		</div>
	</div>
	<div id="preview_note" class="preview_notice">
		<?php esc_html_e( 'Note: This is a simple form preview. This form may display differently when added to your page based on normal inheritance from parent theme styles.', 'gravityforms' ) ?> <i class="hidenotice" title="<?php esc_html_e( 'dismiss', 'gravityforms' ) ?>"></i>
	</div>
</div>
<div id="helper_legend_container">
	<ul id="helper_legend">
		<li class="showid">Element ID</li>
		<li class="showclass">Class Name</li>
	</ul>
</div>
<div id="preview_form_container">
	<span class="rule25"></span>
	<span class="rule33"></span>
	<span class="rule50"></span>
	<span class="rule66"></span>
	<span class="rule75"></span>
	<?php echo RGForms::get_form( $form_id, true, true, true ); ?>
</div>
<div id="browser_size_info"></div>

<?php

wp_print_footer_scripts();

/**
 * Fires in the footer of a Form Preview page
 *
 * @param int $_GET['id'] The ID of the form currently being previewed
 */
do_action( 'gform_preview_footer', $form_id );
?>

</body>
</html>