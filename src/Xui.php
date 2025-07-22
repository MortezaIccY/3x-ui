<?php

namespace XUI;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Panel\Panel;
use XUI\Server\Server;
use XUI\Xray\Xray;

class Xui
{
    private string $address;
    private Client $guzzle;
    public int $output;
    public int $response_output;
    public Server $server;
    public Panel $panel;
    public Xray $xray;
    public const OUTPUT_JSON = 111;
    public const OUTPUT_OBJECT = 112;
    public const OUTPUT_ARRAY = 113;
    public const UNIT_BYTE = 1;
    public const UNIT_KILOBYTE = 1024;
    public const UNIT_MEGABYTE = 1024 * self::UNIT_KILOBYTE;
    public const UNIT_GIGABYTE = 1024 * self::UNIT_MEGABYTE;
    public const UNIT_TERABYTE = 1024 * self::UNIT_GIGABYTE;
    private const COOKIE_DIR = __DIR__ . '/.cookie';

    public function __construct(
        $host,
        $port,
        $uri_path = '/',
        $has_ssl = false,
        $cookie_dir = self::COOKIE_DIR,
        $timeout = 10,
        $proxy = null,
        $output = self::OUTPUT_OBJECT,
        $response_output = self::OUTPUT_OBJECT
    )
    {
        $uri_path = (empty($uri_path) || $uri_path == '/') ? '/' : $uri_path . '/';
        if ($has_ssl)
            $this->address = 'https://' . $host . ':' . $port . $uri_path;
        else
            $this->address = 'http://' . $host . ':' . $port . $uri_path;
        $cookie_path = $cookie_dir . "/$host.cookie";
        if (!file_exists($cookie_path)) {
            touch($cookie_path);
            chmod($cookie_path, 0600);
        } elseif (fileperms($cookie_path) != 0600) {
            chmod($cookie_path, 0600);
        }
        $this->guzzle = new Client([
            'base_uri' => $this->address,
            'timeout' => $timeout,
            'proxy' => $proxy,
            'cookies' => new FileCookieJar($cookie_path, true)
        ]);
        $this->output = $output;
        $this->response_output = $response_output;
    }

    /**
     * Login to xui panel\
     * Uses cookie session if logged in before.
     * @param $username
     * @param $password
     * @return object|array|string
     */
    public function login($username, $password): object|array|string
    {
        $st = microtime(true);
        if ($this->is_login()) {
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $this->server = new Server($this->guzzle, $this->output, $this->response_output);
            $this->panel = new Panel($this->guzzle, $this->output, $this->response_output);
            $this->xray = new Xray($this->guzzle, $this->output, $this->response_output);
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
                    $this->server = new Server($this->guzzle, $this->output, $this->response_output);
                    $this->panel = new Panel($this->guzzle, $this->output, $this->response_output);
                    $this->xray = new Xray($this->guzzle, $this->output, $this->response_output);
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

    /**
     * Generate any random string
     * @param int $length
     * @return string
     */
    public static function random(int $length = 32): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 1; $i <= $length; $i++) :
            $out .= $chars[rand(0, strlen($chars) - 1)];
        endfor;
        return $out;
    }

    /**
     * Generate a uuid
     * @return string
     */
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

    private function output(array|object|string $data): object|array|string
    {
        return match ($this->output) {
            Xui::OUTPUT_JSON => json::to_json($data),
            Xui::OUTPUT_OBJECT => json::to_object($data),
            Xui::OUTPUT_ARRAY => json::to_array($data)
        };
    }

    private function response_output(array|object|string $data): object|array|string
    {
        return match ($this->response_output) {
            Xui::OUTPUT_JSON => json::to_json($data),
            Xui::OUTPUT_OBJECT => json::to_object($data),
            Xui::OUTPUT_ARRAY => json::to_array($data)
        };
    }
}