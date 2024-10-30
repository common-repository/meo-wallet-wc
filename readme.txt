=== MEO Wallet Payment Gateway ===
Contributors: webds
Tags: meowallet,meo wallet, payment, gateway, woocommerce
Requires at least: 4.0
Tested up to: 4.8.2
Stable tag: 2.0.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.en.html

MEO Wallet Payment Gateway is a plugin for WooCommerce that provides possibility for merchants to accept payments from Wallet.pt

 == Description ==
 
MEO Wallet Payment Gateway Plugin is a payment gateway for WooCommerce.
Users will be redirected to MEO Wallet website where they can pay via Wallet, Multibanco (The Portuguese payment method) and VISA or MASTERCARD.

Checkout our portfolio at: http://www.webds.pt

== Installation ==

1. Upload the entire `meowallet_wc` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. On your WooCommerce go to Settings > Checkout TAB > Meo Wallet
4. Configure your options


== Frequently Asked Questions ==

= How do I configure it to work? =

1. Grab the MERCHANT-API-KEY key from your merchant backoffice page (sandbox version) from the "O Meu Negócio/Chaves da API" subsection;
2. Setup the options

== Screenshots ==

1. Setup Meo Wallet
2. Payment option on checkout
3. Redirects to Meo Wallet and back to site


== Changelog ==

= 2.0.3 =
Updated readme Wordpress Version

= 2.0.2 =
Fix get_order_discount deprecated function changed to get_discount_total

= 2.0.1 =
Added stock removal option

= 2.0 =
Fixed translation PT not showing
Added send email to admin after email payment complete


= 1.1.3 =
Added missing Meo Wallet Logo

= 1.1.2 =
Fixed Wrong Multibanco data showing
Fixed no email being sent after purchase
Added Language Support
Added new Icons
Updated meowallet API call

= 1.1.1 =
Fixed Wrong back url information, now showing correct url
Updated meowallet API call

= 1.1 =
Added call back url for information

= 1.0 =
Just released into the wild.