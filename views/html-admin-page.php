<?php

/**
 * Admin options screen.
 *
 * @package WooCommerce_PagSeguro/Admin/Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<h3><?php echo esc_html($this->method_title); ?></h3>

<?php
if ('yes' == $this->get_option('enabled')) {
    if (!$this->using_supported_currency() && !class_exists('woocommerce_wpml')) {
        include dirname(__FILE__) . '/html-notice-currency-not-supported.php';
    }

    global $triedToSaveSameMerchant;
    if ($this->check_merchant_conflict($this->get_id()) || $triedToSaveSameMerchant) {
        include dirname(__FILE__) . '/html-notice-same-merchant-donation.php';
    } else {
        if ('' === $this->get_id()) {
            include dirname(__FILE__) . '/html-notice-id-missing.php';
        }

        if ('' === $this->get_key()) {
            include dirname(__FILE__) . '/html-notice-key-missing.php';
        }

        if ('' === $this->get_secret()) {
            include dirname(__FILE__) . '/html-notice-secret-missing.php';
        }
    }

    // if ('' === $this->get_logo()) {
    //     include dirname(__FILE__) . '/html-notice-logo-missing.php';
    // }
}
?>

<?php echo wpautop($this->method_description); ?>

<?php include dirname(__FILE__) . '/html-admin-help-message.php'; ?>

<table class="form-table">
    <?php $this->generate_settings_html(); ?>
</table>