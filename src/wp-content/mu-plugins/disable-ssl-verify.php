<?php
/**
 * Plugin Name: Disable SSL Verification (DEV ONLY)
 * Description: Disables SSL certificate verification for local development.
 *              DO NOT USE IN PRODUCTION.
 */

// Disable SSL verification for WordPress HTTP API
add_filter('https_ssl_verify', '__return_false');
add_filter('https_local_ssl_verify', '__return_false');

// Also disable for cURL directly (plugins updates, etc.)
add_filter('http_request_args', function($args) {
    $args['sslverify'] = false;
    return $args;
}, 10, 1);