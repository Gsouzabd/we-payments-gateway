<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails;

use Inc_Woo_We_Payments\Classes\StatusDetails\Messages\DueDiligence;
use Inc_Woo_We_Payments\Classes\StatusDetails\Messages\FallbackErrors;
use Inc_Woo_We_Payments\Classes\StatusDetails\Messages\NoMessageInfo;
use Inc_Woo_We_Payments\Classes\StatusDetails\Messages\InternalProblems;
use Inc_Woo_We_Payments\Classes\StatusDetails\Messages\IncorrectInfoCard;
use Inc_Woo_We_Payments\Classes\StatusDetails\Messages\GeneralProblemsCard;

class StatusDetails{

    public function getMessage(StatusHistory $StatusHistory) : string
    {
        $chainOfMessages = new IncorrectInfoCard(
            new GeneralProblemsCard(
                new InternalProblems(
                    new DueDiligence(
                        new FallbackErrors(
                            new DueDiligence(
                                new NoMessageInfo()
                            )
                        )
                    )
                )
            )
        );

        return $chainOfMessages->message($StatusHistory);
    }
    
}