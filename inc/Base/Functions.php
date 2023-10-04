<?php

namespace Inc_Woo_We_Payments\Base;

use DateTime;

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
        $pixExpiration = $order->get_meta('pix_expiration');
        $checkoutLink = $order->get_meta('woo_we_payments_checkout');

        $dateTime = new DateTime($pixExpiration);   
        // Formata a data no formato 'd-m-y\TH:i:s'
        $formattedDatePixEpiration = $dateTime->format('d-m-y\TH:i:s');
        //Gera o qrcode 
        echo '<div id="custom-thankyou-content" style="text-align: center">';

        if ($pixPayload) {
                echo '<p>Realiza o pagamento com o QR CODE abaixo: </p>';
                echo  '<img src="'.$pixPayload.'">'; 
                echo '<p>'.$pixInstructions.'</p>';
                echo '<p>Ou copie e cole o código: </p><p><u>'.$pixCopyPaste.'</u></p>';
                echo '<small>Esse código irá expirar em ' . $formattedDatePixEpiration . '</small>';
        }
        echo '<p>Você também poderá realizar o pagamento e imprimir a cobrança no<br/> <a href='.$checkoutLink.'>Checkout WEpayments</a><p>';
        echo '</div>';
        ?>
        <style>
            div#custom-thankyou-content > img {
                position: relative;
                left: 43%;
            }
        </style>
        <?php
    }
    

    function woo_we_payment_boleto_thankyou($order_id) {
        // Carrega o pedido
        $order = wc_get_order($order_id);
    
        if (!$order) {
            return;
        }
        
        $boletoBarCode = $order->get_meta('boleto_bar_code');
        $digitableLine = $order->get_meta('boleto_digitable_line');
        $checkoutLink = $order->get_meta('woo_we_payments_checkout');

        //Gera o código de barra 
        if ($boletoBarCode) {
            echo '<div id="custom-thankyou-content" style="text-align: center">';
            echo '<p>Pague em qualquer banco de sua preferência com o código de barra abaixo:</p>';
            echo  $boletoBarCode ;
            echo '<u>'.$digitableLine.'</u>';
            echo '<h4><a href="'.$checkoutLink.'"> Imprimir boleto </a><h5>';
            echo '</div>';
        }
        ?>
        <style>
            div#custom-thankyou-content > div {
                position: relative;
                left: 25%;
            }
        </style>
        <?php
    }
                         
                
    
}