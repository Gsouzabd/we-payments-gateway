<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails;

use Inc_Woo_We_Payments\Classes\StatusDetails\Messages\NoMessageInfo;
use Inc_Woo_We_Payments\Classes\StatusDetails\Messages\IncorrectInfoCard;
use Inc_Woo_We_Payments\Classes\StatusDetails\Messages\GeneralProblemsCard;

class StatusDetails{

    public function getMessage(statusHistory $statusHistory) : string
    {
        $chainOfMessages = new IncorrectInfoCard(
            new GeneralProblemsCard(
                new NoMessageInfo()
            )
        );

        return $chainOfMessages->message($statusHistory);
    }
    
}