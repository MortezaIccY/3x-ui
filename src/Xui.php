<?php

namespace XUI;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SessionCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Panel\panel;
use XUI\Server\server;
use XUI\Xray\Inbound;
use XUI\Xray\Outbond;
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
        $cookie_file = null,
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
        $cookie_file = (is_null($cookie_file)) ? __DIR__ . "/../$xui_host.cookie" : $cookie_file;
        $this->guzzle = new Client([
            'base_uri' => $this->address,
            'timeout' => $timeout,
            'proxy' => $proxy,
            'cookies' => new FileCookieJar($cookie_file, true)
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