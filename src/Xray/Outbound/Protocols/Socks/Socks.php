<?php

namespace XUI\Xray\Outbound\Protocols\Socks;

use JSON\json;
use XUI\Xray\Outbound\Protocols\Socks\Settings;

class Socks
{
    public string $protocol = 'socks';
    public Settings $settings;
    public function __construct(string $settings = null)
    {
        if (!is_null($settings)):
            $settings = json::_in($settings, true);
            $this->settings = new Settings($settings['servers'][0]['address'], $settings['servers'][0]['port'], $settings['servers'][0]['users']);
        endif;
    }

    public function generate(
        Settings $settings
    ): void
    {
        $this->settings = $settings;
    }

    public function modify(
        Settings $settings = null
    ): void
    {
        if (!is_null($settings)) $this->settings = $settings;
    }
}