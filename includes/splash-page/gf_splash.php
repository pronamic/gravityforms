<article class="gform-splash" data-js="gform-splash-page">
	<header class="gform-splash__header">
		<img src="<?php echo esc_url( GFCommon::get_base_url() ); ?>/images/logos/gravity-logo-white.svg" alt="Gravity Forms"/>
		<h1 class="screen-reader-text">Gravity Forms</h1>
		<span class="gf-splash-version"><?php echo esc_html( $this->about_version ); ?></span>
	</header>

	<div class="gform-splash__body">
		<?php
		$text = __( 'Build Better Forms with Gravity Forms 2.6', 'gravityforms' );
		echo wp_kses_post( $this->tags->headline( array( 'text' => $text ) ) );

		$text  = '<h3>' . __( 'What’s New with 2.6!', 'gravityforms' ) . '</h3>
			<p>' . __( 'Thanks for installing Gravity Forms 2.6. With this latest release you will find a number of exciting new features alongside numerous updates and additions to enhance your form building experience.', 'gravityforms' ) . '</p>
			<p>' . __( 'From a new intuitive form embed process, to a relocated form Submit button, and an impressive redesign of the UI for the Choices based fields, 2.6 is packed full of the features you need to create beautiful, accessible, and high-converting forms.', 'gravityforms' ) . '</p>
			<a href="https://www.gravityforms.com/blog/gravity-forms-2-6-release" target="_blank" class="gform-button gform-button--size-r gform-button--white" aria-label="' . __( 'Read more about the Gravity Forms 2.6 release', 'gravityforms' ) . '">' . __( 'Read More', 'gravityforms' ) . '</a>';
		$image = array(
			'src' => $this->img_dir . 'collage.png',
			'alt' => __( 'Screenshot of a collection of new features in Gravity Forms 2.6', 'gravityforms' ),
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

		$text  = '<h3>' . __( 'An Inline Form Submit Button', 'gravityforms' ) . '</h3>
			<p>' . __( 'In 2.6 the form Submit button has been moved out of Form Settings and into the form editor - a feature long awaited by many.', 'gravityforms' ) . '</p>
			<p>' . __( 'Due to this relocation, you will now be able to easily inline your Submit button, as well as alter the settings, all without needing to leave the editor or use CSS Ready Classes.', 'gravityforms' ) . '</p>  
			<p>' . __( 'You can select to position the Submit button at the bottom of a form or within the last line alongside other form fields - creating form layouts to your exact specifications has never been easier!', 'gravityforms' ) . '</p>
			<a href="https://docs.gravityforms.com/submit-button" target="_blank" class="gform-button gform-button--size-r gform-button--white" aria-label="' . __( 'Read more about the inline form submit button', 'gravityforms' ) . '">' . __( 'Read More', 'gravityforms' ) . '</a>';
		$image = array(
			'src' => $this->img_dir . 'submit-button.png',
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

		$text  = '<h3>' . __( 'A New Form Embed Process', 'gravityforms' ) . '</h3>
			<p>' . __( 'The process of embedding a form in your website has been reimagined with the Gravity Forms 2.6 new Embed Form flyout.', 'gravityforms' ) . '</p>
			<p>' . __( 'From within the form editor, you can now select where you would like a form to be displayed. This can include an existing page, post, or custom post type (with the use of filters).', 'gravityforms' ) . '</p>
			<p>' . __( 'Equally, if you would like to embed a form in a new page or post, you have the option of creating both directly from within the Embed Form flyout. You can also view the form ID, as well as copy the form’s shortcode if required.', 'gravityforms' ) . '</p>
			<p>' . __( 'This new intuitive Embed Form flyout will streamline your form creation process, saving time and enabling you to publish your forms faster than ever before.', 'gravityforms' ) . '</p>
			<a href="https://docs.gravityforms.com/embed-form-flyout/" target="_blank" class="gform-button gform-button--size-r gform-button--white" aria-label="' . __( 'Read more about the new Embed Form flyout', 'gravityforms' ) . '">' . __( 'Read More', 'gravityforms' ) . '</a>';
		$image = array(
			'src' => $this->img_dir . 'embed.png',
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

		$text  = '<h3>' . __( 'An Updated Choices UI', 'gravityforms' ) . '</h3>
			<p>' . __( 'If you regularly use fields that utilize Choices - Radio Buttons, Checkboxes, and Multi Select, to name a few - then you’re going to love the updated 2.6 Choices user interface.', 'gravityforms' ) . '</p>
			<p>' . __( 'With Gravity Forms 2.6 you will find a new and improved Choices flyout that is responsive to page width. This extra space allows for a much better user experience, enabling you to easily view and manage the Choices options within the form editor.', 'gravityforms' ) . '</p>
			<p>' . __( 'The expandable Choices flyout also sees support for Bulk Choices, as well as our most popular third-party add-ons, again ensuring you can easily edit each choice alongside making any necessary alterations to settings.', 'gravityforms' ) . '</p>
			<a href="https://docs.gravityforms.com/edit-choices-flyout/" target="_blank" class="gform-button gform-button--size-r gform-button--white" aria-label="' . __( 'Read more about the updated Choices UI', 'gravityforms' ) . '">' . __( 'Read More', 'gravityforms' ) . '</a>';
		$image = array(
			'src' => $this->img_dir . 'choices.png',
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

		$text = __( 'Developer Features', 'gravityforms' );
		echo wp_kses_post( $this->tags->headline( array( 'text' => $text ) ) );

		$image = array(
			'src' => $this->img_dir . 'submit-code.png',
			'alt' => __( 'Screenshot of submit button code.', 'gravityforms' ),
		);
		echo wp_kses_post(
			$this->tags->full_width_image(
				array(
					'image'             => $image,
					'container_classes' => 'gform-splash__section--image-spread-left gform-splash__section--image-spread-right gform-splash__section--image-spread-down',
				)
			)
		);

		$text = '<h3>' . __( 'Submit Button Layout Options', 'gravityforms' ) . '</h3>
			<p>' . __( 'The submit button and its settings have been moved to the form editor, but the underlying data structure hasn\'t changed, so button settings will continue to work the same way they always have. This gives users the power to create more flexible layouts without resorting to Ready Classes or custom CSS. Creating single-line forms that fit in a footer or widget is now easier than ever!', 'gravityforms' ) . '</p>';
		echo wp_kses_post( $this->tags->full_width_text( array( 'text' => $text ) ) );

		$column_1 = '<h4>' . __( 'Ajax Saving for Forms', 'gravityforms' ) . '</h4>
			<p>' . __( 'The form editor now saves your form changes using Ajax, giving you a much faster experience when making updates. There are also some new', 'gravityforms' ) . ' <a href="https://docs.gravityforms.com/gform_form_saving_action_event" target="_blank">' . __( 'actions', 'gravityforms' ) . '</a> ' . __( 'and', 'gravityforms' ) . ' <a href="https://docs.gravityforms.com/gform_form_saving_filter_event" target="_blank">' . __( 'filters', 'gravityforms' ) . '</a> ' . __( 'available that ship with this new feature.', 'gravityforms' ) . '</p>';
		$column_2 = '<h4>' . __( 'Support for Custom Post Types', 'gravityforms' ) . '</h4>
			<p>' . __( 'The new Embed Form flyout allows you to quickly embed your current form into new or existing content. The post types available in the UI are filterable, so make sure to', 'gravityforms' ) . ' <a href="https://docs.gravityforms.com/gform_embed_post_types" target="_blank">' . __( 'check our documentation', 'gravityforms' ) . '</a> ' . __( 'if you wish to add any of your own custom post types. ', 'gravityforms' ) . '</p>';
		$column_3 = '<h4>' . __( 'Developer Tools (Coming Soon!)', 'gravityforms' ) . '</h4>
			<p>' . __( 'We have performed major upgrades to our tooling, build process, and libraries, and in the coming releases we’ll be sharing these with you in the form of NPM packages that will be at your disposal. Stay tuned!', 'gravityforms' ) . '</p>';
		$columns  = array(
			$column_1,
			$column_2,
			$column_3,
		);
		echo wp_kses_post( $this->tags->equal_columns( array( 'columns' => $columns ) ) );
		?>

		<footer class="gform-splash__footer">
			<img src="<?php echo esc_url( $this->img_dir ); ?>support.png" alt="<?php esc_attr_e( 'Avatars of Gravity Forms support team members', 'gravityforms' ); ?>">
			<h4>
				<?php esc_html_e( 'Still have questions?', 'gravityforms' ); ?>
			</h4>
			<p>
				<?php esc_html_e( 'Can\'t find what you\'re looking for? Please chat with our friendly team.', 'gravityforms' ); ?>
			</p>
			<a class="gform-button gform-button--primary gform-button--size-l" href="https://www.gravityforms.com/help/" target="blank" title="<?php esc_attr_e( 'Submit a ticket to our support team', 'gravityforms' ); ?>">
				<?php esc_html_e( 'Submit A Ticket', 'gravityforms' ); ?>
			</a>
		</footer>

	</div>

</article>
