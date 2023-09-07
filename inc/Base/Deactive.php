<?php
namespace Inc_Woo_We_Payments\Base;


class Deactive{
    public static function deactivate(){

        flush_rewrite_rules();
    }


}