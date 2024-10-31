<?php

/**
 * Admin View: Notice - Merchant ID used in Donation
 *
 * @package WooCommerce_Pay4Fun/Admin/Notices
 */

if (!defined('ABSPATH')) {
	exit;
}

?>

<div class="error inline">
	<p><strong><?php _e('Merchant Conflict!', 'woocommerce-pay4fun'); ?></strong>: <?php _e('You cannot use the same merchant ID for both Donation and WooCommerce.', 'woocommerce-pay4fun'); ?>
	</p>
</div>