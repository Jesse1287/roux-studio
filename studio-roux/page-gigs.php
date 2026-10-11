<?php
/**
 * Template Name: Gigs
 */
$destination = current_user_can('manage_options')
    ? admin_url('edit.php?post_type=studio_gig')
    : home_url('/');
wp_safe_redirect($destination);
exit;
