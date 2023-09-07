<?php

namespace Inc_Woo_We_Payments\Base;

class Functions{
    
    public function register(){
        add_action('woocommerce_thankyou', array($this, 'woo_we_payment_pix_thankyou'));
        add_action('woocommerce_thankyou', array($this, 'woo_we_payment_boleto_thankyou'));
	}


    function woo_we_payment_pix_thankyou($order_id) {
        // Carrega o pedido
        $order = wc_get_order($order_id);
    
        if (!$order) {
            return;
        }
        
        $pixPayload = $order->get_meta('pix_payload');
        $pixInstructions = $order->get_meta('pix_instructions');
        $pixCopyPaste = $order->get_meta('pix_copy_paste');
        //Gera o qrcode 
        if ($pixPayload) {
            echo '<div id="custom-thankyou-content" style="text-align: center">';
                echo '<p>Realiza o pagamento com o QR CODE abaixo: </p>';
                echo  '<img src="'.$pixPayload.'">'; 
                echo '<p>'.$pixInstructions.'</p>';
                echo '<p>Ou copie e cole o código: </p><p><u>'.$pixCopyPaste.'</u></p>';
            echo '</div>';
        }
    }
    

    function woo_we_payment_boleto_thankyou($order_id) {
        // Carrega o pedido
        $order = wc_get_order($order_id);
    
        if (!$order) {
            return;
        }
        
        $boletoBarCode = $order->get_meta('boleto_bar_code');
        $digitableLine = $order->get_meta('boleto_digitable_line');

        //Gera o qrcode 
        if ($boletoBarCode) {
            echo '<div id="custom-thankyou-content" style="text-align: center">';
            echo '<p>Pague em qualquer banco de sua preferência com o código de barra abaixo:</p>';
            echo  $boletoBarCode ;
            echo '<u>'.$digitableLine.'</u>';


            echo '</div>';
        }
    }
                                
                    
    
    
}