<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails\Messages;

use Inc_Woo_We_Payments\Classes\StatusDetails\StatusHistory;

class InternalProblems extends MessageDetail{

    public function message(StatusHistory $StatusHistory): string
    {
        if($StatusHistory->detail == 'cc_rejected_card_type_not_allowed'){
            return "Tipo de cartão de crédito não permitido";
        }

        if($StatusHistory->detail == 'cc_rejected_duplicated_payment'){
            return "Pagamento duplicado para cartão de crédito";
        }

        if($StatusHistory->detail == 'cc_rejected_invalid_installments'){
            return "Número inválido de parcelas selecionado";
        }

        return $this->nextMessageDetails->message($StatusHistory);
    }
}