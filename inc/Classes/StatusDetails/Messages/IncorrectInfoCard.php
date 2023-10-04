<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails\Messages;

use Inc_Woo_We_Payments\Classes\StatusDetails\StatusHistory;

class IncorrectInfoCard extends MessageDetail{

    public function message(StatusHistory $StatusHistory): string
    {
        if($StatusHistory->detail == 'cc_rejected_bad_filled_card_number'){
            return "Número do cartão incorreto";
        }

        if($StatusHistory->detail == 'cc_rejected_bad_filled_date'){
            return "Data de vencimento do cartão incorreta";
        }

        if($StatusHistory->detail == 'cc_rejected_bad_filled_security_code'){
            return "Código de segurança do cartão incorreto";
        }

        if($StatusHistory->detail == 'cc_rejected_bad_filled_other'){
            return "Informação incorreta relacionada ao cartão";
        }

        return $this->nextMessageDetails->message($StatusHistory);
    }
}