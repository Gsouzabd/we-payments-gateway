<?php
namespace Inc_Woo_We_Payments;

require_once __DIR__ . '../../vendor/autoload.php';


final class Init{


    /**
     * Store all the classes inside an array
     */
    public static function get_services(){
        return[
            Base\Functions::class,
            Base\SettingsLinks::class,
        ];
    }

    /**
     * Loop throug the classes, 
     * initialize them and call the register() method if it exists
     */
    public static function register_services(){

        foreach(self::get_services() as $class){
            $service = self::instantiate($class);
            if(method_exists($service, 'register')){
                $service->register();
            }
        }

        add_filter('woocommerce_payment_gateways', [__CLASS__, 'register_gateways']);
        add_filter('woocommerce_integrations', [__CLASS__, 'add_we_payments_integration']);

    }

    /**
     * Add the custom gateway to the list of available gateways
     *
     * @param array $gateways
     * @return array
     */
    public static function register_gateways($gateways) {
        // $gateways[] = Classes\WC_We_Payments_Gateway::class;
        $gateways[] = Classes\PIX_WC_We_Payments_Gateway::class;
        $gateways[] = Classes\Credit_Card_WC_We_Payments_Gateway::class;
        $gateways[] = Classes\Boleto_WC_We_Payments_Gateway::class;

        return $gateways;
    }

    public static function add_we_payments_integration($integrations) {
        $integrations[] =  Classes\Settings_WC_We_Payments::class;
        return $integrations;
    }
    
    /**
     * Initialize the class
     */
    private static function instantiate($class){
        
        $service = new $class();
         return $service;
    }
  
}

