<?php
namespace Inc_Woo_We_Payments\Classes;


require_once ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';

use WC_Payment_Gateway;

class WC_We_Payments_Gateway extends WC_Payment_Gateway {

    public function __construct() {

        $this->id = 'woo_we_payments'; 
        $this->has_fields = true; 
        $this->method_title = 'We Payments Gateway - Configurações Gerais';
        $this->method_description = 'Ative e configure para utilizar os métodos We Payments.'; 
    
        $this->supports = array(
            'products'
        );
    
        // Load the settings.
        $settings_instance = new Settings_WC_We_Payments(); 
        $settings_instance->init_settings(); 

        $this->enabled = $settings_instance->get_option( 'enabled' );
        $this->testmode = 'yes' === $settings_instance->get_option( 'testmode' );
        $this->public_key = $this->testmode ? $settings_instance->get_option( 'test_public_key' ) : $settings_instance->get_option( 'public_key' );
        $this->api_key = $this->testmode ? $settings_instance->get_option( 'test_api_key' ) : $settings_instance->get_option( 'api_key' );
        $this->url = $this->testmode ? 'https://api.sandbox.wepayout.com.br' : 'https://api.wepayout.com.br';
    }
}
