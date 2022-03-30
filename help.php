<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * Class GFHelp
 * Displays the Gravity Forms Help page
 */
class GFHelp {

	/**
	 * Displays the Gravity Forms Help page
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function help_page() {
		if ( ! GFCommon::ensure_wp_version() ) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		?>
        <link rel="stylesheet" href="<?php echo GFCommon::get_base_url(); ?>/assets/css/dist/admin<?php echo $min; ?>.css" />
		<div class="wrap gforms_help <?php echo GFCommon::get_browser_class() ?>">

			<h2><?php esc_html_e( 'How can we help you?', 'gravityforms' ); ?></h2>

			<div class="gf_help_content">
				<p><?php printf( esc_html__( "Please review the %sdocumentation%s first. If you still can't find the answer %sopen a support ticket%s and we will be happy to answer your questions and assist you with any problems.", 'gravityforms' ), '<a href="https://docs.gravityforms.com/" target="_blank">', '</a>', '<a href="https://www.gravityforms.com/support/" target="_blank">', '</a>' ); ?></p>
			</div>

			<form id="gf_help_page_search" action="https://docs.gravityforms.com" target="_blank">
				<div class="search_box">
					<input type="text" name="s" placeholder="<?php esc_attr_e('Search Our Documentation', 'gravityforms')?>" />
				</div>
			</form>

			<div id="gforms_helpboxes">
				<div class="gforms_helpbox user_documentation">
					<div class="helpbox_header"></div>
					<div class="resource_list">
						<h3><?php esc_html_e( 'User Documentation', 'gravityforms' ); ?></h3>
						<ul>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/create-a-new-form/">
									<?php esc_html_e( 'Creating a Form', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/category/user-guides/getting-started/add-form-to-site/">
									<?php esc_html_e( 'Embedding a Form', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/reviewing-form-submissions/">
									<?php esc_html_e( 'Reviewing Form Submissions', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/configuring-confirmations/">
									<?php esc_html_e( 'Configuring Confirmations', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/configuring-notifications-in-gravity-forms/">
									<?php esc_html_e( 'Configuring Notifications', 'gravityforms' ); ?>
								</a>
							</li>
						</ul>
					</div>
				</div>

				<div class="gforms_helpbox developer_documentation">
					<div class="helpbox_header"></div>
					<div class="resource_list">
						<h3><?php esc_html_e( 'Developer Documentation', 'gravityforms' ); ?></h3>
						<ul class="resource_list">
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/getting-started-gravity-forms-api-gfapi/">
									<?php esc_html_e( 'Discover the Gravity Forms API', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/api-functions/">
									<?php esc_html_e( 'API Functions', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/category/developers/rest-api/">
									<?php esc_html_e( 'REST API', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/category/developers/php-api/add-on-framework/">
									<?php esc_html_e( 'Add-On Framework', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/gfaddon/">
									<?php esc_html_e( 'GFAddOn', 'gravityforms' ); ?>
								</a>
							</li>
						</ul>
					</div>
				</div>

				<div class="gforms_helpbox designer_documentation">
					<div class="helpbox_header"></div>
					<div class="resource_list">
						<h3><?php esc_html_e( 'Designer Documentation', 'gravityforms' ); ?></h3>
						<ul class="resource_list">
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/category/user-guides/design-and-layout/css-selectors/">
									<?php esc_html_e( 'CSS Selectors', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/css-targeting-examples/">
									<?php esc_html_e( 'CSS Targeting Examples', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/css-ready-classes/">
									<?php esc_html_e( 'CSS Ready Classes', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/gform_field_css_class/">
									<?php esc_html_e( 'gform_field_css_class', 'gravityforms' ); ?>
								</a>
							</li>
							<li>
								<a target="_blank" href="https://docs.gravityforms.com/gform_noconflict_styles/">
									<?php esc_html_e( 'gform_noconflict_styles', 'gravityforms' ); ?>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<img id="hexagons-bg-orange" src="<?php echo GFCommon::get_base_url(); ?>/images/hexagons-bg-orange.svg" />
		<img id="hexagons-bg-dark-blue" src="<?php echo GFCommon::get_base_url(); ?>/images/hexagons-bg-dark-blue.svg" />
		<img id="hexagons-bg-light-blue" src="<?php echo GFCommon::get_base_url(); ?>/images/hexagons-bg-light-blue.svg" />
	<?php
	}
}
