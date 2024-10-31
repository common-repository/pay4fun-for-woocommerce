<?php

class WP_P4FSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks.
     */
    private $options;
    private $crypto;

    /**
     * Start up.
     */
    public function __construct()
    {
        $this->crypto = new P4FWC_DataEncryption();
        $this->options = get_option('p4f_options');
    }

    /**
     * Add the wordpress hooks.
     */
    public function init()
    {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_plugin_page']);
            add_action('admin_init', [$this, 'page_init']);
            add_action('current_screen', [$this, 'get_current_screen']);
        }
    }

    public function get_current_screen()
    {
        $currentScreen = get_current_screen();
        if ('settings_page_p4f-settings-page' === $currentScreen->id) {
            if (!extension_loaded('openssl')) {
                add_action('admin_notices', [$this, 'p4f_no_openssl_warning']);
            }
            if ($this->check_merchant_conflict()) {
                add_action('admin_notices', [$this, 'p4f_merchant_conflict_warning']);
            } else {
                if (!isset($this->options['merchant_id']) || '' === $this->options['merchant_id']) {
                    add_action('admin_notices', [$this, 'p4f_no_merchant_id_warning']);
                }
                if (!isset($this->options['merchant_key']) || '' === $this->options['merchant_key']) {
                    add_action('admin_notices', [$this, 'p4f_no_merchant_key_warning']);
                }
                if (!isset($this->options['merchant_secret']) || '' === $this->options['merchant_secret']) {
                    add_action('admin_notices', [$this, 'p4f_no_merchant_secret_warning']);
                }
            }

            // if (!isset($this->options['merchant_logo']) || '' === $this->options['merchant_logo']) {
            //     add_action('admin_notices', [$this, 'p4f_no_merchant_logo_warning']);
            // }
        }
    }

    public function p4f_no_openssl_warning()
    {
        $class = 'notice notice-warning';
        $message = __('The OpenSSL PHP extension is not loaded in this environment. Be warned that your credentials will be stored in plain text if not enable. If you don\'t want this, enable the openssl extension in php.ini', 'woocommerce-pay4fun');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function p4f_no_merchant_id_warning()
    {
        $class = 'notice notice-warning';
        $message = __('You must provide your donation Merchant ID in order to enable this functionality.', 'woocommerce-pay4fun');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function p4f_no_merchant_key_warning()
    {
        $class = 'notice notice-warning';
        $message = __('You must provide your donation Merchant KEY in order to enable this functionality.', 'woocommerce-pay4fun');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function p4f_no_merchant_secret_warning()
    {
        $class = 'notice notice-warning';
        $message = __('You must provide your donation Merchant SECRET in order to enable this functionality.', 'woocommerce-pay4fun');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function p4f_no_merchant_logo_warning()
    {
        $class = 'notice notice-warning';
        $message = __('You must provide your donation Merchant Logo in order to enable this functionality.', 'woocommerce-pay4fun');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function p4f_merchant_conflict_warning()
    {
        $class = 'notice notice-warning';
        $message = __('You cannot use the same pay4fun merchant for both donations and woocommerce.', 'woocommerce-pay4fun');

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }

    public function check_merchant_conflict()
    {
        $wc_settings = get_option('woocommerce_pay4fun_settings');
        $wc_merchant_id = (!empty($wc_settings) && null !== $wc_settings['merchant_id'] && $wc_settings['merchant_id'] !== "") ? $this->crypto->decrypt($wc_settings['merchant_id']) : '';
        $donation_merchant_id = (!empty($this->options['merchant_id']) && null !== $this->options['merchant_id'] && $this->options['merchant_id'] !== "") ? $this->crypto->decrypt($this->options['merchant_id']) : '';

        if ($donation_merchant_id === '' && $wc_merchant_id === '') return false;
        return ($donation_merchant_id === $wc_merchant_id);
    }

    /**
     * Add options page.
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            __('Pay4Fun', 'woocommerce-pay4fun'),
            __('Pay4Fun', 'woocommerce-pay4fun'),
            'manage_options',
            'p4f-settings-page',
            [$this, 'p4f_create_admin_page']
        );
    }

    /**
     * Options page callback.
     */
    public function p4f_create_admin_page()
    {
        global $wp;
        //$current_url = add_query_arg($wp->query_string, '', home_url($wp->request));
        $current_url = home_url($_SERVER['REQUEST_URI']);

        // Set class property
        $this->options = get_option('p4f_options'); ?>
        <div class="wrap">
            <h1><?php echo __('Pay4Fun Settings', 'woocommerce-pay4fun'); ?></h1>
            <?php //settings_errors('p4f-settings-page'); 
            ?>
            <form method="post" action="options.php" onsubmit="return validate_fields()">
                <input type="hidden" name="action" value="p4f_donate_form_response">
                <input type="hidden" name="p4f_donate_nonce" value="<?php echo wp_create_nonce(); ?>">
                <?php
                // This prints out all hidden setting fields
                settings_fields('p4f_option_group');
                do_settings_sections('p4f-settings-page');
                submit_button(); ?>
            </form>
            <?php if (isset($this->options['enable_debug']) && $this->options['enable_debug'] == 1) { ?>
                <h2>Plugin Log</h2>
                <textarea style="width:100%;" rows="10"><?php echo P4FWC_LogViewer::getPluginLog(); ?></textarea>
                <h2>Pay4Fun API Log</h2>
                <textarea style="width:100%;" rows="10"><?php echo P4FWC_LogViewer::getAPILog(); ?></textarea>
                <a href="<?php echo get_site_url() . '/wp-json/p4f-plugin/v1/logs/clear?return_url=' . $current_url; ?>"><?php echo __('Clear Logs', 'woocommerce-pay4fun'); ?></a>
            <?php } ?>
        </div>
    <?php

        $wc_settings = get_option('woocommerce_pay4fun_settings');
        $wc_merchant_id = (!empty($wc_settings) && null !== $wc_settings['merchant_id'] && $wc_settings['merchant_id'] !== "") ? $this->crypto->decrypt($wc_settings['merchant_id']) : '';
        $donation_merchant_id = (!empty($this->options['merchant_id']) && null !== $this->options['merchant_id'] && $this->options['merchant_id'] !== "") ? $this->crypto->decrypt($this->options['merchant_id']) : '';
        wp_enqueue_script('p4f_donate_adminpage_validation_script', plugin_dir_url(__FILE__) . '../assets/js/p4f-donate-page-field-validation.js', array('jquery'));

        wp_localize_script(
            'p4f_donate_adminpage_validation_script',
            'merchant',
            array(
                'wc_merchant_id' => $wc_merchant_id,
                'donation_merchant_id' => $donation_merchant_id,
                'id_conflict_message' => __('Donation Merchant cannot be the same as the WooCommerce Merchant.', 'woocommerce-pay4fun'),
                'merchant_logo_tooltip' => __('This will be the log shown in Pay4Fun Checkout Page.', 'woocommerce-pay4fun'),
                'order_prefix_tooltip' => __('This Prefix will be added to the Invoice ID in order to uniquely identify this donation when using multiple sites.', 'woocommerce-pay4fun'),
                'description_tooltip' => __('This field is to help identify the donation within Pay4Fun system.', 'woocommerce-pay4fun'),
                'redirect_new_page_tooltip' => __('If enabled, when the user clicks on the donate button the checkout page will be opened in a new browser window.', 'woocommerce-pay4fun'),
                'enable_debug_tooltip' => __('This will enable logging the transactions for debugging purposes', 'woocommerce-pay4fun'),
                'sandbox_mode_tooltip' => __('Turn this on if you need to run tests to validate connectivity with pay4fun', 'woocommerce-pay4fun'),
                'merchant_id_tooltip' => __('This is the Donation Merchant ID provided to you by pay4fun. Do not use other merchant ID here.', 'woocommerce-pay4fun'),
                'merchant_secret_tooltip' => __('This is the Donation Merchant Secret provided by pay4fun.', 'woocommerce-pay4fun'),
                'merchant_key_tooltip' => __('This is the Donation Merchant Key provided by pay4fun.', 'woocommerce-pay4fun'),
                'donate_ok_page_id_tooltip' => __('This is the page to redirect the users to after a successful transaction with pay4fun.', 'woocommerce-pay4fun'),
                'donate_nok_page_id_tooltip' => __('This is the page to redirect the users to when there is an error in the transaction or the user aborted the payment.', 'woocommerce-pay4fun'),
                'currency_tooltip' => __('Select the currency that should be used to donate. All buttons will use the same currency.', 'woocommerce-pay4fun'),
                'language_tooltip' => __('Select the language to be used in the checkout page.', 'woocommerce-pay4fun')
            )
        );
    }

    /**
     * Register and add settings.
     */
    public function page_init()
    {
        register_setting(
            'p4f_option_group', // Option group
            'p4f_options', // Option name
            [$this, 'sanitize'] // Sanitize
        );

        add_settings_section(
            'p4f_credentials_section', // ID
            __('Pay4Fun Donation API Credentials', 'woocommerce-pay4fun'), // Title
            [$this, 'print_section_info'], // Callback
            'p4f-settings-page' // Page
        );

        add_settings_field(
            'merchant_id', // ID
            __('Merchant ID', 'woocommerce-pay4fun'), // Title
            [$this, 'merchant_id_callback'], // Callback
            'p4f-settings-page', // Page
            'p4f_credentials_section' // Section
        );

        add_settings_field(
            'merchant_secret',
            __('Secret', 'woocommerce-pay4fun'),
            [$this, 'merchant_secret_callback'],
            'p4f-settings-page',
            'p4f_credentials_section'
        );

        add_settings_field(
            'merchant_key',
            __('Key', 'woocommerce-pay4fun'),
            [$this, 'merchant_key_callback'],
            'p4f-settings-page',
            'p4f_credentials_section'
        );

        add_settings_section(
            'p4f_other_settings_section', // ID
            __('Other Settings', 'woocommerce-pay4fun'), // Title
            [$this, 'print_other_settings_section_info'], // Callback
            'p4f-settings-page' // Page
        );

        add_settings_field(
            'order_prefix',
            __('Order Prefix', 'woocommerce-pay4fun'),
            [$this, 'order_prefix_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );

        add_settings_field(
            'language',
            __('Language', 'woocommerce-pay4fun'),
            [$this, 'language_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );

        add_settings_field(
            'merchant_logo',
            __('Merchant Logo', 'woocommerce-pay4fun'),
            [$this, 'merchant_logo_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );

        add_settings_field(
            'currency',
            __('Currency', 'woocommerce-pay4fun'),
            [$this, 'currency_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );

        add_settings_field(
            'donate_ok_page_id',
            __('Donate Success Return Page', 'woocommerce-pay4fun'),
            [$this, 'donate_ok_page_id_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );

        add_settings_field(
            'donate_nok_page_id',
            __('Donate Error Return Page', 'woocommerce-pay4fun'),
            [$this, 'donate_nok_page_id_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );

        add_settings_field(
            'description',
            __('Description', 'woocommerce-pay4fun'),
            [$this, 'description_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );

        add_settings_field(
            'redirect_new_page',
            __('Redirect to new Page?', 'woocommerce-pay4fun'),
            [$this, 'redirect_new_page_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );

        add_settings_field(
            'sandbox_mode',
            __('Sandbox Mode?', 'woocommerce-pay4fun'),
            [$this, 'sandbox_mode_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );

        add_settings_field(
            'enable_debug',
            __('Enable Debug?', 'woocommerce-pay4fun'),
            [$this, 'enable_debug_callback'],
            'p4f-settings-page',
            'p4f_other_settings_section'
        );
    }

    /**
     * Sanitize each setting field as needed.
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; //prevent double save on first option create_function
        global $my_save_post_flag;

        if ($my_save_post_flag == 0) {
            error_log(print_r($input, true));
            $new_input = [];
            if (isset($input['merchant_id']) && '' !== $input['merchant_id']) {
                $new_input['merchant_id'] = $this->crypto->encrypt(absint($input['merchant_id']));
            }

            if (isset($input['merchant_secret']) && '' !== $input['merchant_secret']) {
                $new_input['merchant_secret'] = $this->crypto->encrypt(sanitize_text_field($input['merchant_secret']));
            }

            if (isset($input['merchant_key']) && '' !== $input['merchant_key']) {
                $new_input['merchant_key'] = $this->crypto->encrypt(sanitize_text_field($input['merchant_key']));
            }

            if (isset($input['donate_ok_page_id'])) {
                $new_input['donate_ok_page_id'] = sanitize_text_field($input['donate_ok_page_id']);
            }

            if (isset($input['donate_nok_page_id'])) {
                $new_input['donate_nok_page_id'] = sanitize_text_field($input['donate_nok_page_id']);
            }

            if (isset($input['currency'])) {
                $new_input['currency'] = sanitize_text_field($input['currency']);
            }

            if (isset($input['language'])) {
                $new_input['language'] = sanitize_text_field($input['language']);
            }

            if (isset($input['merchant_logo'])) {
                $new_input['merchant_logo'] = esc_url_raw($input['merchant_logo']);
            }

            if (isset($input['redirect_new_page'])) {
                $new_input['redirect_new_page'] = sanitize_text_field($input['redirect_new_page']);
            }

            if (isset($input['enable_debug'])) {
                $new_input['enable_debug'] = sanitize_text_field($input['enable_debug']);
            }

            if (isset($input['sandbox_mode'])) {
                $new_input['sandbox_mode'] = sanitize_text_field($input['sandbox_mode']);
            }

            if (isset($input['description'])) {
                $new_input['description'] = sanitize_text_field($input['description']);
            }

            if (isset($input['order_prefix'])) {
                $new_input['order_prefix'] = sanitize_text_field($input['order_prefix']);
            }

            $my_save_post_flag = 1;
            return $new_input;
        }
        $my_save_post_flag = 0;
        return $input;
    }

    /**
     * Print the Section text.
     */
    public function print_section_info()
    {
        echo __('Please provide you Pay4Fun API credentials', 'woocommerce-pay4fun');
    }

    /**
     * Print the Section text.
     */
    public function print_other_settings_section_info()
    {
        echo __('Remaining Settings to configure Pay4Fun Donations', 'woocommerce-pay4fun');
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function merchant_logo_callback()
    {
        printf(
            '<span class="dashicons dashicons-editor-help" id="merchant_logo_tooltip"></span><input type="text" id="merchant_logo" name="p4f_options[merchant_logo]" value="%s" size="100" />',
            isset($this->options['merchant_logo']) ? esc_attr($this->options['merchant_logo']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function order_prefix_callback()
    {
        printf(
            '<span class="dashicons dashicons-editor-help" id="order_prefix_tooltip"></span><input type="text" id="order_prefix" name="p4f_options[order_prefix]" value="%s" size="20" maxlength="5" />',
            isset($this->options['order_prefix']) ? esc_attr($this->options['order_prefix']) : 'WC_'
        );
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function description_callback()
    {
        printf(
            '<span class="dashicons dashicons-editor-help" id="description_tooltip"></span><input type="text" id="description" name="p4f_options[description]" value="%s" size="100" maxlength="40" />',
            isset($this->options['description']) ? esc_attr($this->options['description']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function redirect_new_page_callback()
    {
    ?>
        <span class="dashicons dashicons-editor-help" id="redirect_new_page_tooltip"></span>
        <input type="checkbox" id="redirect_new_page" name="p4f_options[redirect_new_page]" value="1" <?php checked(1 == (isset($this->options['redirect_new_page']) ? esc_attr($this->options['redirect_new_page']) : '')); ?> />
    <?php
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function enable_debug_callback()
    {
    ?>
        <span class="dashicons dashicons-editor-help" id="enable_debug_tooltip"></span>
        <input type="checkbox" id="enable_debug" name="p4f_options[enable_debug]" value="1" <?php checked(1 == (isset($this->options['enable_debug']) ? esc_attr($this->options['enable_debug']) : '')); ?> />
    <?php
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function sandbox_mode_callback()
    {
    ?>
        <span class="dashicons dashicons-editor-help" id="sandbox_mode_tooltip"></span>
        <input type="checkbox" id="sandbox_mode" name="p4f_options[sandbox_mode]" value="1" <?php checked(1 == (isset($this->options['sandbox_mode']) ? esc_attr($this->options['sandbox_mode']) : '')); ?> />
<?php
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function merchant_id_callback()
    {
        printf(
            '<span class="dashicons dashicons-editor-help" id="merchant_id_tooltip"></span><input type="text" id="merchant_id" name="p4f_options[merchant_id]" value="%s" size="20" />',
            isset($this->options['merchant_id']) && $this->options['merchant_id'] !== '' ? esc_attr($this->crypto->decrypt($this->options['merchant_id'])) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function merchant_secret_callback()
    {
        printf(
            '<span class="dashicons dashicons-editor-help" id="merchant_secret_tooltip"></span><input type="text" id="merchant_secret" name="p4f_options[merchant_secret]" value="%s" size="70" />',
            isset($this->options['merchant_secret']) && $this->options['merchant_secret'] !== '' ? esc_attr($this->crypto->decrypt($this->options['merchant_secret'])) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function merchant_key_callback()
    {
        printf(
            '<span class="dashicons dashicons-editor-help" id="merchant_key_tooltip"></span><input type="text" id="merchant_key" name="p4f_options[merchant_key]" value="%s" size="70" />',
            isset($this->options['merchant_key']) && $this->options['merchant_key'] !== '' ? esc_attr($this->crypto->decrypt($this->options['merchant_key'])) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function donate_ok_page_id_callback()
    {
        $select = '';
        $select_options = '';
        $select = '<span class="dashicons dashicons-editor-help" id="donate_ok_page_id_tooltip"></span><select name="p4f_options[donate_ok_page_id]">';
        if ($pages = get_pages()) {
            foreach ($pages as $page) {
                if (isset($this->options['donate_ok_page_id']) && $page->ID == $this->options['donate_ok_page_id']) {
                    $select_options .= '<option value="' . $page->ID . '" selected="selected">' . $page->post_title . '</option>';
                } else {
                    $select_options .= '<option value="' . $page->ID . '" >' . $page->post_title . '</option>';
                }
            }
        }
        $select .= $select_options;
        $select .= '</select>';
        echo $select;
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function donate_nok_page_id_callback()
    {
        $select = '';
        $select_options = '';
        $select = '<span class="dashicons dashicons-editor-help" id="donate_nok_page_id_tooltip"></span><select name="p4f_options[donate_nok_page_id]">';
        if ($pages = get_pages()) {
            foreach ($pages as $page) {
                if (isset($this->options['donate_nok_page_id']) && $page->ID == $this->options['donate_nok_page_id']) {
                    $select_options .= '<option value="' . $page->ID . '" selected="selected">' . $page->post_title . '</option>';
                } else {
                    $select_options .= '<option value="' . $page->ID . '" >' . $page->post_title . '</option>';
                }
            }
        }
        $select .= $select_options;
        $select .= '</select>';
        echo $select;
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function currency_callback()
    {
        $select = '';
        $select_options = '';
        $select = '<span class="dashicons dashicons-editor-help" id="currency_tooltip"></span><select name="p4f_options[currency]">';

        $currencies = ['BRL', 'USD', 'EUR', 'GBP'];

        foreach ($currencies as $currency) {
            if (isset($this->options['currency']) && $currency == $this->options['currency']) {
                $select_options .= '<option value="' . $currency . '" selected="selected">' . $currency . '</option>';
            } else {
                $select_options .= '<option value="' . $currency . '">' . $currency . '</option>';
            }
        }
        $select .= $select_options;
        $select .= '</select>';
        echo $select;
    }

    /**
     * Get the settings option array and print one of its values.
     */
    public function language_callback()
    {
        $select = '';
        $select_options = '';
        $select = '<span class="dashicons dashicons-editor-help" id="language_tooltip"></span><select name="p4f_options[language]">';

        $languages = ['pt-BR', 'en-US', 'es-ES'];

        foreach ($languages as $language) {
            if (isset($this->options['language']) && $language == $this->options['language']) {
                $select_options .= '<option value="' . $language . '" selected="selected">' . $language . '</option>';
            } else {
                $select_options .= '<option value="' . $language . '">' . $language . '</option>';
            }
        }
        $select .= $select_options;
        $select .= '</select>';
        echo $select;
    }
}
