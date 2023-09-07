<?php

namespace Inc_Woo_We_Payments\Classes;

use DateTime;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;


if(file_exists((dirname(__FILE__) . '/vendor/autoload.php'))){
    require_once((dirname(__FILE__) . '/vendor/autoload.php'));
}


class PIX_WC_We_Payments_Gateway extends WC_We_Payments_Gateway{

    public function __construct() {

        //get parent configs options
        parent::__construct();
        $api_key = $this->api_key;
        $url = $this->url;
        
        $this->id = 'woo_we_payments_pix'; 
        $this->method_title = 'We Payments - Pix';
        $this->method_description = 'Receba pagamentos em PIX com a We Payments.'; 
    
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
        // $this->chave_pix = $this->chave_pix ? $this->get_option( 'chave_pix' ) : $this->get_option( 'chave_pix' );
    
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    
        
        // webhook
        add_action( 'woocommerce_api_woo_we_payments_pix', array( $this, 'webhook' ) );
    }

    /**
    * Plugin options
    */
    public function init_form_fields(){

        $this->form_fields = array(
            'enabled' => array(
                'title'       => 'Ativar',
                'label'       => 'Ativar PIX - We Payments',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title' => array(
                'title'       => 'Título',
                'type'        => 'text',
                'description' => '',
                'default'     => 'We Payments',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => 'Descrição',
                'type'        => 'textarea',
                'description' => '',
                'default'     => 'O QR CODE aparecerá após a finalização da compra.',
            ),
            'instructions' => [
                'title'       => __( 'Instruções de Pagamento', 'woo-we-payments' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => __( 'Descrição que aparecerá na tela de agradecimento.', 'woo-we-payments' ),
                'default'     => __( 'Faça o pagamento com QR Code no app do seu banco.' ),
            ],
            // 'chave_pix' => array(
            //     'title'       => 'Chave Pix',
            //     'type'        => 'text',
            //     'description' => '',
            //     'default'     => 'We Payments',
            //     'desc_tip'    => true,
            // ),
           
        );
    }


    /*
    * Custom CSS and JS
    */
    public function payment_scripts() {


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
        $formattedDate = $dateTime->format('Y-m-d\TH:i:s');


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
            'callbackUrl' => site_url('/wc-api/woo_we_payments_pix'),
            'title' => array(
                'expireDate' => $formattedDate,
                'amountInCents' => $amount_in_cents,
                'instructions' => 'any bank'
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
                    'zipCode' => $zipcode,
                    'city' => $city,
                    'district' => $neighborhood ? $neighborhood : 'Centro', 
                    'stateCode' => $state_code
                )
            )
        );
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url  . '/v1/payin/payments/pix',
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
        
        if (curl_errno($curl)) {
            echo 'Error: ' . curl_error($curl);
        } else {
            $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            echo json_encode(array('response' => $response));

            if($response_code == 201){
 
    
                // Empty cart
                $woocommerce->cart->empty_cart();
    
                // Redirect to the thank you page
                $pixPayload = $response['barCodeNumber'];
                $options = new QROptions([
                    'outputType' => QRCode::OUTPUT_IMAGE_PNG, // Escolha o formato de saída desejado, como PNG, etc.
                ]);
                
                $qrcode = new QRCode($options);
                
                $qrCodeImage = $qrcode->render($pixPayload);

				$order->add_order_note( 'Cobrança criada na We Payments.');
                $order->add_order_note( 'Pix gerado: '.$pixPayload, true );

                
                $return_data = array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order),
                );
                // Armazena o $pixPayload como um metadado do pedido
                $order->update_meta_data( 'pix_payload', $qrCodeImage);
                $order->update_meta_data( 'pix_copy_paste', $pixPayload);
                $order->update_meta_data( 'pix_instructions', $this->instructions);
                
                $order->save();
                
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
                            $order->payment_complete();
            
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





