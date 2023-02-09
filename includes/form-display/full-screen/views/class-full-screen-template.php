<?php

// In WP 5.5+, form ID is passed in $args. Otherwise, we need to grab it from our filter.
$form_id = empty( $args['form_id'] ) ? apply_filters( 'gform_full_screen_form_id', 0 ) : $args['form_id'];

gravity_form( $form_id );