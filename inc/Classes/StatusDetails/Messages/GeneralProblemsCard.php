<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails\Messages;

use Inc_Woo_We_Payments\Classes\StatusDetails\statusHistory;

class GeneralProblemsCard extends MessageDetail{

    public function message(statusHistory $statusHistory): string
    {
        if($statusHistory->detail == 'cc_rejected_card_disabled'){
            return "Cartão de crédito desativado";
        }

        if($statusHistory->detail == 'cc_rejected_card_error'){
            return "Erro geral no cartão de crédito";
        }

        if($statusHistory->detail == 'cc_rejected_insufficient_amount'){
            return "Cartão de crédito com fundos insuficientes";
        }

        return $this->nextMessageDetails->message($statusHistory);
    }
}