<?php

// The widget class
class WP_P4FDonationsWidget extends WP_Widget
{

    // Main constructor
    public function __construct()
    {
        parent::__construct(
            // Base ID of your widget
            'WP_P4FDonationsWidget',

            // Widget name will appear in UI
            __("P4F Donate Button Widget", "woocommerce-pay4fun"),

            // Widget description
            array('description' => __("This widget will add a Donate button using Pay4Fun payment method.", "woocommerce-pay4fun"),)
        );
    }

    public function init()
    {
        add_action('widgets_init', [$this, 'load_widget']);
        add_shortcode('p4f-donate', [$this, 'donate_button']);
    }

    // Register the widget
    public function load_widget()
    {
        register_widget('WP_P4FDonationsWidget');
    }



    // The widget form (for the backend )
    public function form($instance)
    {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('New title', 'wpb_widget_domain');
        }
        if (isset($instance['amount'])) {
            $amount = $instance['amount'];
        } else {
            $amount = __('Amount', 'wpb_widget_domain');
        }
        // Widget admin form
?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            <label for="<?php echo $this->get_field_id('amount'); ?>"><?php _e('Amount:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('amount'); ?>" name="<?php echo $this->get_field_name('amount'); ?>" type="text" value="<?php echo esc_attr($amount); ?>" />
        </p>
<?php
    }

    // Update widget settings
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['amount'] = (!empty($new_instance['amount'])) ? strip_tags($new_instance['amount']) : '';
        return $instance;
    }

    // Display the widget
    public function widget($args, $instance)
    {

        $title = apply_filters('widget_title', $instance['title']);

        // before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];

        // This is where you run the code and display the output
        $content = $this->donate_button(['amount' => $instance['amount']]);
        echo $content; // . $content2 . $content3;
        echo $args['after_widget'];
    }


    public function donate_button($atts)
    {
        global $wp;
        $current_url = home_url(add_query_arg([], $wp->request));
        $amount = ((isset($atts['amount']) && is_numeric(($atts['amount']))) ? filter_var($atts['amount'], FILTER_SANITIZE_NUMBER_FLOAT) : 0);
        $options = get_option('p4f_options');
        $currency = (!empty($options['currency'])) ? $options['currency'] : '';

        switch ($currency) {
            case 'BRL':
                $money = "R$ " . number_format($amount, 2, ',', '.');
                break;
            case 'USD':
                $money = "$ " . number_format($amount, 2, '.', ',');
                break;
            case 'EUR':
                $money = "&euro; " . number_format($amount, 2, '.', ',');
                break;
            case 'GBP':
                $money = "&#163; " . number_format($amount, 2, '.', ',');
                break;
            default:
                $money = number_format($amount, 2);
                break;
        }

        $Content = '';
        $Content .= '<form method="GET" action="' . get_site_url() . '/wp-json/p4f-plugin/v1/donate" ' . (isset($options['redirect_new_page']) && $options['redirect_new_page'] == 1 ? 'target="_blank"' : '') . '>';
        if (!isset($atts['amount'])) {
            $Content .= '    <label for="p4fDonateValue">Value</label>';
            $Content .= '    <input type="text" id="p4fDonateValue" name="p4fDonateValue" value="' . $amount . '">';
        } else {
            $Content .= '    <input type="hidden" id="p4fDonateValue" name="p4fDonateValue" value="' . $amount . '">';
        }
        $Content .= '    <input type="hidden" id="p4fReturnUrl" name="p4fReturnUrl" value="' . $current_url . '">';
        $Content .= '    <input type="submit" value="Donate (' . $money . ')!">';
        $Content .= '</form>';

        return $Content;
    }
}
