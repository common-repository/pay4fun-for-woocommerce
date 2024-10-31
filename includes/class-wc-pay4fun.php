<?php

/**
 * Plugin's main class
 *
 * @package WooCommerce_Pay4Fun
 */

/**
 * WooCommerce bootstrap class.
 */
class WC_Pay4Fun
{

    /**
     * Initialize the plugin public actions.
     */
    public static function init()
    {
        // Load plugin text domain.
        //add_action('init', array(__CLASS__, 'load_plugin_textdomain'));
        //add_action('woocommerce_admin_field_encrypted', array('WC_Pay4Fun_Gateway', 'show_encrypted_field'));

        // Checks with WooCommerce is installed.
        if (class_exists('WC_Payment_Gateway')) {
            self::includes();

            add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));
            add_filter('plugin_action_links_' . plugin_basename(WC_PAY4FUN_PLUGIN_FILE), array(__CLASS__, 'plugin_action_links'));
        } else {
            add_action('admin_notices', array(__CLASS__, 'woocommerce_missing_notice'));
        }
    }


    /**
     * Load the plugin text domain for translation.
     */
    // public static function load_plugin_textdomain()
    // {
    //     load_plugin_textdomain('woocommerce-pay4fun', false, dirname(plugin_basename(WC_PAY4FUN_PLUGIN_FILE)) . '/languages/');
    // }

    /**
     * Action links.
     *
     * @param array $links Action links.
     *
     * @return array
     */
    public static function plugin_action_links($links)
    {
        $plugin_links   = array();
        $plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=pay4fun')) . '">' . __('Settings', 'woocommerce-pay4fun') . '</a>';

        return array_merge($plugin_links, $links);
    }

    /**
     * Includes.
     */
    private static function includes()
    {
        include_once dirname(__FILE__) . '/class-wc-pay4fun-gateway.php';
    }

    /**
     * Add the gateway to WooCommerce.
     *
     * @param  array $methods WooCommerce payment methods.
     *
     * @return array          Payment methods with Pay4Fun.
     */
    public static function add_gateway($methods)
    {
        $methods[] = 'WC_Pay4Fun_Gateway';

        return $methods;
    }

    /**
     * WooCommerce missing notice.
     */
    public static function woocommerce_missing_notice()
    {
        include P4F_PLUGIN_DIR . 'views/html-notice-missing-woocommerce.php';
    }
}
