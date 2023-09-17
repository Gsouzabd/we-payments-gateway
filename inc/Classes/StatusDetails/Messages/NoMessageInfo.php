<?php


namespace Inc_Woo_We_Payments\Classes\StatusDetails\Messages;

use Inc_Woo_We_Payments\Classes\StatusDetails\statusHistory;

class NoMessageInfo extends MessageDetail{

    public function __construct()
    {
        parent::__construct(null);
    }

    public function message(statusHistory $statusHistory): string
    {
        return "Nenhuma informação fornecida";
    }
}