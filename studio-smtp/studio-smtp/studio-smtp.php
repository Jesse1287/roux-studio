<?php
/*
Plugin Name: Studio SMTP
Description: Routes all WordPress emails through Gmail SMTP for Roux's Audio Production.
Version: 2.1
Author: Roux
*/

if (!defined('ABSPATH')) exit;

add_action('phpmailer_init', function($phpmailer) {
    if (!defined('STUDIO_SMTP_PASSWORD') || empty(STUDIO_SMTP_PASSWORD)) return;
    $email = defined('STUDIO_SMTP_EMAIL') ? STUDIO_SMTP_EMAIL : '';
    $phpmailer->isSMTP();
    $phpmailer->Host       = 'smtp.gmail.com';
    $phpmailer->Port       = 587;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = $email;
    $phpmailer->Password   = STUDIO_SMTP_PASSWORD;
    $phpmailer->SMTPSecure = 'tls';
    $phpmailer->From       = $email;
    $phpmailer->FromName   = "Roux's Audio Production";
});

add_filter('wp_mail_from', function() { return defined('STUDIO_SMTP_EMAIL') ? STUDIO_SMTP_EMAIL : ''; });
add_filter('wp_mail_from_name', function() { return "Roux's Audio Production"; });
