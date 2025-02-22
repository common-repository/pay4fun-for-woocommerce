<?php

/**
 * Missing WooCommerce notice.
 *
 * @package WooCommerce_Pay4Fun/Admin/Notices
 */

if (!defined('ABSPATH')) {
	exit;
}

$is_installed = false;

if (function_exists('get_plugins')) {
	$all_plugins  = get_plugins();
	$is_installed = !empty($all_plugins['woocommerce/woocommerce.php']);
}

?>

<div class="error">
	<p><strong><?php esc_html_e('Pay4Fun for WooCommerce', 'woocommerce-pay4fun'); ?></strong> <?php esc_html_e('depends on the last version of WooCommerce to work!', 'woocommerce-pay4fun'); ?></p>

	<?php if ($is_installed && current_user_can('install_plugins')) : ?>
		<p><a href="<?php echo esc_url(wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active'), 'activate-plugin_woocommerce/woocommerce.php')); ?>" class="button button-primary"><?php esc_html_e('Active WooCommerce', 'woocommerce-pay4fun'); ?></a></p>
	<?php else :
		if (current_user_can('install_plugins')) {
			$url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');
		} else {
			$url = 'http://wordpress.org/plugins/woocommerce/';
		}
	?>
		<p><a href="<?php echo esc_url($url); ?>" class="button button-primary"><?php esc_html_e('Install WooCommerce', 'woocommerce-pay4fun'); ?></a></p>
	<?php endif; ?>
</div>