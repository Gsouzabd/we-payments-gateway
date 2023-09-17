<?php 
namespace Inc_Woo_We_Payments\Classes;

use WC_Integration;

if(file_exists((dirname(__FILE__) . '/vendor/autoload.php'))){
    require_once((dirname(__FILE__) . '/vendor/autoload.php'));
}

class Settings_WC_We_Payments extends WC_Integration {

    public function __construct() {
		// Setup general properties
        $this->id = 'woo_we_payments'; 

        $this->method_title = 'WEPayments';
        $this->init_form_fields();
    
        // Load the settings.
        $this->init_settings();

		// Actions.
		add_action(
			'woocommerce_update_options_integration_' .  $this->id,
			[ $this, 'process_admin_options' ]
		);
	}

    
    public function process_admin_options() {
        parent::process_admin_options();
    }
    
    public function init_form_fields() {
        $this->settings = array(
            'enabled' => array(
                'title'       => 'Ativar',
                'label'       => 'Ativar WEPayments Gateway',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'testmode' => array(
                'title'       => 'Ambiente Sandbox',
                'label'       => 'Habilitar',
                'type'        => 'checkbox',
                'description' => 'No ambiente Sandbox você poderá simular pagamentos para testar vendas. Não esqueça de desativar essa opção quando sua loja estiver em produção.',
                'default'     => 'yes'
            ),
            'test_api_key' => array(
                'title'       => 'Sandbox API Key',
                'type'        => 'text',
                'description' => 'Por favor, insira sua API Key Sandbox do WePayments. Isto é necessário para processar pagamentos em teste.'
            ),
            'test_public_key' => array(
                'title'       => 'Sandbox Public Key',
                'type'        => 'text',
                'description' => 'Por favor, insira sua Public Key Sandbox do WePayments. Isto é necessário para processar pagamentos em teste.'
            ),
            'api_key' => array(
                'title'       => 'Produção API Key',
                'type'        => 'text',
                'description' => 'Por favor, insira sua API Key do WePayments. Isto é necessário para processar pagamentos em produção.'
            ),
            'public_key' => array(
                'title'       => 'Produção Public Key',
                'type'        => 'text',
                'description' => 'Por favor, insira sua Public Key do WePayments. Isto é necessário para processar pagamentos em produção.'
            ),
            'upload_file_enabled' => array(
                'title'       => 'Ativar Campos para Upload de Arquivo',
                'label'       => 'Ativar ',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'upload_file_title' => array(
                'title'       => 'Título da aba de Uploads de Arquivos',
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => 'Título que aparecerá acima do campo para envio de arquivos na página de finalização de compra.',
                'default'     => 'Envie seu arquivo',
            )
        );
    
        foreach ($this->settings as $key => $setting) {
            $this->form_fields[$key] = $setting;
            $this->$key = $this->get_option($key); // Initialize properties
        }
    }
    
}
