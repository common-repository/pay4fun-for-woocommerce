<?php

class WP_P4FDonationsPage
{
    /**
     * Holds the values to be used in the fields callbacks.
     */
    private $table_name;
    private $dbVersion = '1.0';

    /**
     * Start up.
     */
    public function __construct()
    {
        $this->table_name = $GLOBALS['wpdb']->prefix . 'p4f_donations';
        $this->create_database();
    }

    public function init()
    {
        if (is_admin()) {
            add_option('p4f_db_version', $this->dbVersion);
            add_action('plugins_loaded', [$this, 'create_database']);
            add_action('admin_menu', [$this, 'add_plugin_page']);
            add_action('admin_head', [$this, 'js_delete_donations_action']);
        }
    }

    public function create_database()
    {
        global $wpdb;
        $installed_ver = get_option('p4f_db_version');

        if ($installed_ver != $this->dbVersion) {
            $this->table_name = $wpdb->prefix . 'p4f_donations';

            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE {$this->table_name} (
                id               mediumint(9) NOT NULL AUTO_INCREMENT,
                transaction_id   varchar(30) NOT NULL,
                time             datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                donator_name     varchar(100) NOT NULL,
                order_number     varchar(30) NOT NULL,
                amount           decimal(10,2) NOT NULL,
                status           varchar(100) NOT NULL,
                p4f_url          varchar(1000) NOT NULL,
                origin_url       varchar(1000) NOT NULL,
                PRIMARY KEY  (id)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
            update_option('p4f_db_version', $this->dbVersion);
        }
    }

    /**
     * Add options page.
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            __('Pay4Fun Donations', 'woocommerce-pay4fun'),
            __('Pay4Fun Donations', 'woocommerce-pay4fun'),
            'manage_options',
            'p4f-donations-page',
            [$this, 'p4f_create_donations_page']
        );
    }


    public function js_delete_donations_action()
    {
        $options = get_option('p4f_options');
        $url = get_site_url() . '/wp-json/p4f-plugin/v1/donations/clear';
?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {

                $('.myajax').click(function() {
                    if (confirm('<?php echo __('This will delete all donations registries, are you sure?', 'woocommerce-pay4fun'); ?>')) {
                        var data = {
                            action: 'remove_donations',
                            token: '<?php echo (!empty($options) ? $options['merchant_id'] : ''); ?>'
                        };

                        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                        $.get('<?php echo $url; ?>', data, function(response) {
                            alert('<?php echo __('Donations Cleared!', 'woocommerce-pay4fun'); ?>');
                            window.location.reload();
                            //window.location.href = window.location.href;
                            //history.go(0);
                        });
                    }
                });


            });
        </script>
    <?php

    }


    /**
     * Options page callback.
     */
    public function p4f_create_donations_page()
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY time DESC LIMIT 100", OBJECT); ?>

        <style>
            table,
            caption,
            tbody,
            tfoot,
            thead,
            tr,
            th,
            td {
                margin: 0;
                font-size: 100%;
                font: inherit;
                vertical-align: baseline;
                padding: 0.25rem;
                text-align: left;
                border: 1px solid #ccc;
            }

            table {
                border-collapse: collapse;
                border-spacing: 0;
                width: 100%;
            }

            tbody tr:nth-child(odd) {
                background: #eee;
            }

            thead th {
                font-size: 16px;
                font-weight: 400;
                text-align: left;
                padding: 20px;
            }
        </style>
        <div class="wrap">
            <h1><?php echo __('Pay4Fun Donations (Latest 100 transactions)', 'woocommerce-pay4fun'); ?></h1>
            <table border="1">
                <thead align="left" style="display: table-header-group">
                    <tr>
                        <th>ID</th>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Order Number</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>P4F URL</th>
                        <th>Origin URL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($results as $row) {
                    ?>
                        <tr class="item_row">
                            <td><?php echo $row->id; ?></td>
                            <td><?php echo $row->transaction_id; ?></td>
                            <td><?php echo $row->time; ?></td>
                            <td><?php echo $row->donator_name; ?></td>
                            <td><?php echo $row->order_number; ?></td>
                            <td><?php echo $row->amount; ?></td>
                            <td><?php echo $row->status; ?></td>
                            <td><a target='blank' href='<?php echo $row->p4f_url; ?>'>Link</a></td>
                            <td><a target='blank' href='<?php echo $row->origin_url; ?>'>Link</a></td>
                        </tr>
                    <?php
                    } ?>
                </tbody>
            </table>
            <a href="" class="myajax"><?php echo __('CLEAR ENTRIES', 'woocommerce-pay4fun'); ?></a>
        </div>
<?php
    }
}
