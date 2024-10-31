<?php

/**
 * Receipt page error template
 *
 * @package WooCommerce_PagSeguro/Templates
 */
if (!defined('ABSPATH')) {
    exit;
}

?>

<?php if (isset($_GET['motive'])) { ?>
    <ul class="woocommerce-error">
        <li><?php echo esc_html($_GET['motive']); ?></li>
    </ul>
<?php } ?>
<a class="button cancel" href="<?php echo esc_url($order->get_cancel_order_url()); ?>"><?php esc_html_e('Cancel your Order', 'woocommerce-pay4fun'); ?></a>