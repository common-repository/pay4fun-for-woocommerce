<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('p4f_options');
delete_option('p4f_db_version');
delete_option('widget_wp_p4fdonationswidget');
delete_option('woocommerce_pay4fun_settings');
