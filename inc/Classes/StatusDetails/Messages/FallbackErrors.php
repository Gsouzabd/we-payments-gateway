<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails\Messages;

use Inc_Woo_We_Payments\Classes\StatusDetails\StatusHistory;

class FallbackErrors extends MessageDetail{

    public function message(StatusHistory $StatusHistory): string
    {
        if($StatusHistory->detail == 'cc_rejected_other_reason'){
            return "Transação rejeitada por outros motivos";
        }

        if($StatusHistory->detail == 'cc_rejected_fallback'){
            return "Rejeição de fallback (tempo limite, informações de pagamento inválidas...)";
        }

        if($StatusHistory->detail == 'bad_request'){
            return "Solicitação inválida durante a transação";
        }

        return $this->nextMessageDetails->message($StatusHistory);
    }
}