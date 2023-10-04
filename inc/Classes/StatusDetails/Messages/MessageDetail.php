<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails\Messages;

use Inc_Woo_We_Payments\Classes\StatusDetails\StatusHistory;

abstract class MessageDetail{
    
    protected ?MessageDetail $nextMessageDetails;

    public function __construct(?MessageDetail $nextMessageDetails)
    {
        $this->nextMessageDetails = $nextMessageDetails;
    }

    abstract public function message(StatusHistory $StatusHistory): string;
}