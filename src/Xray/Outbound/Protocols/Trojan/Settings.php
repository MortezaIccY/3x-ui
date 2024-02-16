<?php

namespace XUI\Xray\Outbound\Protocols\Trojan;

use JSON\json;
use stdClass;

class Settings
{
    public string $address;
    public int $port;
    public string $password;
    public string|null $email;

    public function __construct(string $address, int $port, string $password, string|null $email = null)
    {
        $this->address = $address;
        $this->port = $port;
        $this->password = $password;
        $this->email = $email;
    }

    /**
     * Returns fully structured settings for xray json config
     * @return array
     */
    public function settings(): array
    {
        $return = [
            'servers' => [
                [
                    'address' => $this->address,
                    'port' => $this->port,
                    'password' => $this->password
                ]
            ]
        ];
        if (!is_null($this->email)) $return['servers'][0]['email'] = $this->email;
        return $return;
    }

    public static function random(int $length = 32): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 1; $i <= $length; $i++) :
            $out .= $chars[rand(0, strlen($chars) - 1)];
        endfor;
        return $out;
    }

    public static function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}