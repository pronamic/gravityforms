<article class="gform-splash" data-js="gform-splash-page">

	<header class="gform-splash__header">
		<img class="gform-logo" src="<?php echo esc_url( GFCommon::get_base_url() ); ?>/images/logos/gravity-logo-white.svg" alt="Gravity Forms"/>
		<h1><?php esc_html_e( 'Gravity Forms 3.0', 'gravityforms' ); ?></h1>
		<p><?php esc_html_e( 'Now with international phone formats, accessibility by default, and Block API v3 support.', 'gravityforms' ); ?></p>
		<a class="gform-button gform-button--size-height-xxl gform-button--white gform-button--width-auto gform-button--icon-trailing"  href="<?php echo esc_url( admin_url( 'admin.php?page=gf_new_form' ) ); ?>" title="<?php esc_attr_e( 'Get started with a new form', 'gravityforms' ); ?>">
			<span class="gform-button__text gform-button__text--inactive gform-typography--size-text-md"><?php esc_html_e( 'Get Started', 'gravityforms' ); ?></span>
			<span class="gform-common-icon gform-common-icon--arrow-narrow-right gform-button__icon" aria-hidden="true"></span>
		</a>
		<div class="gform-reviews">
            <ul class="gform-reviews__list">
                <li class="gform-reviews__list-item gform-reviews__list-item--g2">
                    <a
                        href="https://www.g2.com/products/gravity-forms/reviews"
                        title="<?php esc_html_e( 'Read reviews of Gravity Forms on G2', 'gravityforms' ); ?>"
                        target="_blank"
                        class="gform-reviews__link"
                    >
                        <span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'gravityforms' ); ?></span>
                        <img
                            src="<?php echo esc_attr( $this->img_dir ) . 'g2.svg';  ?>"
                            alt="<?php esc_attr_e( 'G2 logo', 'gravityforms' ); ?>"
                            class="gform-reviews__logo"
                        />
                        <span class="gform-reviews__stars gform-reviews__stars--icon" aria-hidden="true">
                            <span class="gform-common-icon gform-common-icon--star"></span>
                            <span class="gform-common-icon gform-common-icon--star"></span>
                            <span class="gform-common-icon gform-common-icon--star"></span>
                            <span class="gform-common-icon gform-common-icon--star"></span>
                            <span class="gform-common-icon gform-common-icon--star"></span>
				        </span>
                        200+ <?php esc_html_e( '4.7 Stars', 'gravityforms' ); ?>
                    </a>
                </li>
                <li class="gform-reviews__list-item gform-reviews__list-item--trustpilot">
                    <a
                        href="https://www.trustpilot.com/review/gravityforms.com"
                        title="<?php esc_html_e( 'Read reviews of Gravity Forms on Trustpilot', 'gravityforms' ); ?>"
                        class="gform-reviews__link"
                        target="_blank"
                    >
                        <span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'gravityforms' ); ?></span>
                        <img
                            src="<?php echo esc_attr( $this->img_dir ) . 'trustpilot.svg'; ?>"
                            alt="<?php esc_attr_e( 'Trustpilot logo', 'gravityforms' ); ?>"
                            class="gform-reviews__logo"
                        />
                        <span class="gform-reviews__stars gform-reviews__stars--image">
                            <img
                                src="<?php echo esc_attr( $this->img_dir ) . 'trustpilot-rating.svg'; ?>"
                                alt="<?php esc_attr_e( 'Trustpilot rating', 'gravityforms' ); ?>"
                                class="gform-reviews__stars-image"
                            />
				        </span>
                        50+ <?php esc_html_e( '4.4 Stars', 'gravityforms' ); ?>
                    </a>
                </li>
            </ul>
		</div>
	</header>

	<div class="gform-splash__body">

        <div class="gform-splash__sections">
            <?php
            $text  = '<h3>' . esc_html__( 'International phone formats', 'gravityforms' ) . '</h3>
                <p>' . esc_html__( 'Create better experiences for users worldwide with support for international phone formats. Set a default country, let users select their country and code, and ensure accuracy with built-in validation.', 'gravityforms' ) . '</p>
                <a href="https://docs.gravityforms.com/gravity-forms-3-0-key-features/" target="_blank" class="gform-button gform-button--size-height-xl gform-button--primary-new gform-button--width-auto" title="' . esc_attr__( 'Read more about the International Phone Formats', 'gravityforms' ) . '">
                <span class="gform-button__text gform-button__text--inactive gform-typography--size-text-sm">' . esc_html__( 'Read more', 'gravityforms' ) . '</span>
                <span class="screen-reader-text">' . esc_html__( 'About the International Phone Formats', 'gravityforms' ) . '</span>
				<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'gravityforms' ) . '</span>
                &nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>';
            $image = array(
                'src' => $this->img_dir . 'phone.png',
                'alt' => esc_attr__( 'Screenshot of the International Phone Formats in Gravity Forms 3.0', 'gravityforms' ),
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

            $text  = '<h3>' . esc_html__( 'Accessibility, built in', 'gravityforms' ) . '</h3>
                <p>' . esc_html__( 'Building WCAG 2.1 AA-compliant forms just got easier. Accessible settings are now on by default for every new form you create - build more inclusive forms from the get-go.', 'gravityforms' ) . '</p>
                <a href="https://docs.gravityforms.com/gravity-forms-3-0-key-features/" target="_blank" class="gform-button gform-button--size-height-xl gform-button--primary-new gform-button--width-auto" title="' . esc_attr__( 'Read more about Accessibility by Default', 'gravityforms' ) . '">
                <span class="gform-button__text gform-button__text--inactive gform-typography--size-text-sm">' . esc_html__( 'Read more', 'gravityforms' ) . '</span>
                <span class="screen-reader-text">' . esc_html__( 'About Accessibility by Default', 'gravityforms' ) . '</span>
				<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'gravityforms' ) . '</span>
				&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>';
            $image = array(
                'src' => $this->img_dir . 'accessibility.png',
                'alt' => esc_attr__( 'Screenshot of Accessibility by Default in Gravity Forms 3.0', 'gravityforms' ),
            );

            echo wp_kses_post(
                $this->tags->equal_columns(
                    array(
                        'columns' => array(
							$text,
                            $this->tags->build_image_html( $image ),
                        ),
                        'container_classes' => 'column--vertical-center',
                    ),
                )
            );

			$text  = '<h3>' . esc_html__( 'Block API v3 support', 'gravityforms' ) . '</h3>
                <p>' . esc_html__( 'Embed and style forms in the block editor with full support for the WordPress Block API v3. Get better compatibility with current themes and tools, and a smoother experience inside the editor.', 'gravityforms' ) . '</p>
                <a href="https://docs.gravityforms.com/gravity-forms-3-0-key-features/" target="_blank" class="gform-button gform-button--size-height-xl gform-button--primary-new gform-button--width-auto" title="' . esc_attr__( 'Read more about Accessibility by Default', 'gravityforms' ) . '">
                <span class="gform-button__text gform-button__text--inactive gform-typography--size-text-sm">' . esc_html__( 'Read more', 'gravityforms' ) . '</span>
                <span class="screen-reader-text">' . esc_html__( 'About Block API v3 support', 'gravityforms' ) . '</span>
				<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'gravityforms' ) . '</span>
				&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>';
			$image = array(
				'src' => $this->img_dir . 'block.png',
				'alt' => esc_attr__( 'Screenshot of the Block Editor in Gravity Forms 3.0', 'gravityforms' ),
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

            $col1_icon = $style_icon = $this->tags->build_image_html(
                array(
                    'src' => $this->img_dir . 'javascript-icon.svg',
                    'alt' => esc_attr__( 'JavaScript icon', 'gravityforms' ),
                    'width' => '52px',
                    'height' => '52px',
                    'class' => 'image--width-auto',
                )
            );
            $col1 = $col1_icon . '<h4>' . esc_html__( 'Cleaner JavaScript libraries', 'gravityforms' ) . '</h4>
                <p>' . esc_html__( 'Updated libraries for formatted inputs and character counts provide a more modern experience behind the scenes.', 'gravityforms' ) . ' <a href="https://docs.gravityforms.com/gravity-forms-3-0-key-features/" title="' . esc_attr__( 'Read more about the Gravity Forms 3.0 Cleaner JavaScript Libraries', 'gravityforms' ) . '" target="_blank">' . esc_html__( 'Read more', 'gravityforms' ) . '<span class="screen-reader-text">' . esc_html__( 'About Cleaner JavaScript Libraries (opens in a new tab)', 'gravityforms' ) . '</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a></p>';

            $col2_icon = $style_icon = $this->tags->build_image_html(
                array(
                    'src' => $this->img_dir . 'button-icon.svg',
                    'alt' => esc_attr__( 'button icon', 'gravityforms' ),
                    'width' => '52px',
                    'height' => '52px',
                    'class' => 'image--width-auto',
                )
            );
            $col2 = $col2_icon . '<h4>' . esc_html__( 'Consistent button markup', 'gravityforms' ) . '</h4>
                <p>' . esc_html__( 'More consistent button markup makes it easier to style forms and create a more polished finish across your site.', 'gravityforms' ) . ' <a href="https://docs.gravityforms.com/gravity-forms-3-0-key-features/" title="' . esc_attr__( 'Read more about the Gravity Forms 3.0 Consistent Button Markup', 'gravityforms' ) . '" target="_blank">' . esc_html__( 'Read more', 'gravityforms' ) . '<span class="screen-reader-text">' . esc_html__( 'About Consistent Button Markup (opens in a new tab)', 'gravityforms' ) . '</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a></p>';

            echo wp_kses_post(
                $this->tags->equal_columns(
                    array(
                        'columns' => array(
                            $col1,
                            $col2,
                        ),
                        'container_classes' => 'column--vertical-center',
                    ),
                )
            );
            ?>
        </div>

		<footer class="gform-splash__footer">
			<h4>
				<?php esc_html_e( 'Ready to get started?', 'gravityforms' ); ?>
			</h4>
			<p>
				<?php esc_html_e( 'Discover what’s new in Gravity Forms 3.0 and put the latest improvements to work.', 'gravityforms' ); ?>
			</p>
			<a class="gform-button gform-button--size-height-xxl gform-button--white gform-button--width-auto gform-button--icon-trailing"  href="<?php echo esc_url( admin_url( 'admin.php?page=gf_new_form' ) ); ?>" title="<?php esc_attr_e( 'Get started with a new form', 'gravityforms' ); ?>">
				<span class="gform-button__text gform-button__text--inactive gform-typography--size-text-md"><?php esc_html_e( 'Get Started', 'gravityforms' ); ?></span>
				<span class="gform-common-icon gform-common-icon--arrow-narrow-right gform-button__icon" aria-hidden="true"></span>
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
