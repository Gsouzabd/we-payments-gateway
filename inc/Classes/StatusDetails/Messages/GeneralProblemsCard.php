<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails\Messages;

use Inc_Woo_We_Payments\Classes\StatusDetails\StatusHistory;

class GeneralProblemsCard extends MessageDetail{

    public function message(StatusHistory $StatusHistory): string
    {
        if($StatusHistory->detail == 'cc_rejected_card_disabled'){
            return "Cartão de crédito desativado";
        }

        if($StatusHistory->detail == 'cc_rejected_card_error'){
            return "Erro geral no cartão de crédito";
        }

        if($StatusHistory->detail == 'cc_rejected_insufficient_amount'){
            return "Cartão de crédito com fundos insuficientes";
        }

        return $this->nextMessageDetails->message($StatusHistory);
    }
}