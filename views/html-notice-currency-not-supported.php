<?php

/**
 * Admin View: Notice - Currency not supported.
 *
 * @package WooCommerce_Pay4Fun/Admin/Notices
 */

if (!defined('ABSPATH')) {
	exit;
}

?>

<div class="error inline">
	<p><strong><?php _e('Pay4Fun is Disabled', 'woocommerce-pay4fun'); ?></strong>: <?php /* translators: %s: Currency Code */ printf(__('Currency <code>%s</code> is not supported. Works only with Brazilian Real, US Dollar or Euro.', 'woocommerce-pay4fun'), get_woocommerce_currency()); ?>
	</p>
</div>