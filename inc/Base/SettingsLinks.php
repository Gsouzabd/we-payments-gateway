<?php

namespace Inc_Woo_We_Payments\Base;


class SettingsLinks{

    protected $plugin;

    public function __construct()
    {
        $this->plugin = woo_we_payments_BASENAME;
    }

    public function register(){

        add_filter("plugin_action_links_$this->plugin", array($this, 'settings_link'));

    }

    public function settings_link($links){
        $settings_link = '<a href="admin.php?page=wc-settings&tab=integration&section=woo_we_payments">Configurações</a>';
        array_push($links, $settings_link);
        return $links;
    }


}