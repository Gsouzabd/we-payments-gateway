<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails\Messages;

use Inc_Woo_We_Payments\Classes\StatusDetails\StatusHistory;

class DueDiligence extends MessageDetail{

    public function message(StatusHistory $StatusHistory): string
    {
        if($StatusHistory->detail == 'cc_rejected_blacklist'){
            return "Cartão de crédito ou titular assinado como lista negra";
        }

        if($StatusHistory->detail == 'cc_rejected_high_risk'){
            return "Cartão de crédito assinado como usuário de alto risco para prosseguir com a transação";
        }

        return $this->nextMessageDetails->message($StatusHistory);
    }
}