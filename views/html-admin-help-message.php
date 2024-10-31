<?php

/**
 * Admin help message.
 *
 * @package WooCommerce_Pay4Fun/Admin/Settings
 */

if (!defined('ABSPATH')) {
	exit;
}

if (apply_filters('woocommerce_pay4fun_help_message', true)) : ?>
	<div class="updated inline woocommerce-message">
		<p><?php echo esc_html(__('Donations Credentials should be set in ', 'woocommerce-pay4fun')); ?>
			<a href="<?php echo get_admin_url() . "options-general.php?page=p4f-settings-page"; ?>"><?php esc_html_e('Pay4Fun Donations Settings Page', 'woocommerce-pay4fun'); ?></a>
		</p>
	</div>
<?php endif;
