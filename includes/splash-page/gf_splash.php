<article class="gform-splash" data-js="gform-splash-page">
	<header class="gform-splash__header">
		<img class="gform-logo" src="<?php echo esc_url( GFCommon::get_base_url() ); ?>/images/logos/gravity-logo-white.svg" alt="Gravity Forms"/>
		<h1><?php esc_html_e( 'Edit Forms with Ease in Gravity Forms 2.8!', 'gravityforms' ); ?></h1>
		<p><?php esc_html_e( 'The new Compact View makes it a cinch to edit long forms!', 'gravityforms' ); ?></p>
		<a class="gform-button gform-button--size-height-xxl gform-button--white gform-button--width-auto gform-button--icon-trailing"  href="<?php echo esc_url( admin_url( 'admin.php?page=gf_new_form' ) ); ?>" title="<?php esc_attr_e( 'Get started with a new form', 'gravityforms' ); ?>">
			<span class="gform-button__text gform-button__text--inactive gform-typography--size-text-md"><?php esc_html_e( 'Get Started', 'gravityforms' ); ?></span>
			<span class="gform-common-icon gform-common-icon--arrow-narrow-right gform-button__icon"></span>
		</a>
		<div class="gform-reviews">
			<a href="https://www.g2.com/products/gravity-forms/reviews" title="<?php esc_html_e( 'Read reviews of Gravity Forms on G2', 'gravityforms' ); ?>">
				<img src="<?php echo $this->img_dir . 'g2.svg'; ?>" alt="<?php esc_attr_e( 'G2 logo', 'gravityforms' ); ?>">
				<span class="gform-reviews__stars">
					<span class="gform-common-icon gform-common-icon--star"></span>
					<span class="gform-common-icon gform-common-icon--star"></span>
					<span class="gform-common-icon gform-common-icon--star"></span>
					<span class="gform-common-icon gform-common-icon--star"></span>
					<span class="gform-common-icon gform-common-icon--star"></span>
				</span>
				200+ <?php esc_html_e( '4.7 Stars', 'gravityforms' ); ?>
			</a>
		</div>
	</header>

	<div class="gform-splash__body">
		<?php
		$text  = '<h3>' . __( 'Form Editor Compact View', 'gravityforms' ) . '</h3>
			<p>' . __( 'Our new compact view makes it easier than ever to edit your forms! If you have long forms, you no longer have to scroll for ages to find the field you’re looking for. The compact view gives you a bird’s eye view of your form, so you can quickly find the fields you need to edit.', 'gravityforms' ) . ' <a href="https://docs.gravityforms.com/compact-view/" title="' . __( 'Read more about Compact View', 'gravityforms' ) . '" target="_blank">' . __( 'Read More', 'gravityforms' ) . '</a></p>';
		$image = array(
			'src' => $this->img_dir . 'compact-view.png',
			'alt' => __( 'Screenshot of the compact view in Gravity Forms 2.8', 'gravityforms' ),
		);

		echo wp_kses_post(
			$this->tags->equal_columns(
				array(
					'columns' => array(
						$this->tags->build_image_html( $image ),
						$text,
					),
					'container_classes' => 'column--vertical-center',
				),
			)
		);

		$style_icon = $this->tags->build_image_html(
			array(
				'src' => $this->img_dir . 'icon-swatch.png',
				'alt' => __( 'Icon of color swatches', 'gravityforms' ),
				'width' => '48px',
				'height' => '48px',
				'class' => 'image--width-auto',
			)
		);
		$db_icon = $this->tags->build_image_html(
			array(
				'src' => $this->img_dir . 'icon-db.png',
				'alt' => __( 'Icon of a database', 'gravityforms' ),
				'width' => '48px',
				'height' => '48px',
				'class' => 'image--width-auto',
			)
		);
		$col1text  = $style_icon . '<h4>' . __( 'Orbital Form Styling', 'gravityforms' ) . '</h4>
			<p>' . __( 'You might have noticed that we recently added a new setting so that you can use the beautiful and customizable Orbital form theme everywhere on your site, including shortcodes! Soon you’ll see Orbital in more places, and you’ll find more ways to customize it.', 'gravityforms' ) . ' <a href="https://docs.gravityforms.com/block-themes-and-style-settings/" title="' . __( 'Read more about styling your forms', 'gravityforms' ) . '" target="_blank">' . __( 'Read More', 'gravityforms' ) . '</a></p>';
		$col2text = $db_icon . '<h4>' . __( 'Performance Improvements', 'gravityforms' ) . '</h4>
			<p>' . __( 'We are always striving to improve the performance of Gravity Forms. In this release, you’ll notice smaller CSS files so that you don’t have to sacrifice performance to have good-looking forms.', 'gravityforms' ) . '</p>';
		echo wp_kses_post(
			$this->tags->equal_columns(
				array(
					'columns' => array(
						$col1text,
						$col2text,
					),
				),
			)
		);

		?>


		<footer class="gform-splash__footer">
			<h4>
				<?php esc_html_e( 'Ready to get started?', 'gravityforms' ); ?>
			</h4>
			<p>
				<?php esc_html_e( 'We believe there\'s a better way to manage your data and forms. Are you ready to create a form? Let\'s go!', 'gravityforms' ); ?>
			</p>
			<a class="gform-button gform-button--size-height-xxl gform-button--white gform-button--width-auto gform-button--icon-trailing"  href="<?php echo esc_url( admin_url( 'admin.php?page=gf_new_form' ) ); ?>" title="<?php esc_attr_e( 'Get started with a new form', 'gravityforms' ); ?>">
				<span class="gform-button__text gform-button__text--inactive gform-typography--size-text-md"><?php esc_html_e( 'Get Started', 'gravityforms' ); ?></span>
				<span class="gform-common-icon gform-common-icon--arrow-narrow-right gform-button__icon"></span>
			</a>
		</footer>

		<div class="gform-splash__background gform-splash__background-one"></div>
		<div class="gform-splash__background gform-splash__background-two"></div>
		<div class="gform-splash__background gform-splash__background-three"></div>
		<div class="gform-splash__background gform-splash__background-four"></div>
		<div class="gform-splash__background gform-splash__background-five"></div>
		<div class="gform-splash__background gform-splash__background-six"></div>
		<div class="gform-splash__background gform-splash__background-seven"></div>

	</div>

</article>
