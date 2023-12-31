<?php
/*
* Plugin Name:          WEpayments for WooCommerce
* Description:          Plugin de integração com o gateway de pagamento - WEpayments
* Author:               Flow Digital Creative Ecommerce
* Author URI:           https://goflow.digital
* Version:              1.0.0
* License: 				GPLv3
* License URI: 			http://www.gnu.org/licenses/gpl-3.0.html
* Text Domain:          woo_we_payments
* WC requires at least: 6.3.0
* WC tested up to:      6.3.0
*/


defined('ABSPATH') or die("You can't acess directly");

if(file_exists((dirname(__FILE__) . '/vendor/autoload.php'))){
    require_once((dirname(__FILE__) . '/vendor/autoload.php'));
}

/*
**Define main path and url
*/
define( 'woo_we_payments_BASENAME', plugin_basename(__FILE__));
define( 'woo_we_payments_PATH', plugin_dir_path((__FILE__)));
define( 'woo_we_payments_URL', plugin_dir_url(__FILE__));



use Inc_Woo_We_Payments\Base\Activate;
use Inc_Woo_We_Payments\Base\Deactive;


function activate_woo_we_payments_plugin(){
    Activate::activate();

}
function deactivate_woo_we_payments_plugin(){
    Deactive::deactivate();
}

register_activation_hook(__FILE__, 'activate_woo_we_payments_plugin');
register_deactivation_hook(__FILE__, 'deactivate_woo_we_payments_plugin');

register_activation_hook(__FILE__, 'needs_brazilian_woocommerce_plugin');

function needs_brazilian_woocommerce_plugin() {
    if (!is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php')) {
        add_action('admin_notices', 'woo_we_payments_dependency_warning');
    }
}


function woo_we_payments_dependency_warning() {
    echo '<div class="notice notice-error"><p>';
    echo 'O plugin WEpayments para WooCommerce precisa que o plugin Brazilian Market on WooCommerce esteja ativo. Por favor, instale ou ative-o.';
    echo '</p></div>';
}



register_activation_hook(__FILE__, 'activate_woo_we_payments_plugin');
register_deactivation_hook(__FILE__, 'deactivate_woo_we_payments_plugin');



if(class_exists('Inc_Woo_We_Payments\\Init')){
    Inc_Woo_We_Payments\Init::register_services();
}




