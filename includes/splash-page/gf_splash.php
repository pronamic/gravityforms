<article class="gform-splash" data-js="gform-splash-page">

	<header class="gform-splash__header">
		<img class="gform-logo" src="<?php echo esc_url( GFCommon::get_base_url() ); ?>/images/logos/gravity-logo-white.svg" alt="Gravity Forms"/>
		<h1><?php esc_html_e( 'New Fields Added in Gravity Forms 2.9!', 'gravityforms' ); ?></h1>
		<p><?php esc_html_e( 'The new Image Choice and Multiple Choice fields give you more flexibility and control when creating forms.', 'gravityforms' ); ?></p>
		<a class="gform-button gform-button--size-height-xxl gform-button--white gform-button--width-auto gform-button--icon-trailing"  href="<?php echo esc_url( admin_url( 'admin.php?page=gf_new_form' ) ); ?>" title="<?php esc_attr_e( 'Get started with a new form', 'gravityforms' ); ?>">
			<span class="gform-button__text gform-button__text--inactive gform-typography--size-text-md"><?php esc_html_e( 'Get Started', 'gravityforms' ); ?></span>
			<span class="gform-common-icon gform-common-icon--arrow-narrow-right gform-button__icon"></span>
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
                        <img
                            src="<?php echo $this->img_dir . 'g2.svg'; ?>"
                            alt="<?php esc_attr_e( 'G2 logo', 'gravityforms' ); ?>"
                            class="gform-reviews__logo"
                        />
                        <span class="gform-reviews__stars gform-reviews__stars--icon">
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
                        <img
                            src="<?php echo $this->img_dir . 'trustpilot.svg'; ?>"
                            alt="<?php esc_attr_e( 'Trustpilot logo', 'gravityforms' ); ?>"
                            class="gform-reviews__logo"
                        />
                        <span class="gform-reviews__stars gform-reviews__stars--image">
                            <img
                                src="<?php echo $this->img_dir . 'trustpilot-rating.svg'; ?>"
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
            $text  = '<h3>' . __( 'Image Choice Field', 'gravityforms' ) . '</h3>
                <p>' . __( 'A picture is worth a thousand words! The new Image Choice field lets you add stylish images straight from the media library to your choices. Easily create beautiful forms with eye-catching images that speak to your users.', 'gravityforms' ) . '</p>
                <a href="https://docs.gravityforms.com/image-choice-field/" target="_blank" class="gform-button gform-button--size-height-xl gform-button--primary-new gform-button--width-auto" aria-label="' . __( 'Read more about the Image Choice field', 'gravityforms' ) . '" title="' . __( 'Read more about the Image Choice field', 'gravityforms' ) . '"><span class="gform-button__text gform-button__text--inactive gform-typography--size-text-sm">' . __( 'Read More', 'gravityforms' ) . '</span></a>';
            $image = array(
                'src' => $this->img_dir . 'image-choice-field.png',
                'alt' => __( 'Screenshot of the Image Choice field in Gravity Forms 2.9', 'gravityforms' ),
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

            $text  = '<h3>' . __( 'Multiple Choice Field', 'gravityforms' ) . '</h3>
                <p>' . __( 'The Multiple Choice field is a new, flexible way to let users choose one or many options. Gather the information you need, while ensuring a high-end experience for those submitting the form.', 'gravityforms' ) . '</p>
                <a href="https://docs.gravityforms.com/multiple-choice-field/" target="_blank" class="gform-button gform-button--size-height-xl gform-button--primary-new gform-button--width-auto" aria-label="' . __( 'Read more about the Multiple Choice field', 'gravityforms' ) . '" title="' . __( 'Read more about the Multiple Choice field', 'gravityforms' ) . '"><span class="gform-button__text gform-button__text--inactive gform-typography--size-text-sm">' . __( 'Read More', 'gravityforms' ) . '</span></a>';
            $image = array(
                'src' => $this->img_dir . 'multiple-choice-field.png',
                'alt' => __( 'Screenshot of the Multiple Choice field in Gravity Forms 2.9', 'gravityforms' ),
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

            $col1_icon = $style_icon = $this->tags->build_image_html(
                array(
                    'src' => $this->img_dir . 'editor-design-improvements-icon.svg',
                    'alt' => __( 'Icon of color swatches', 'gravityforms' ),
                    'width' => '52px',
                    'height' => '52px',
                    'class' => 'image--width-auto',
                )
            );
            $col1 = $col1_icon . '<h4>' . __( 'Editor Design Improvements', 'gravityforms' ) . '</h4>
                <p>' . __( 'We’ve brought our beautiful Orbital form theme into the form editor! With 2.9 you’ll find a more consistent and visually-pleasing form editing experience, closely mirroring how your form will look on the front end.', 'gravityforms' ) . ' <a href="https://docs.gravityforms.com/gravity-forms-2-9-key-features/" title="' . __( 'Read more about the Gravity Forms 2.9 editor design improvements', 'gravityforms' ) . '" target="_blank">' . __( 'Read More', 'gravityforms' ) . '</a></p>';

            $col2_icon = $style_icon = $this->tags->build_image_html(
                array(
                    'src' => $this->img_dir . 'editor-accessibility-improvements-icon.svg',
                    'alt' => __( 'Icon of accessibility symbol', 'gravityforms' ),
                    'width' => '52px',
                    'height' => '52px',
                    'class' => 'image--width-auto',
                )
            );
            $col2 = $col2_icon . '<h4>' . __( 'Editor Accessibility Improvements', 'gravityforms' ) . '</h4>
                <p>' . __( 'As part of our continuing commitment to make form building available to everyone, we have improved the accessibility of the form editor. If you rely on keyboard navigation or screen readers, you’ll now have an easier time navigating the field settings.', 'gravityforms' ) . ' <a href="https://docs.gravityforms.com/gravity-forms-2-9-key-features/" title="' . __( 'Read more about the Gravity Forms 2.9 editor accessibility improvements', 'gravityforms' ) . '" target="_blank">' . __( 'Read More', 'gravityforms' ) . '</a></p>';

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
