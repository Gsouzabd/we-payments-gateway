<?php

namespace Inc_Woo_We_Payments\Classes\StatusDetails\Messages;

use Inc_Woo_We_Payments\Classes\StatusDetails\statusHistory;

abstract class MessageDetail{
    
    protected ?MessageDetail $nextMessageDetails;

    public function __construct(?MessageDetail $nextMessageDetails)
    {
        $this->nextMessageDetails = $nextMessageDetails;
    }

    abstract public function message(statusHistory $statusHistory): string;
}