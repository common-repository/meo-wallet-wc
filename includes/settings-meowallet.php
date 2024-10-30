<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for Meo Wallet Gateway
 */
$data = array(
    'enabled' => array(
        'title' => __('Activate/Deativate', 'meo-wallet-wc'),
        'label' => __('Activate MEO Wallet', 'meo-wallet-wc'),
        'type' => 'checkbox',
        'description' => __('Activate MEO Wallet', 'meo-wallet-wc'),
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __('Title', 'meo-wallet-wc'),
        'type' => 'text',
        'description' => __('Give a title to be shown during the payment process', 'meo-wallet-wc'),
        'default' => __('MEO Wallet', 'meo-wallet-wc')
    ),
    'description' => array(
        'title' => __('Description', 'meo-wallet-wc'),
        'type' => 'textarea',
        'description' => __('A description of the Meo Wallet service to be read by your clients', 'meo-wallet-wc'),
        'default' => __('Pay with MEO Wallet - MEO Wallet, Multibanco, Credit Card', 'meo-wallet-wc')
    ),
    'apikey_live' => array(
        'title' => __('API Key', 'meo-wallet-wc'),
        'type' => 'text',
        'description' => __('Insert your Meo Wallet API key. Not the same as Meo Wallet Sandbox API Key. <br />To get your API key, click <a target="_blank" href="https://www.wallet.pt/login/">here</a>', 'meo-wallet-wc'),
        'default' => '',
        'class' => 'production_settings sensitive'
    ),
    'apikey_sandbox' => array(
        'title' => __('Sandbox API Key', 'meo-wallet-wc'),
        'type' => 'text',
        'description' => __('Insert your Meo Wallet Sandbox API key. <br />To get your API key, click <a target="_blank" href="https://www.sandbox.meowallet.pt/login/">here</a>', 'meo-wallet-wc'),
        'default' => '',
        'class' => 'sandbox_settings sensitive'
    ),
    'environment' => array(
        'title' => __('Choose your enviorment', 'meo-wallet-wc'),
        'type' => 'select',
        'label' => __('Actiavte Meo Wallet in test mode!', 'meo-wallet-wc'),
        'description' => __('Choose your Enviorment between Test and Production', 'meo-wallet-wc'),
        'default' => 'sandbox',
        'options' => array(
            'sandbox' => __('Test', 'meo-wallet-wc'),
            'production' => __('Production', 'meo-wallet-wc'),
        ),
    ),
    'debug' => array(
        'title' => __('Debug Log', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'woocommerce'),
        'default' => 'no',
        'description' => sprintf(__('Log Meo Wallet events, inside <code>%s</code>', 'woocommerce'), wc_get_log_file_path('meo-wallet-wc'))
    ),
);

if (get_woocommerce_currency() != 'EUR') {
    $data['ex_to_euro'] = array(
        'title' => __("Exchange rate", 'meo-wallet-wc'),
        'type' => 'text',
        'description' => 'Exchange rate for Euro',
        'default' => '1',
    );
}
return $data;
