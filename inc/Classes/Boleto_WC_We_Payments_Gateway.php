<?php

namespace Inc_Woo_We_Payments\Classes;

use DateTime;
use DateInterval;
use Picqer\Barcode\BarcodeGeneratorHTML;

if(file_exists((dirname(__FILE__) . '/vendor/autoload.php'))){
    require_once((dirname(__FILE__) . '/vendor/autoload.php'));
}

class Boleto_WC_We_Payments_Gateway extends WC_We_Payments_Gateway{

    public function __construct() {

        //get parent configs options
        parent::__construct();
        $api_key = $this->api_key;
        $url = $this->url;
        
        $this->id = 'woo_we_payments_boleto'; 
        $this->method_title = 'WEPayments - Boleto';
        $this->method_description = 'Receba pagamentos de Boleto com a WEPayments.'; 
    
        $this->supports = array(
            'products'
        );
    
        $this->init_form_fields();
    
        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->instructions = $this->get_option( 'instructions' );
        $this->document = $this->get_option( 'document' );
        $this->sender_name =  $this->get_option( 'sender_name' );
        $this->expiration = $this->get_option( 'expiration' );
        $this->fine = $this->get_option( 'fine' );
        $this->interest = $this->get_option( 'interest' );
        $this->discount = $this->get_option('discount');
        $this->discount_type = $this->get_option('discount_type');
        $this->discount_date = $this->get_option('discount_date');

        

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    
        // webhook
        add_action( 'woocommerce_api_woo_we_payments_boleto', array( $this, 'webhook' ) );
    }


    /**
    * Plugin options
    */
    public function init_form_fields(){

        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Ativar',
                'label'       => 'Ativar Boleto - WEPayments',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Título',
                'type'        => 'text',
                'description' => 'Título que aparecerá na página de finalização de compra.',
                'default'     => 'Boleto',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Descrição',
                'type'        => 'textarea',
                'description' => 'Descrição que aparecerá na página de finalização de compra.',
                'default'     => 'O código de barra aparecerá após a finalização da compra.',
                'desc_tip'    => true,

            ),
            'document' => array(
                'title'       => 'Documento do Beneficiário',
                'type'        => 'text',
                'description' => 'CPF ou CNPJ do beneficiário final (Sem pontuação).',
                'custom_attributes' => array(
                    'required' => 'required', 
                ),
                'desc_tip'    => true,
            ),
            'sender_name' => array(
                'title'       => 'Nome do Beneficiário',
                'type'        => 'text',
                'description' => 'Nome do beneficiário final.',
                'custom_attributes' => array(
                    'required' => 'required', 
                ),
                'desc_tip'    => true,
            ),
            'expiration' => array(
                'title'       => __( 'Quantidade de dias para vencimento', 'woo-we-payments' ),
                'type'        => 'number',
                'description' => __( 'Defina em dia o prazo de vencimento do Boleto.', 'woo-we-payments' ),
                'default'     => '0',
            ),
            'fine' => array(
                'title'       => __( 'Multa após vencimento ', 'woo-we-payments' ),
                'type'        => 'number',
                'description' => __( 'Caso haja multa para pagamentos após o dia do vencimento, informe a porcentagem.', 'woo-we-payments' ),
                'default'     => '0',
                'desc_tip'    => true,
            ),
            'interest' => array(
                'title'       => __( 'Juros após vencimento ', 'woo-we-payments' ),
                'type'        => 'number',
                'description' => __( 'Caso haja juros diário para pagamentos após o dia do vencimento, informe a porcentagem. (Menor ou igual a 20%)', 'woo-we-payments' ),
                'default'     => '0',
                'desc_tip'    => true,
            ),
            'discount_type' => array(
                'title'       => __( 'Tipo de Desconto', 'woo-we-payments' ),
                'type'        => 'select',
                'default'     => 'percent',
                'description' => __( 'Caso forneça desconto para pagamentos adiantados, informe o tipo de desconto.', 'woo-we-payments' ),
                'desc_tip'    => true,
                'options'     => array(
                    'percent'            => __( 'Porcentagem', 'woo-we-payments' ),
                    'amountInCents'      => __( 'Valor Fixo em centavos', 'woo-we-payments' ),
                    'dailyAmountInCents' => __( 'Valor Diário em centavos', 'woo-we-payments' ),
                ),
            ),
            'discount' => array(
                'title'       => __( 'Valor do Desconto', 'woo-we-payments' ),
                'type'        => 'number',
                'description' => __( 'Defina o valor do desconto', 'woo-we-payments' ),
                'default'     => '0',
                'desc_tip'    => true,
            ),
            'discount_date' => array(
                'title'       => __( 'Data de Vencimento do Desconto', 'woo-we-payments' ),
                'type'        => 'number',
                'default'     => '',
                'desc_tip'    => true,
                'description' => __( 'Prazo em dias do vencimento do desconto (não pode ser superior à data de vencimento).', 'woo-we-payments' ),
            ),
            
           
        );
    }


    public function validate_fields() {

        if( empty( $_POST[ 'billing_first_name' ]) ) {
            wc_add_notice(  'Campo "nome" é obrigatório!', 'error' );
            return false;
        }
        if( empty( $_POST[ 'billing_cpf' ]) ) {
            wc_add_notice(  'Campo "CPF" é obrigatório!', 'error' );
            return false;
        }
        return true;

    }


    public function process_payment( $order_id ) {
        global $woocommerce;

        $order = wc_get_order( $order_id );

        // Verifica se o pedido existe e se está em andamento
        if ( ! $order || ! $order->has_status( array( 'pending', 'processing' ) ) ) {
            echo 'Invalid order.';
            return;
        }
        //order data
        $amount_in_cents = $order->get_total() * 100; // Converte para centavos
        $dateTime = new DateTime();
        $dateTime->add(new DateInterval('P'.($this->expiration ? $this->expiration  : 0).'D'));
        $formattedDate = $dateTime->format('Y-m-d');

        if ($this->discount_date !== null) {
            $discountDeadLine = new DateTime();
            $discountDeadLine->add(new DateInterval('P'.($this->discount_date ? $this->discount_date  : 0).'D'));
            $formattedDiscountDate = $discountDeadLine->format('Y-m-d');
        } 
        
        //customer data
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $document_number = str_replace(array('.', '-', ' '), '', get_user_meta($order->get_user_id(), 'billing_cpf', true));
        $street = get_user_meta( $order->get_user_id(), 'billing_address_1', true );
        $street_number = get_user_meta( $order->get_user_id(), 'billing_number', true );
        $street_complement = get_user_meta( $order->get_user_id(), 'billing_address_2', true );
        $neighborhood = get_user_meta( $order->get_user_id(), 'billing_neighborhood', true );
        $city = get_user_meta( $order->get_user_id(), 'billing_city', true );
        $state_code = get_user_meta( $order->get_user_id(), 'billing_state', true );
        $zipcode = get_user_meta( $order->get_user_id(), 'billing_postcode', true );

        $request_data = array(
            'customNumber' => strval($order_id),
            'callbackUrl' => site_url('/wc-api/woo_we_payments_boleto'),
            'title' => array(
                'expireDate' => $formattedDate,
                'amountInCents' => $amount_in_cents,
                'instructions' => "Pedido: {$order_id} - ".site_url()
            ),
            'buyer' => array(
                'name' => $customer_name,
                'document' => array(
                    'number' => $document_number,
                    'type' => 'CPF'
                ),
                'address' => array(
                    'street' => $street,
                    'number' => $street_number,
                    'complement' => $street_complement ? $street_complement :  '',
                    'zipCode' => $zipcode = str_replace("-", "", $zipcode),
                    'city' => $city,
                    'district' => $neighborhood ? $neighborhood : 'Centro', 
                    'stateCode' => $state_code
                )
            ),
            'sender' => array(
                'name' => $this->sender_name,
                'document' => $this->document,
                'helpdesk' => site_url()
            ),
            'fine' => array(
                'percent' => floatval($this->fine),
                'interest' => floatval($this->interest),
            ),
            'discount' => array(
                $this->discount_type => floatval($this->discount),
                ($this->discount_type == 'dailyAmountInCents') ? '' : 'date' => $formattedDiscountDate
            )
        );


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url  . '/v1/payin/payments/boleto',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($request_data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->api_key
            ),
        ));
        
        $response = json_decode(curl_exec($curl),true);
        echo json_encode(array('response' => $response));
		// ORDER LOG
		$logger = wc_get_logger();
        
        if (curl_errno($curl)) {
            echo 'Error: ' . curl_error($curl);
            $logger->info( wc_print_r( $response, true ), array( 'source' => 'woo-we-payments-boleto-errors' ) );

        } else {
            $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if($response_code == 201){
   
                // Empty cart
                $woocommerce->cart->empty_cart();
    
                // Redirect to the thank you page
                $boletoBarCode = $response['barCodeNumber'];
                $digitableLine = $response['digitableLine'];
                
                $generator = new BarcodeGeneratorHTML();
                $barcodeHtml = $generator->getBarcode($boletoBarCode, $generator::TYPE_CODE_128);
                
                
				$order->add_order_note( 'Cobrança criada na WEPayments.');
                $order->add_order_note( 'Boleto gerado. Linha digitável '.$digitableLine, true );

                $key = $response['key'];
                $payinId = $response['id'];

				$this->we_payents_save_key_and_checkout($order,$key);

                $return_data = array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order),
                );
                // Armazena o $boletoBarCode como um metadado do pedido
                $order->update_meta_data( 'boleto_bar_code', $barcodeHtml);
                $order->update_meta_data( 'boleto_digitable_line', $digitableLine);    
                
                $logger->info( wc_print_r( $response, true ), array( 'source' => 'woo-we-payments-boleto-success' ) );
                
                $order->save();
                sleep(5);
                $this->send_cdb_documents($order_id, $payinId);
                
                return $return_data;
            }
        }
        curl_close($curl);      
    }        


	public function webhook() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$webhook_data = file_get_contents("php://input");
			
			// Decodifica os dados do webhook
			$decoded_data = json_decode($webhook_data, true);
			
			if ($decoded_data) {
				$order_id = isset($decoded_data['customNumber']) ? $decoded_data['customNumber'] : '';
                $status = isset($decoded_data['status']) ? $decoded_data['status'] : false;
				// Verifica se o ID do pedido é válido
				if (!empty($order_id)) {
					// Obtenha o pedido com base no ID do pedido
					$order = wc_get_order($order_id);
		
					if ($order) {
                        $note_content = '';
                        if($status == 'confirmed' || $status ==  'confirmado'
                        || $status == 'paid' || $status ==  'pago'){
                            $order->update_status( 'processing' );
            
                            $note_content = 'Pagamento realizado. Atualizado via webhook. ';
                        }
                        if($status == 'awaiting_approval'){
                            $note_content = 'Aguardando aprovação. Atualizado via webhook. ';
                        }
                        if($status == 'canceled' || $status ==  'cancelado'
                        || $status == 'rejected' || $status ==  'rejeitado'){
                            $order->update_status('cancelled', 'Pagamento cancelado ou rejeitado. Atualizado via webhook.');

                            $note_content = 'Pagamento cancelado ou rejeitado. Atualizado via webhook. ';
                        }

                        $order->add_order_note($note_content);
						// Salva o pedido para que a nota seja registrada
						$order->save();
					}
				}
			}
		}
		
		http_response_code(200);
	}


}





