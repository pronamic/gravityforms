<article class="gform-splash" data-js="gform-splash-page">
	<header class="gform-splash__header">
		<img class="gform-logo" src="<?php echo esc_url( GFCommon::get_base_url() ); ?>/images/logos/gravity-logo-white.svg" alt="Gravity Forms"/>
		<h1><?php esc_html_e( 'Build Forms Quickly Using Gravity Forms 2.7', 'gravityforms' ); ?></h1>
		<p><?php esc_html_e( 'Never start from scratch again. Gravity Forms comes with several pre-built form templates to help you save even more time. We truly made the most beginner-friendly WordPress forms plugin in the market.', 'gravityforms' ); ?></p>
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
		<img class="gform-splash__header-bottom-image" src="<?php echo $this->img_dir . 'hero-image.png'; ?>" alt="<?php esc_attr_e( 'Screenshot of the new form template library', 'gravityforms' ); ?>">
	</header>

	<div class="gform-splash__body">
		<?php
		$text = __( 'New Form Template Library', 'gravityforms' );
		echo wp_kses_post( $this->tags->headline( array( 'text' => $text ) ) );

		$text  = '<p>' . __( 'We’re celebrating the release of Gravity Forms 2.7 today! It includes some exciting new features that will make form management easier than ever.', 'gravityforms' ) . '</p>
			<p>' . __( 'With this update, you can get started building your forms quickly by selecting one of our hand-curated templates from the new Form Template Library.', 'gravityforms' ) . '</p>
			<p>' . __( 'Whether you’re looking to create a simple contact form, request a quote form, donation form, payment order form, or a subscription form, we have a form template for you.', 'gravityforms' ) . '</p>
			<p>' . // Translators: 1. opening link tag with link to Gravity Forms template library, 2. closing link tag.
				 sprintf(
					 __( 'We hope to bring you many new templates to this growing library, and look forward to any %stemplate suggestions%s you may have to make form creation even easier.', 'gravityforms' ),
					 '<a href="https://www.gravityforms.com/form-templates/">',
					 '</a>') .
				 '</p>
			<a href="https://docs.gravityforms.com/using-the-gravity-forms-template-library/" target="_blank" class="gform-button gform-button--size-height-xl gform-button--primary-new gform-button--width-auto"  aria-label="' . __( 'Read more about the Form Template Library', 'gravityforms' ) . '"><span class="gform-button__text gform-button__text--inactive gform-typography--size-text-sm">' . __( 'Read More', 'gravityforms' ) . '</span></a>';
		$image = array(
			'src' => $this->img_dir . 'template-library.png',
			'alt' => __( 'Screenshot of a collection of new features in Gravity Forms 2.7', 'gravityforms' ),
		);
		echo wp_kses_post(
			$this->tags->text_and_image(
				array(
					'text'              => $text,
					'image'             => $image,
					'image_placement'   => 'left',
					'container_classes' => 'gform-splash__section--image-spread-right',
				)
			)
		);

		$text  = '<h3>' . __( 'Faster Setup Wizard', 'gravityforms' ) . '</h3>
			<p>' . __( 'We want customers to love using Gravity Forms from the very first moment it’s installed. So we’ve redesigned and rebuilt the setup wizard from the ground up to provide a simpler, more streamlined experience.', 'gravityforms' ) . '</p>
			<p>' . __( 'New customers will appreciate the ease-of-use, while seasoned customers will find their setup times substantially reduced.', 'gravityforms' ) . '</p>
			<a href="https://docs.gravityforms.com/gravity-forms-setup-wizard/" target="_blank" class="gform-button gform-button--size-height-xl gform-button--primary-new gform-button--width-auto"  aria-label="' . __( 'Read more about the setup wizard', 'gravityforms' ) . '"><span class="gform-button__text gform-button__text--inactive gform-typography--size-text-sm">' . __( 'Read More', 'gravityforms' ) . '</span></a>';
		$image = array(
			'src' => $this->img_dir . 'onboarding-wizard.png',
			'alt' => __( 'Screenshot of the submit button in Gravity Forms 2.6.', 'gravityforms' ),
		);
		echo wp_kses_post(
			$this->tags->text_and_image(
				array(
					'text'              => $text,
					'image'             => $image,
					'image_placement'   => 'right',
					'container_classes' => 'gform-splash__section--image-spread-left gform-splash__section--image-spread-right gform-splash__section--image-spread-down',
				)
			)
		);

		$text  = '<h3>' . __( 'Better Form Styling Out of the Box', 'gravityforms' ) . '</h3>
			<p>' . __( 'If you’ve ever had trouble getting your forms to look exactly the way you want, you’ll love the new styling options we’ve added to the block settings. Tweak the color scheme, change the size of inputs, modify button styles, and much more - all from within the WordPress block editor.', 'gravityforms' ) . '</p>
			<p>' . __( 'Now each of your forms can be tailored to look and feel exactly how you need them to, without the need to write a single line of CSS.', 'gravityforms' ) . '</p>
			<a href="https://docs.gravityforms.com/block-themes-and-style-settings/" target="_blank" class="gform-button gform-button--size-height-xl gform-button--primary-new gform-button--width-auto"  aria-label="' . __( 'Read more about form styling', 'gravityforms' ) . '"><span class="gform-button__text gform-button__text--inactive gform-typography--size-text-sm">' . __( 'Read More', 'gravityforms' ) . '</span></a>';
		$image = array(
			'src' => $this->img_dir . 'block-settings.png',
			'alt' => __( 'Screenshot of the embed form UI in Gravity Forms 2.6.', 'gravityforms' ),
		);
		echo wp_kses_post(
			$this->tags->text_and_image(
				array(
					'text'              => $text,
					'image'             => $image,
					'image_placement'   => 'left',
					'container_classes' => 'gform-splash__section--image-spread-left gform-splash__section--image-spread-right gform-splash__section--image-spread-down',
				)
			)
		);

		$text  = '<h3>' . __( 'Increased Form Spam Protection', 'gravityforms' ) . '</h3>
			<p>' . __( 'We’ve improved the anti-spam honeypot protection to be much more effective. We added an additional method for detecting spam entries, and let you choose whether you want the entries to go to a dedicated spam folder or be blocked entirely.', 'gravityforms' ) . '</p>
			<p>' . __( 'Simply enable the honeypot in your form settings and enjoy the benefits of much less form spam.', 'gravityforms' ) . '</p>
			<a href="https://docs.gravityforms.com/spam-honeypot-enhancements/" target="_blank" class="gform-button gform-button--size-height-xl gform-button--primary-new gform-button--width-auto"  aria-label="' . __( 'Read more about spam protection', 'gravityforms' ) . '"><span class="gform-button__text gform-button__text--inactive gform-typography--size-text-sm">' . __( 'Read More', 'gravityforms' ) . '</span></a>';
		$image = array(
			'src' => $this->img_dir . 'spam-protection.png',
			'alt' => __( 'Screenshot of the choices UI in Gravity Forms 2.6.', 'gravityforms' ),
		);
		echo wp_kses_post(
			$this->tags->text_and_image(
				array(
					'text'              => $text,
					'image'             => $image,
					'image_placement'   => 'right',
					'container_classes' => 'gform-splash__section--image-spread-left gform-splash__section--image-spread-right gform-splash__section--image-spread-down',
				)
			)
		);

		$text = __( 'More Developer Features', 'gravityforms' );
		echo wp_kses_post( $this->tags->headline( array( 'text' => $text ) ) );

		$image = array(
			'src' => $this->img_dir . 'packages.png',
			'alt' => __( 'Screenshot of packages code.', 'gravityforms' ),
		);
		echo wp_kses_post(
			$this->tags->full_width_image(
				array(
					'image'             => $image,
					'container_classes' => 'gform-splash__section--image-spread-left gform-splash__section--image-spread-right gform-splash__section--image-spread-down',
				)
			)
		);

		$text = '<h3>' . __( 'Brand New Components', 'gravityforms' ) . '</h3>
			<p>' . __( 'Gravity Forms 2.7 adds over 100 new components to our collection. It’s been a long journey but we are super proud of where we landed, and think it’s going to position Gravity Forms as a useful tool for a whole new group of developers.', 'gravityforms' ) . '</p>
			<p>' . __( 'Best of all, we\'re making our components available as a package that add-on developers can use in their own products to provide a consistent user experience across the entire ecosystem.', 'gravityforms' ) . '</p>';
		echo wp_kses_post( $this->tags->full_width_text( array( 'text' => $text ) ) );
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
