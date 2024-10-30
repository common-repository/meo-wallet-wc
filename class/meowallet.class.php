<?php
/**
 * Meo Wallet Standard Payment Gateway
 *
 * Provides a Meo Wallet Standard Payment Gateway for WooCommerce
 *
 * @class 		WC_MEOWALLET_GW
 * @extends		WC_Payment_Gateway
 * @version     0.5
 * @license     GPLv3
 * @author 		WebDS
 */
if (!defined('ABSPATH'))
    exit;

if (!class_exists("MEOWallet_API")) {
    require_once('api.class.php');
}

class WC_MEOWALLET_GW extends WC_Payment_Gateway {

    /** @var boolean Whether or not logging is enabled */
    public static $log_enabled = false;

    /** @var WC_Logger Logger instance */
    public static $log = false;
    protected $SANDBOX_URL = 'https://services.sandbox.meowallet.pt/api/v2';
    protected $WALLET_URL = 'https://services.wallet.pt/api/v2';

    /**
     * Constructor for the gateway.
     */
    function __construct() {
        global $wallet;


        // Default Variables
        $this->id = 'meowallet_wc';
        $this->icon = plugins_url('assets/images/mw.png', dirname(__FILE__));
        $this->has_fields = false;
        $this->method_title = __('Meo Wallet', 'meo-wallet-wc');
        $this->method_description = __('Accepts payments via Meo Wallet, MB & Credit Cards', 'meo-wallet-wc');
        //$this->notify_url = WC()->api_request_url('WC_MEOWALLET_GW');
        $this->notify_url = str_replace('https:', 'http:', add_query_arg('wc-api', 'wc_gateway_meowallet', home_url('/')));
        ###############
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
        ###############
        // Main setings
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->env = $this->get_option('environment');

        $this->url = ($this->env == 'production') ? $this->WALLET_URL : $this->SANDBOX_URL;
        $this->apikey = ($this->env == 'production') ? $this->get_option('apikey_live') : $this->get_option('apikey_sandbox');

        $this->to_euro_rate = $this->get_option('to_euro_rate');
        ###############

        self::$log_enabled = $this->get_option('debug');

        $wallet = new MEOWallet_API($this->url, $this->apikey);

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_api_wc_gateway_meowallet', array($this, 'meowallet_callback'));

        // Customer Emails
        add_filter('multibanco_wallet_email_instructions_table_html', array($this, 'meowallet_email_instructions_table_html'), 1, 4);
        // Customer Emails
        add_filter('multibanco_wallet_cc_instructions_table_html', array($this, 'meowallet_cc_instructions_table_html'), 1, 1);
        // MeoWallet - Email payment received text filter
        add_filter('meowallet_instructions_payment_received', array($this, 'meowallet_email_instructions_payment_received'));

        add_action('woocommerce_email_after_order_table', array($this, 'email_instructions'), 12, 3);
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
    }

    /**
     * Logging method
     * @param  string $message
     */
    public static function log($message) {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = new WC_Logger();
            }
            self::$log->add('meo-wallet-wc', $message);
        }
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {

        $this->form_fields = include( plugin_dir_path(dirname(__FILE__)) . 'includes/settings-meowallet.php' );
    }

    /**
     * Admin Options
     *
     * Setup the gateway settings screen.
     * Override this in your gateway.
     *
     * @since 1.0.0
     */
    public function admin_options() {
        $image_path = plugins_url('assets/images/mw.png', dirname(__FILE__));
        ?>
        <!-- <h3><?php _e('MEO Wallet', 'meo-wallet-wc'); ?></h3> -->
        <a  class="webds_mf_logo" href="http://www.webds.pt" target="_blank"><img src="http://www.webds.pt/webds_logomail.png" alt="WebDS" /></a>
        <center>
            <?php echo "<a href=\"https://wallet.pt\"><img src=\"$image_path\" /></a>"; ?><br>
            <small>by <a href="http://www.webds.pt" target="_blank">WebDS</a></small>
        </center>
        <table class="form-table">
            <?php
            $this->generate_settings_html();
            ?>
            <tr valign="top">
                <th class="titledesc" scope="row">
                    <label><?php _e('Activate MB return data', 'meo-wallet-wc'); ?></label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Activate MB return data', 'meo-wallet-wc'); ?></span></legend>
                        <p class="description"><?php _e('In order to recieve MB generated data to show on your site you will have to send Meo Wallet a ticekt requesting activation.', 'meo-wallet-wc'); ?></p>
                        <p><?php _e('Login on <strong>Meo Wallet</strong> and click', 'meo-wallet-wc'); ?> <a href="https://wallet.pt/dashboard/account/contact/add">Apoio ao Cliente</a></p>
                        <p><b><?php _e('Message example', 'meo-wallet-wc'); ?></b>: Exmo Srºs, Venho por este meio requisitar a activação para a API das referencias multibanco sem ter de saltar para a página da Meo Wallet. Com os melhores cumprimentos. Aguardo vosso feedback.</p>
                        <br>
                        <p><?php _e('After activation confirmation, generate a new API KEY and change it here. Go to', 'meo-wallet-wc'); ?> <strong>O meu negócio</strong> > <strong>Chaves API</strong></p>
                    </fieldset>
                </td>
            </tr>
            <tr valign="top">
                <th class="titledesc" scope="row">
                    <label><?php _e('Callback url', 'meo-wallet-wc'); ?></label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php _e('Callback url', 'meo-wallet-wc'); ?></span></legend>
                        <p><strong><?php echo get_site_url(); ?>/wc-api/wc_gateway_meowallet</strong></p>
                        <br>
                        <p class="description"><?php _e('MEO Wallet notifies you of changes in the status of a transaction via callbacks to your site. The callback endpoint for your site is configured on the merchant backoffice in the Edit Wallet section.', 'meo-wallet-wc'); ?></p>
                        <p><?php _e('Login on <strong>Meo Wallet</strong>', 'meo-wallet-wc'); ?> > <strong>O meu negócio</strong> > <strong>Editar Wallet</strong>, Url de Callback</p>
                    </fieldset>
                </td>
            </tr>
        </table>

        <div class="webds_mf_footer">
            <?php _e('A company', 'meo-wallet-wc'); ?><br/>
            <a href="https://www.webhs.pt"><img src="https://www.webhs.pt/logowebhs.png" alt="WebHS" /></a><br>
            <?php _e('Web hosting solutions, domain register and SSL certificates', 'meo-wallet-wc'); ?>
        </div>
        <?php
    }

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {
        global $wallet;

        $order = new WC_Order($order_id);

        $client_details = array();
        $client_details['name'] = $_POST['billing_first_name'] . ' ' . $_POST['billing_last_name'];
        $client_details['email'] = $_POST['billing_email'];

        $client_address = array();
        $client_address['country'] = $_POST['billing_country'];
        $client_address['address'] = $_POST['billing_address_1'];
        $client_address['city'] = $_POST['billing_city'];
        $client_address['postalcode'] = $_POST['billing_postcode'];

        $items = array();
        if (sizeof($order->get_items()) > 0) {
            foreach ($order->get_items() as $item) {

                if ((int) $item['qty'] > 0) {
                    $client_items = array();
                    $client_items['id'] = $item['product_id'];
                    $client_items['name'] = $item['name'];
                    $client_items['descr'] = '';
                    $client_items['qt'] = (int) $item['qty'];

                    $items[] = $client_items;
                }
            }
        }
        if ($order->get_total_shipping() > 0) {
            $items[] = array(
                'id' => 'shippingfee',
                'price' => $order->get_total_shipping(),
                'qt' => 1,
                'name' => 'Shipping Fee',
            );
        }
        if ($order->get_total_tax() > 0) {
            $items[] = array(
                'id' => 'taxfee',
                'price' => $order->get_total_tax(),
                'qt' => 1,
                'name' => 'Tax',
            );
        }
        if ($order->get_discount_total() > 0) {
            $items[] = array(
                'id' => 'totaldiscount',
                'price' => $order->get_total_discount() * -1,
                'qt' => 1,
                'name' => 'Total Discount'
            );
        }
        if (sizeof($order->get_fees()) > 0) {
            $fees = $order->get_fees();
            $i = 0;
            foreach ($fees as $item) {
                $items[] = array(
                    'id' => 'itemfee' . $i,
                    'price' => $item['line_total'],
                    'qt' => 1,
                    'name' => $item['name'],
                );
                $i++;
            }
        }

        $params = array(
            'payment' => array(
                'client' => array(
                    'name' => $order->billing_first_name . ' ' . $order->billing_last_name,
                    'email' => $_POST['billing_email'],
                    'address' => array(
                        'country' => $_POST['billing_country'],
                        'address' => $_POST['billing_address_1'],
                        'city' => $_POST['billing_city'],
                        'postalcode' => $_POST['billing_postcode']
                    )
                ),
                'amount' => $order->get_total(),
                'currency' => 'EUR',
                'items' => $items,
                'ext_invoiceid' => (string) $order_id,
            ),
            'url_confirm' => $order->get_checkout_order_received_url(),
            'url_cancel' => $order->get_checkout_payment_url($on_checkout = false)
        );


        $response = $wallet->post('checkout', $params);

        if (!$order->has_status('on-hold'))
            $order->reduce_order_stock();

        // Remove cart
        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $response->url_redirect
        );
    }

    /**
     * Add content to the WC emails.
     *
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($order->payment_method !== $this->id)
            return;

        $ref = $this->get_mb($order->id);

        switch ($order->status) {
            case 'on-hold':
            case 'pending':
                if (is_array($ref)) {
                    echo $this->email_instructions_table_html($ref['ent'], $ref['ref'], $order->order_total);
                }
                break;
            case 'processing':
                echo $this->email_instructions_payment_received();
                break;
            default:
                return;
                break;
        }
    }

    /**
     * Get Referenica Multibanco data
     *
     * @param int $order_id
     */
    function get_mb($order_id) {
        $meta_values = get_post_meta($order_id);

        if (!empty($meta_values['_meowallet_mb_entity'][0]) && !empty($meta_values['_meowallet_mb_ref'][0])) {
            return array(
                'ent' => $meta_values['_meowallet_mb_entity'][0],
                'ref' => chunk_split($meta_values['_meowallet_mb_ref'][0], 3, ' ')
            );
        } else {
            return false;
        }
    }

    /**
     * Prepare Email
     *
     * @param int $order_id
     */
    function email_instructions_table_html($ent, $ref, $order_total = '') {

        ob_start();
        ?>
        <table>
            <tr>
                <td colspan="2">
                    <img src="<?php echo plugins_url('../assets/images/mw_big.png', __FILE__); ?>" alt="Multibanco" title="Multibanco" style="margin-top: 10px;"/>
                </td>
            </tr>
            <tr>
                <td><?php _e('Entity', 'meo-wallet-wc'); ?>:</td>
                <td><strong><?php echo $ent; ?></strong></td>
            </tr>
            <tr>
                <td><?php _e('Reference', 'meo-wallet-wc'); ?>:</td>
                <td><strong><?php echo chunk_split($ref, 3, ' '); ?></strong></td>
            </tr>
            <?php if (!empty($order_total)) { ?>
                <tr>
                    <td><?php _e('Amount', 'meo-wallet-wc'); ?>:</td>
                    <td><strong><?php echo $order_total; ?> &euro;</strong></td>
                </tr>
            <?php } ?>
        </table>
        <?php
        return apply_filters('multibanco_wallet_email_instructions_table_html', ob_get_clean(), $ent, $ref, $order_total);
    }

    /**
     * Prepare Email
     *
     * @param int $order_id
     */
    function cc_instructions_table_html() {

        ob_start();
        ?>
        <table>
            <tr>
                <td colspan="2">
                    <img src="<?php echo plugins_url('../assets/images/mw_big.png', __FILE__); ?>" alt="CC" title="CC" style="margin-top: 10px;"/>
                </td>
            </tr>
            <tr>
                <td><?php _e('MEO Wallet paid using: ', 'meo-wallet-wc'); ?></td>
                <td><strong><?php _e('Credit card', 'meo-wallet-wc'); ?></strong></td>
            </tr>
        </table>
        <?php
        return apply_filters('multibanco_wallet_cc_instructions_table_html', ob_get_clean());
    }

    function meowallet_email_instructions_table_html($html, $ent, $ref, $order_total = '') {
        ob_start();
        ?>
        <img src="<?php echo plugins_url('../assets/images/mw_big.png', __FILE__); ?>" alt="Multibanco" title="Multibanco" style="margin: 20px 0px;"/>
        <p>
            <?php _e('Entity', 'meo-wallet-wc'); ?>: <strong><?php echo $ent; ?></strong>
            <br/>
            <?php _e('Reference', 'meo-wallet-wc'); ?>: <strong><?php echo $ref; ?></strong>
            <?php if (!empty($order_total)) { ?>
                <br/>
                <?php _e('Amount', 'meo-wallet-wc'); ?>: <strong><?php echo $order_total; ?></strong>
            <?php }  ?>
        </p>
        <?php
        return ob_get_clean();
    }

    function meowallet_cc_instructions_table_html($html) {
        ob_start();
        ?>
        <img src="<?php echo plugins_url('../assets/images/mw_big.png', __FILE__); ?>" alt="Multibanco" title="Multibanco" style="margin: 20px 0px;"/>
        <br>
        <p><?php _e('MEO Wallet paid using: ', 'meo-wallet-wc'); ?> <strong><?php _e('Credit card', 'meo-wallet-wc'); ?></strong><p>
            <?php
            return ob_get_clean();
        }

        function email_instructions_payment_received() {
            ob_start();
            ?>
        <p>
            <b><?php _e('Payment received.', 'meo-wallet-wc'); ?></b>
            <br/>
            <?php _e('We will now process your order.', 'meo-wallet-wc'); ?>
        </p>
        <?php
        return apply_filters('meowallet_instructions_payment_received', ob_get_clean());
    }

    function meowallet_email_instructions_payment_received($html) {
        //We can, for example, format and return just part of the text
        ob_start();
        ?>
        <p style="color: #FF0000; font-weight: bold; margin-top: 20px;">
            <?php _e('Payment received.', 'meo-wallet-wc'); ?>
        </p>
        <?php
        return ob_get_clean();
    }

    /**
     * Output for the order received page.
     *
     * @param int $order_id
     */
    public function thankyou_page($order_id) {
        $ref = $this->get_mb($order_id);

        if ($ref) {
            echo $this->email_instructions_table_html($ref['ent'], $ref['ref']);
        } else {
            echo $this->cc_instructions_table_html();
        }
    }

    /**
     * Check for MEO Wallet Response
     */
    function meowallet_callback() {
        global $wallet;
        @ob_clean();
        $admin_email = get_option('admin_email');
        $blog_title = get_bloginfo();
        $verbatim_callback = file_get_contents('php://input');
        $callback = json_decode($verbatim_callback);


        if (false === $wallet->post('callback/verify', $verbatim_callback)) {
            $this->log('API Response: Invalid Callback ID: ' . $callback->operation_id);
            header('HTTP/1.1 400 Bad Request', true, 400);
        }

        $this->log('API Response: Callback: ' . print_r($callback, true));
        //error_log(print_r($callback, true));
        if ($callback->operation_status == 'COMPLETED') {
            $wc_order = new WC_Order(absint($callback->ext_invoiceid));
            $wc_order->add_order_note(__('MEO Wallet paid using: ', 'meo-wallet-wc') . $callback->method);
            $wc_order->payment_complete();

            $response = $wallet->get('operations/' . $callback->operation_id);

            $subject = __('MEO Wallet paid using: ', 'meo-wallet-wc') . $callback->method . ' [' . __('Encomenda', 'meo-wallet-wc') . ' : ' . $response->ext_invoiceid . ']';
            $message_html = '<html><body>';
            $message_html = '<img src="' . plugins_url('../assets/images/mw_big.png', __FILE__) . '" alt="Multibanco" title="Multibanco" style="margin: 20px 0px;"/>';
            $message_html .= '<table border="1" cellspacing="0" width="100%" style="border-color: #666;" cellpadding="10">';
            $message_html .= "<tr style='background: #eee;'><td><strong>Encomenda:</strong> </td><td>" . $response->ext_invoiceid . "</td></tr>";
            $message_html .= "<tr><td><strong>" . __('Wallet ID', 'meo-wallet-wc') . ":</strong> </td><td>" . $callback->operation_id . "</td></tr>";
            $message_html .= "<tr><td><strong>" . __('Method', 'meo-wallet-wc') . ":</strong> </td><td>" . $response->method . "</td></tr>";
            if ($response->method == 'MB') {
                $message_html .= "<tr><td><strong>" . __('Entity', 'meo-wallet-wc') . ":</strong> </td><td>" . $response->mb->entity . "</td></tr>";
                $message_html .= "<tr><td><strong>" . __('Reference', 'meo-wallet-wc') . ":</strong> </td><td>" . $response->mb->ref . "</td></tr>";
            }
            $message_html .= "<tr><td><strong>" . __('Amount', 'meo-wallet-wc') . ":</strong> </td><td>" . $response->amount . "</td></tr>";
            $message_html .= "<tr><td><strong>" . __('Wallet Comission', 'meo-wallet-wc') . ":</strong> </td><td>" . $response->fee . "</td></tr>";
            $message_html .= "</table>";
            $message_html .= "</body></html>";
            $headers = 'From: ' . $blog_title . ' <' . $admin_email . '>' . "\r\n";
            $headers .= 'Reply-To: ' . $admin_email . "\r\n";
            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n"; //Enables HTML ContentType. Remove it for Plain Text Messages

            wp_mail($admin_email, $subject, $message_html, $headers);
        }
        if ($callback->operation_status == 'PENDING') {
            $wc_order = new WC_Order(absint($callback->ext_invoiceid));

            if ($callback->method == 'MB') {
                $wc_order->add_order_note(sprintf(__('Entity: %s<br>Reference: %s<br>Amount: %s &euro;', 'meo-wallet-wc'), $callback->mb_entity, $callback->mb_ref, $callback->amount));
                add_post_meta(absint($callback->ext_invoiceid), '_meowallet_mb_entity', $callback->mb_entity, true);
                add_post_meta(absint($callback->ext_invoiceid), '_meowallet_mb_ref', $callback->mb_ref, true);
            }


            // Mark as on-hold (we're awaiting the payment)
            $wc_order->update_status('on-hold', __('Waiting Meo Wallet payment:', 'meo-wallet-wc') . ' <b>' . $callback->method . '<b><br>');
        }
    }

}
