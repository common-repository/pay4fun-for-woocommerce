<?php

/**
 * Admin View: Notice - Merchant Secret missing
 *
 * @package WooCommerce_Pay4Fun/Admin/Notices
 */

if (!defined('ABSPATH')) {
	exit;
}

?>

<div class="error inline">
	<p><strong><?php _e('Pay4Fun Disabled', 'woocommerce-pay4fun'); ?></strong>: <?php _e('You should inform your merchant secret.', 'woocommerce-pay4fun'); ?>
	</p>
</div>