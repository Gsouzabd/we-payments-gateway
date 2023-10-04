<?php
namespace Inc_Woo_We_Payments\Classes;


require_once ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';
use WP_Error;

use CURLFile;
use WC_Payment_Gateway;

class WC_We_Payments_Gateway extends WC_Payment_Gateway {

    private static $file_checkout_field = false;

    public function __construct() {

        $this->id = 'woo_we_payments'; 
        $this->has_fields = true; 
        $this->method_title = 'WEpayments Gateway - Configurações Gerais';
        $this->method_description = 'Ative e configure para utilizar os métodos WEpayments.'; 
    
        $this->supports = array(
            'products',
            'refunds'
        );
    
        // Load the settings.
        $settings_instance = new Settings_WC_We_Payments(); 
        $settings_instance->init_settings(); 

        $this->enabled = $settings_instance->get_option( 'enabled' );
        $this->testmode = 'yes' === $settings_instance->get_option( 'testmode' );
        $this->public_key = $this->testmode ? $settings_instance->get_option( 'test_public_key' ) : $settings_instance->get_option( 'public_key' );
        $this->api_key = $this->testmode ? $settings_instance->get_option( 'test_api_key' ) : $settings_instance->get_option( 'api_key' );
        $this->url = $this->testmode ? 'https://api.sandbox.wepayout.com.br' : 'https://api.wepayout.com.br';
        $this->upload_file_enabled = $settings_instance->get_option( 'upload_file_enabled' );
        $this->upload_file_title = $settings_instance->get_option( 'upload_file_title' );
        $this->upload_receita_enabled = $settings_instance->get_option( 'upload_receita_enabled' );
        $this->upload_receita_required = $settings_instance->get_option( 'upload_receita_required' );
        $this->upload_personal_document_enabled = $settings_instance->get_option( 'upload_personal_document_enabled' );
        $this->upload_personal_document_required = $settings_instance->get_option( 'upload_personal_document_required' );
        $this->upload_autorizacao_anvisa_enabled = $settings_instance->get_option( 'upload_autorizacao_anvisa_enabled' );
        $this->upload_autorizacao_anvisa_required = $settings_instance->get_option( 'upload_autorizacao_anvisa_required' );

        $this->we_checkout = $this->testmode ? 'https://pagar.sandbox.goboleto.com/?hash=' : 'https://pagar.goboleto.com/?hash=';
                
        // webhook refunds
        add_action( 'woocommerce_api_woo_we_payments_refunds', array( $this, 'webhook' ) );


		//upload file
        if($this->upload_file_enabled == 'yes'){
            add_action( 'woocommerce_checkout_update_order_meta', array( $this,'save_custom_checkout_field' ));
            if (!self::$file_checkout_field) {
                add_action('woocommerce_checkout_before_terms_and_conditions', array($this, 'add_file_checkout_field'));
                add_action('save_post', array($this, 'save_cdb_documents_on_order_update'));
   		        add_action('add_meta_boxes', array( $this, 'register_metabox' ), 20 );

                self::$file_checkout_field = true;
            }
     
        }
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

        if($this->upload_personal_document_required == 'yes'){
            if( empty( $_POST[ 'base64_personal_document' ]) ) {
                wc_add_notice(  'Campo "Documento Pessoal" é obrigatório!', 'error' );
                return false;
            }
        }
        if($this->upload_receita_required == 'yes'){
            if( empty( $_POST[ 'base64_receita' ]) ) {
                wc_add_notice(  'Campo "Receituário Médico" é obrigatório!', 'error' );
                return false;
            }
        }
        if($this->upload_autorizacao_anvisa_required == 'yes'){
            if( empty( $_POST[ 'base64_autorizacao_anvisa' ]) ) {
                wc_add_notice(  'Campo "Autorização Anvisa" é obrigatório!', 'error' );
                return false;
            }
        }
        return true;
    }


    public function process_refund($order_id, $amount = null, $reason = '') {
        $order = wc_get_order($order_id);
        $logger = wc_get_logger();
    
        $payinId = get_post_meta( $order_id, 'woo_we_payments_payinid', true );
        $amount_in_cents = $amount * 100;
    
        $curl = curl_init();
    
        $request_data = array(
            "amountCents" => $amount_in_cents,
            "reason" => $reason,
            "callbackUrl" => site_url('/wc-api/woo_we_payments_refunds'),
        );
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url . "/v1/payin/payments/payin-refund/{$payinId}",
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
                'Authorization: Bearer ' . $this->api_key
            ),
        ));
    
        $response = json_decode(curl_exec($curl), true);
    
    
        if (curl_errno($curl)) {
            // Log cURL error
            $logger->error('cURL Error: ' . curl_error($curl), array('source' => 'woo-we-payments-refunds-curl-error'));
            $logger->info(wc_print_r($response, true), array('source' => 'woo-we-payments-refunds-errors'));
				if(isset($response['message'])){
                    $order->add_order_note( "Falha no reembolso: ". $response['message']);
								return new WP_Error( 'error', $response['message'] );

                }
            return false;
        } else {
            if (!$response['id']) {
                $logger->info('Refund failed for order #' . $order_id, array('source' => 'woo-we-payments-refunds-failure'));
                $logger->info('Refund Response: ' . json_encode($response), array('source' => 'woo-we-payments-refunds-failure'));
                if(isset($response['message'])){
                    $order->add_order_note( "Falha no reembolso: ". $response['message']);
													return new WP_Error( 'error', $response['message'] );

                }
                return false; 
            }
            
            
            // Log successful refund
            $logger->info('Refund processed for order #' . $order_id, array('source' => 'woo-we-payments-refunds-success'));
            $logger->info('Refund Response: ' . json_encode($response), array('source' => 'woo-we-payments-refunds-success'));
            return true;
        }
            
    }
    
    
function display_admin_error_notice($message) {
    echo '<div class="error"><p>' . esc_html($message) . '</p></div>';
}


    /*
    ** Save WEpayments key and checkout in meta data
    */
    public function we_payments_save_key_and_checkout($order, $key, $payinId){
        $order->update_meta_data( 'woo_we_payments_key', $key);
        $order->update_meta_data( 'woo_we_payments_payinid', $payinId);
        $order->update_meta_data( 'woo_we_payments_checkout', $this->we_checkout.$key);

        $order->add_order_note( "WEpayments Checkout: {$this->we_checkout}{$key}");
    }


	public function add_file_checkout_field() {
		ob_start();
		?>

        <strong><?= __( $this->upload_file_title ) ?></strong>

        <!-- Campo para o arquivo de receita -->
        <?php if($this->upload_receita_enabled == 'yes'):?>
            <div id="woo_we_payments_receita_upload_field">
                <label for="personal_document"><?= __('Receituário Médico') ?></label>
                <input type="file" id="receituario_medico" accept="image/* ,application/pdf" 
                    onchange="convertFileToBase64Receita()" 
                    <?php if($this->upload_receita_required == 'yes'):?>
                    required
                    <?php endif;?>
                    >
                <small>png, jpeg ou jpg.</small>
                <input type="hidden" name="base64_receita" id="base64ReceitaHidden">
            </div>
        <?php endif;?>

        <!-- Campo para autorizacao_anvisa -->
        <?php if($this->upload_autorizacao_anvisa_enabled == 'yes'):?>
        <div class="form-row" id="woo_we_payments_autorizacao_anvisa_field">
            <label for="autorizacao_anvisa"><?= __('Autorização ANVISA') ?></label>
            <input type="file" class="input-file" name="autorizacao_anvisa" id="autorizacao_anvisa"  accept="image/* ,application/pdf"
                onchange="convertFileToBase64AutorizacaoAnvisa()"
                <?php if($this->upload_autorizacao_anvisa_required == 'yes'):?>
                required
                <?php endif;?>
                >
            <small>png, jpeg ou jpg.</small>
            <input type="hidden" name="base64_autorizacao_anvisa" id="base64AutorizacaoAnvisaHidden">
        </div>
        <?php endif;?>

        <!-- Campo para personal_document -->
        <?php if($this->upload_personal_document_enabled == 'yes'):?>
        <div class="form-row" id="woo_we_payments_personal_document_field">
            <label for="personal_document"><?= __('Documento Pessoal') ?></label>
            <input type="file" class="input-file" name="personal_document" id="personal_document"  accept="image/* ,application/pdf"
                onchange="convertFileToBase64PersonalDocument()"
                <?php if($this->upload_personal_document_required == 'yes'):?>
                required
                <?php endif;?>
                >
            <small>png, jpeg ou jpg.</small>
            <input type="hidden" name="base64_personal_document" id="base64PersonalDocumentHidden">
        </div>
        <?php endif;?>

        
		<script>
            jQuery(document).ready(function($) {
                // Toggle input if it's an WEpayments method
                function checkPaymentMethod() {
                    var selectedPaymentMethod = $('input[name="payment_method"]:checked').attr('id');

                    if (selectedPaymentMethod && selectedPaymentMethod.indexOf('woo_we_payments') !== -1) {
                        $('#woo_we_payments_receita_upload_field').show();
                        $('#woo_we_payments_autorizacao_anvisa_field').show();
                        $('#woo_we_payments_personal_document_field').show();

                    } else {
                        $('#woo_we_payments_receita_upload_field').hide();
                        $('#woo_we_payments_autorizacao_anvisa_field').hide();
                        $('#woo_we_payments_personal_document_field').hide();
                    }
                }

                checkPaymentMethod();

                $('form.checkout').on('change', 'input[name="payment_method"]', function() {
                    checkPaymentMethod();
                });
            });


            function convertFileToBase64Receita(event) {
                const receituario_medico = document.getElementById('receituario_medico');
                const base64ReceitaHidden = document.getElementById('base64ReceitaHidden');

                const selectedFileReceita = receituario_medico.files[0];
                if (selectedFileReceita) {
                    const readerReceita = new FileReader();
                    readerReceita.onload = function (event) {
                        if (selectedFileReceita && event.target.result) {
                            base64ReceitaHidden.value = event.target.result;
                        }

                    };
                    readerReceita.readAsDataURL(selectedFileReceita);
                }
            }

            function convertFileToBase64AutorizacaoAnvisa(event) {
                const autorizacao_anvisa = document.getElementById('autorizacao_anvisa');
                const base64AutorizacaoAnvisaHidden = document.getElementById('base64AutorizacaoAnvisaHidden');

                const selectedFileAutorizacaoAnvisa = autorizacao_anvisa.files[0];
                if (selectedFileAutorizacaoAnvisa) {
                    const readerAutorizacaoAnvisa = new FileReader();
                    readerAutorizacaoAnvisa.onload = function (event) {
                        if (selectedFileAutorizacaoAnvisa && event.target.result) {
                            base64AutorizacaoAnvisaHidden.value = event.target.result;
                        }
                    };
                    readerAutorizacaoAnvisa.readAsDataURL(selectedFileAutorizacaoAnvisa);
                }
            }

            function convertFileToBase64PersonalDocument(event) {
                const personal_document = document.getElementById('personal_document');
                const base64PersonalDocumentHidden = document.getElementById('base64PersonalDocumentHidden');

                const selectedFilePersonalDocument = personal_document.files[0];
                if (selectedFilePersonalDocument) {
                    const readerPersonalDocument = new FileReader();
                    readerPersonalDocument.onload = function (event) {
                        if (selectedFilePersonalDocument && event.target.result) {
                            base64PersonalDocumentHidden.value = event.target.result;
                        }
                    };
                    readerPersonalDocument.readAsDataURL(selectedFilePersonalDocument);
                }
            }

		</script>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	

	public function save_custom_checkout_field( $order_id ) {
		$receita_file_upload = $_POST['base64_receita'];
        $autorizacao_anvisa_file_upload = $_POST['base64_autorizacao_anvisa'];
        $personal_document_upload = $_POST['base64_personal_document'];
		if ( $receita_file_upload != '' ) {
	
			update_post_meta( $order_id, 'receita_file_upload', $receita_file_upload );
		}
        if ( $autorizacao_anvisa_file_upload != '') {
	
			update_post_meta( $order_id, 'autorizacao_anvisa_file_upload', $autorizacao_anvisa_file_upload );
		}
        if ( $personal_document_upload != '') {
	
			update_post_meta( $order_id, 'personal_document_file_upload', $personal_document_upload );
		}
	}


    public function send_cdb_documents($order_id, $payinId) {
        $curl = curl_init();
    
        $receita = get_post_meta($order_id, 'receita_file_upload', true);
        $personal_document = get_post_meta($order_id, 'personal_document_file_upload', true);
        $autorizacao_anvisa = get_post_meta($order_id, 'autorizacao_anvisa_file_upload', true);
        $fatura = get_post_meta($order_id, 'fatura_file_upload', true);
        $rastreamento = get_post_meta($order_id, 'woo_we_payments_rastreamento', true);
    
        $request_data = array();
        
        if ($receita) {
            $request_data['receituario_medico'] = $this->parseToFile($receita, 'receita_');
        }
        
        if ($personal_document) {
            $request_data['personal_document'] = $this->parseToFile($personal_document, 'personal_');
        }
        
        if ($autorizacao_anvisa) {
            $request_data['autorizacao_anvisa'] = $this->parseToFile($autorizacao_anvisa, 'autorizacao_');
        }
        
        if ($fatura) {
            $request_data['fatura'] = $this->parseToFile($fatura, 'fatura_');
        }
    
        if ($rastreamento) {
            $request_data['codigo_rastreio_transportadora'] = $rastreamento;
        }
    
        $headers = array(
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: multipart/form-data'
        );
    
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url . "/v1/kyc/documents/" . strval($payinId) . "/send-cbd",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request_data,
            CURLOPT_HTTPHEADER => $headers
        ));
    
        $response = curl_exec($curl);
        $logger = wc_get_logger();
        $logger->info(wc_print_r($response, true), array('source' => 'woo-we-payments-send-cdb'));
    
        if (curl_errno($curl)) {
            echo 'Erro ao enviar a requisição: ' . curl_error($curl);
        } else {
            echo $response;
        }
    
        curl_close($curl);
    
        // Clean up temporary files
        foreach ($request_data as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    function parseToFile($base64, $filePrefix) {
        preg_match('#^data:([a-zA-Z0-9]+/([a-zA-Z0-9]+));base64,#i', $base64, $matches);
    
        if (!isset($matches[2])) {
            echo "Failed to detect file type from base64 data: $base64";
            return null;
        }
    
        $fileExtension = $matches[2];
        $tempFilePath = tempnam(sys_get_temp_dir(), $filePrefix) . '.' . $fileExtension;
        $base64Data = preg_replace('#^data:[a-zA-Z0-9]+/[a-zA-Z0-9]+;base64,#i', '', $base64);
    
        $binaryData = base64_decode($base64Data, true);
    
        if ($binaryData !== false) {
            file_put_contents($tempFilePath, $binaryData);
            return new CURLFile($tempFilePath, mime_content_type($tempFilePath), $filePrefix . $fileExtension);
        } else {
            echo "Failed to decode base64 data into a file: $base64";
            return null;
        }
    }
    
    
    
    


    public function register_metabox() {
		add_meta_box(
			'woo_we_payments_admin',
			'WEpayments',
			array( $this, 'send_cdb_admin' ),
			'shop_order',
			'side',
			'default'
		);
	}

    function send_cdb_admin($post){
        
        $fatura =  get_post_meta($post->ID, 'fatura_file_upload', true);

        ?>
        <div class="order_data_column">
            <p class="form-field form-field-wide wc-order-status">
                <strong for="woo_we_payments_fatura_document_field"><?=esc_html('Fatura: ', 'woo-we-payments');?></strong>

                <?php if(!$fatura) :?>
                    <input type="file" id='woo_we_payments_fatura_document_field' class="form-field-wide"
                        value=<?=get_post_meta($post->ID, 'base64_fatura', true);?>
                        accept="image/* ,application/pdf" 
                        onchange="convertFileToBase64Fatura()"/>
                    <input type="hidden" name="base64_fatura" id="base64FaturaHidden">
                <?php else:?>
                    <span class="form-field-wide">Arquivo enviado.</span>
                <?php endif;?>
            </p>
            <p class="form-field form-field-wide wc-order-status">
            <strong><?=esc_html('Rastreamento: ', 'woo-we-payments');?></strong>

                <input type="text" id="woo_we_payments_rastreamento" 
                    name="woo_we_payments_rastreamento" 
                    value=<?=get_post_meta($post->ID, 'woo_we_payments_rastreamento', true);?>>
            </p>
            <p>
                <small>Atualize o pedido para confirmar o envio</small>
            </p>
        </div>
        <script>
            function convertFileToBase64Fatura(event) {
                const fatura = document.getElementById('woo_we_payments_fatura_document_field');
                const base64FaturaHidden = document.getElementById('base64FaturaHidden');

                const selectedFileFatura = fatura.files[0];
                if (selectedFileFatura) {
                    const readerFatura = new FileReader();
                    readerFatura.onload = function (event) {
                        if (selectedFileFatura && event.target.result) {
                            base64FaturaHidden.value = event.target.result;
                        }

                    };
                    readerFatura.readAsDataURL(selectedFileFatura);
                }
            }
        </script>
        <?php
    }
	
	function save_cdb_documents_on_order_update($post_id) {
		// Check if this is an autosave or a post save.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

		$order = wc_get_order($post_id);

		if (!$order) {
			return;
		}

		$payinId = $order->get_meta('woo_we_payments_payinid');

		if (isset($_POST['base64_fatura']) || isset($_POST['woo_we_payments_rastreamento'])) {
			if (!is_null($_POST['base64_fatura'])) {
				$base64_fatura = sanitize_text_field($_POST['base64_fatura']);
				update_post_meta($post_id, 'fatura_file_upload', $base64_fatura);
			}
			if (!is_null($_POST['woo_we_payments_rastreamento'])) {
				$rastreamento = sanitize_text_field($_POST['woo_we_payments_rastreamento']);
				update_post_meta($post_id, 'woo_we_payments_rastreamento', $rastreamento);
			}

			// Assuming $this refers to the current class instance.
			$this->send_cdb_documents($post_id, $payinId);
		}
	}




    
}
