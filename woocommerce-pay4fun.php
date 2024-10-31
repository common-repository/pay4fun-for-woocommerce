<?php

/**
 * @version 1.0.0
/*
Plugin Name: Pay4Fun for WooCommerce
Plugin URI: http://wordpress.org/plugins/woocommerce-pay4fun
Description: Includes Pay4Fun as a payment gateway to WooCommerce and also adds Donation capabilities to Wordpress.
Version: 1.0.0
Author: Pay4Fun
Author URI: https://www.p4f.com/
License: GPL v2.0
Text Domain: woocommerce-pay4fun
Domain Path: /languages
WC requires at least: 4.0.0
WC tested up to:      4.6.2
 */

// Make sure we don't expose any info if called directly
defined('ABSPATH') || exit;

// Plugin constants.
define('P4F_VERSION', '4.1.6');
define('P4F_MINIMUM_WP_VERSION', '4.0');
define('P4F_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('P4F_DELETE_LIMIT', 100000);
define('WC_PAY4FUN_VERSION', '1.0.0');
define('WC_PAY4FUN_PLUGIN_FILE', __FILE__);
define('P4FWC_ENCRYPTION_KEY', '');
define('P4FWC_ENCRYPTION_SALT', '');


add_action('init', function () {
    load_plugin_textdomain('woocommerce-pay4fun', false, dirname(plugin_basename(WC_PAY4FUN_PLUGIN_FILE)) . '/languages/');
});


if (!class_exists('P4FWC_Logviewer')) {
    include_once P4F_PLUGIN_DIR . 'helpers/class-log-viewer.php';
}

if (!class_exists('P4FWC_DataEncryption')) {
    include_once P4F_PLUGIN_DIR . 'helpers/class-data-encryption.php';
}

if (!class_exists('Pay4Fun')) {
    include_once P4F_PLUGIN_DIR . 'helpers/class-pay4fun.php';
}


if (!class_exists('WP_P4FRestAPI')) {
    include_once P4F_PLUGIN_DIR . 'includes/class-wp-p4f-rest-api.php';
    add_action('plugins_loaded', array(new WP_P4FRestAPI(), 'init'));
}

if (!class_exists('WP_P4FSettingsPage')) {
    include_once P4F_PLUGIN_DIR . 'includes/class-wp-p4f-settings-page.php';
    add_action('plugins_loaded', array(new WP_P4FSettingsPage(), 'init'));
}

if (!class_exists('WP_P4FDonationsPage')) {
    include_once P4F_PLUGIN_DIR . 'includes/class-wp-p4f-donations-page.php';
    add_action('plugins_loaded', array(new WP_P4FDonationsPage(), 'init'));
}

if (!class_exists('WP_P4FDonationsWidget')) {
    include_once P4F_PLUGIN_DIR . 'includes/class-wp-p4f-donations-widget.php';
    add_action('plugins_loaded', array(new WP_P4FDonationsWidget(), 'init'));
}

if (!class_exists('WC_Pay4Fun')) {
    include_once P4F_PLUGIN_DIR . 'includes/class-wc-pay4fun.php';
    add_action('plugins_loaded', array('WC_Pay4Fun', 'init'));
    add_filter('woocommerce_valid_order_statuses_for_cancel', 'validate_cancelling', 999, 2);
    function validate_cancelling($statuses, $order = '')
    {
        return array('pending', 'processing', 'on-hold', 'failed');
    }
}
