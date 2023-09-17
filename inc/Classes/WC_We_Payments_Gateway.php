<?php
namespace Inc_Woo_We_Payments\Classes;


require_once ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';

use CURLFile;
use WC_Payment_Gateway;

class WC_We_Payments_Gateway extends WC_Payment_Gateway {

    private static $file_checkout_field = false;

    public function __construct() {

        $this->id = 'woo_we_payments'; 
        $this->has_fields = true; 
        $this->method_title = 'WEPayments Gateway - Configurações Gerais';
        $this->method_description = 'Ative e configure para utilizar os métodos WEPayments.'; 
    
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
        $this->upload_file_enabled = $settings_instance->get_option( 'upload_file_enabled' );
        $this->upload_file_title = $settings_instance->get_option( 'upload_file_title' );

        $this->we_checkout = $this->testmode ? 'https://pagar.sandbox.goboleto.com/?hash=' : 'https://pagar.goboleto.com/?hash=';
                
		//upload file
        if($this->upload_file_enabled == 'yes'){
            add_action( 'woocommerce_checkout_update_order_meta', array( $this,'save_custom_checkout_field' ));
            if (!self::$file_checkout_field) {
                add_action('woocommerce_checkout_before_terms_and_conditions', array($this, 'add_file_checkout_field'));
                self::$file_checkout_field = true;
            }
        }

    }

    /*
    ** Save WEPayments key and checkout in meta data
    */
    public function we_payents_save_key_and_checkout($order, $key){
        $order->update_meta_data( 'woo_we_payments_key', $key);
        $order->update_meta_data( 'woo_we_payments_checkout', $this->we_checkout.$key);
        $order->add_order_note( "WEPayments Checkout: {$this->we_checkout}{$key}");
    }


	public function add_file_checkout_field() {
		ob_start();
		?>

        <strong><?= __( $this->upload_file_title ) ?></strong>

        <!-- Campo para o arquivo de receita -->
        <div id="woo_we_payments_receita_upload_field" style="margin-bottom: 30px;">
            <label for="personal_document"><?= __('Receituário Médico') ?></label>
            <input type="file" id="receituario_medico" accept="image/*" 
                onchange="convertFileToBase64Receita()">
            <small>png, jpeg ou jpg.</small>
            <input type="hidden" name="base64_receita" id="base64ReceitaHidden">
        </div>

        <!-- Campo para autorizacao_anvisa -->
        <div class="form-row" id="woo_we_payments_autorizacao_anvisa_field">
            <label for="autorizacao_anvisa"><?= __('Autorização ANVISA') ?></label>
            <input type="file" class="input-file" name="autorizacao_anvisa" id="autorizacao_anvisa"  accept="image/*"
                onchange="convertFileToBase64AutorizacaoAnvisa()">
            <small>png, jpeg ou jpg.</small>
            <input type="hidden" name="base64_autorizacao_anvisa" id="base64AutorizacaoAnvisaHidden">

        </div>

        <!-- Campo para personal_document -->
        <div class="form-row" id="woo_we_payments_personal_document_field">
            <label for="personal_document"><?= __('Documento Pessoal') ?></label>
            <input type="file" class="input-file" name="personal_document" id="personal_document"  accept="image/*"
                onchange="convertFileToBase64PersonalDocument()">
            <small>png, jpeg ou jpg.</small>
            <input type="hidden" name="base64_personal_document" id="base64PersonalDocumentHidden">
        </div>
        
		<script>
            jQuery(document).ready(function($) {
                // Toggle input if it's an WEPayments method
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
		$receita = get_post_meta( $order_id, 'receita_file_upload', true );
        $personal_document = get_post_meta( $order_id, 'personal_document_file_upload', true );
		$autorizacao_anvisa = get_post_meta( $order_id, 'autorizacao_anvisa_file_upload', true );
        
        $receitaTempFilePath = $this->parseToFile($receita, 'receita_');
        $personalDocumentTempFilePath = $this->parseToFile($personal_document, 'personal_');
        $autorizacaoTempFilePath = $this->parseToFile($autorizacao_anvisa, 'autorizacao_');
        

        $request_data = array(
            'autorizacao_anvisa' => new CURLFile($autorizacaoTempFilePath, 'image/png', 'autorizacao.png'), 
            'personal_document' => new CURLFile($personalDocumentTempFilePath, 'image/png', 'personal.png'),
            'receituario_medico' => new CURLFile($receitaTempFilePath, 'image/png', 'receituario.png'),
        );

        $headers = array(
            'Authorization: Bearer ' . $this->api_key,
            'Content-Type: multipart/form-data'
        );
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->url . "/v1/kyc/documents/".strval($payinId)."/send-cbd",
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
        
        if (curl_errno($curl)) {
            echo 'Erro ao enviar a requisição: ' . curl_error($curl);
        }else{
            echo $response;
        }
        
        curl_close($curl);
        
        unlink($receitaTempFilePath);
        unlink($autorizacaoTempFilePath);
        unlink($personalDocumentTempFilePath);
    }
    
    
    function parseToFile($base64, $filePrefix) {
        preg_match('#^data:image/(\w+);base64,#i', $base64, $matches);
        
        $tempFilePath = tempnam(sys_get_temp_dir(), $filePrefix) . $filePrefix.'.jpg';
        $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $base64);

        $binaryData = base64_decode($base64Data, true);
        
        if ($binaryData !== false) {
            file_put_contents($tempFilePath, $binaryData);
            return $tempFilePath;
        } else {
            echo "Falha na decodificação dos dados em base64.";
            return null; 
        }
    }
    
}
