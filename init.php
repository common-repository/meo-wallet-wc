<?php

/*
  Plugin Name: MEO Wallet Payment Gateway
  Plugin URI: https://www.webds.pt
  Description: MEO Wallet Payment Gateway is the best away to accept payments via MEO Wallet, Multibanco and Credit/Debit Card.
  Version: 2.0.2
  Author: WebDS
  Author URI: https://www.webds.pt
  License: GPLv3
  Text Domain: meo-wallet-wc
 */

add_action('plugins_loaded', 'meowallet_wc_init');

function meowallet_wc_init() {

    /**
     * Localisation
     */
    load_plugin_textdomain('meo-wallet-wc', false, plugin_basename(dirname(__FILE__)) . '/languages');

    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once dirname(__FILE__) . '/class/meowallet.class.php';

    /**
     * Add the Gateway to WooCommerce
     * */
    function add_meowallet_wc($methods) {
        $methods[] = 'WC_MEOWALLET_GW';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_meowallet_wc');
}
