<?php

namespace XUI;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Panel\panel;
use XUI\Server\server;
use XUI\Xray\Inbound\Protocols\Http\Http;
use XUI\Xray\Inbound\Protocols\Shadowsocks\Shadowsocks;
use XUI\Xray\Inbound\Protocols\Socks\Socks;
use XUI\Xray\Inbound\Protocols\Trojan\Trojan;
use XUI\Xray\Inbound\Protocols\Vless\Vless;
use XUI\Xray\Inbound\Protocols\Vmess\Vmess;
use XUI\Xray\xray;

require_once 'vendor/autoload.php';

class Xui
{
    public bool $has_ssl;
    public string $host;
    public string $port;
    public string $uri_path;
    private string $address;
    private Client $guzzle;
    public int $output;
    public int $response_output;
    public server $server;
    public panel $panel;
    public xray $xray;
    public const OUTPUT_JSON = 111;
    public const OUTPUT_OBJECT = 112;
    public const OUTPUT_ARRAY = 113;
    public const UNIT_BYTE = 1;
    public const UNIT_KILOBYTE = 1024;
    public const UNIT_MEGABYTE = 1024 * self::UNIT_KILOBYTE;
    public const UNIT_GIGABYTE = 1024 * self::UNIT_MEGABYTE;
    public const UNIT_TERABYTE = 1024 * self::UNIT_GIGABYTE;

    public function __construct(
        $xui_host,
        $xui_port,
        $xui_uri_path = '/',
        $has_ssl = false,
        $cookie_dir = null,
        $timeout = 5,
        $proxy = null,
        $output = self::OUTPUT_OBJECT,
        $response_output = self::OUTPUT_OBJECT
    )
    {
        $this->has_ssl = $has_ssl;
        $this->host = $xui_host;
        $this->port = $xui_port;
        $this->uri_path = $xui_uri_path;
        if ($this->has_ssl)
            $this->address = 'https://' . $this->host . ':' . $this->port . $this->uri_path;
        else
            $this->address = 'http://' . $this->host . ':' . $this->port . $this->uri_path;
        $cookie_dir = (is_null($cookie_dir)) ? "$xui_host.cookie" : $cookie_dir . "/$xui_host.cookie";
        $this->guzzle = new Client([
            'base_uri' => $this->address,
            'timeout' => $timeout,
            'proxy' => $proxy,
            'cookies' => new FileCookieJar($cookie_dir, true)
        ]);
        $this->output = $output;
        $this->response_output = $response_output;
    }

    public function login($username, $password)
    {
        $st = microtime(true);
        if ($this->is_login()) {
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $this->server = new server($this->guzzle, $this->output, $this->response_output);
            $this->panel = new panel($this->guzzle, $this->output, $this->response_output);
            $this->xray = new xray($this->guzzle, $this->output, $this->response_output);
            $return = ['ok' => true, 'response' => null, 'size' => null, 'time_taken' => $tt];
        } else {
            try {
                $result = $this->guzzle->post('login', [
                    'form_params' => [
                        'username' => $username,
                        'password' => $password
                    ]
                ]);
                $body = $result->getBody();
                $contents = json::_in($body->getContents(), true);
                if ($contents['success']):
                    $this->server = new server($this->guzzle, $this->output, $this->response_output);
                    $this->panel = new panel($this->guzzle, $this->output, $this->response_output);
                    $this->xray = new xray($this->guzzle, $this->output, $this->response_output);
                endif;
                $response = $this->response_output($contents);
                $et = microtime(true);
                $tt = round($et - $st, 3);
                $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
            } catch (GuzzleException $err) {
                $error_code = $err->getCode();
                $error = $err->getMessage();
                $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
            }
        }
        return $this->output($return);
    }

    private function is_login(): bool
    {
        try {
            $this->guzzle->post('server/status');
            return true;
        } catch (GuzzleException $err) {
            return false;
        }
    }

//    public static function v2rayng(Vmess|Vless|Shadowsocks|Trojan|Socks|Http $inbound_config, $address, $name)
//    {
//        $inbound_stream = $inbound_config->stream_settings;
//        switch ($inbound_config->protocol):
//            case 'vmess':
//                $config = [
//                    'v' => 2,
//                    'ps' => $name,
//                    'add' => $address,
//                    'port' => $inbound_config->port,
//                    'id' => $inbound_config->settings->clients[0]['id'],
//                ];
//                switch ($inbound_stream->network):
//                    case 'tcp':
//                        $config['net']=
//                        $config['path']=()$inbound_stream->tcp_settings['']
//                    break;
//                    case 'ws':
//
//                    break;
//                endswitch;
//            break;
//            case 'vless':
//                $config['v'] = 2;
//                $config['ps'] = $name;
//                $config['add'] = $address;
//                $config['port'] = $inbound_config->port;
//                $config['id'] = $inbound_config->settings->clients[0]['id'];
//                switch ($inbound_stream->network):
//                    case 'tcp':
//
//                    break;
//                    case 'ws':
//
//                    break;
//                endswitch;
//            break;
//            case 'shadowsocks':
//
//            break;
//            case 'trojan':
//
//            break;
//            case 'socks':
//
//            break;
//            case 'http':
//
//            break;
//        endswitch;
//    }

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

    private function output(array $data)
    {
        switch ($this->output):
            case self::OUTPUT_JSON:
                $return = json::_out($data, true);
            break;
            case self::OUTPUT_OBJECT:
                $data = json::_out($data);
                $return = json::_in($data);
            break;
            default:
                $return = $data;
            break;
        endswitch;
        return $return;
    }

    private function response_output(array $data)
    {
        switch ($this->response_output):
            case self::OUTPUT_JSON:
                $return = json::_out($data, true);
            break;
            case self::OUTPUT_OBJECT:
                $data = json::_out($data);
                $return = json::_in($data);
            break;
            default:
                $return = $data;
            break;
        endswitch;
        return $return;
    }
}