<?php

namespace XUI\Xray;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Xray\Reverse\Reverse;
use XUI\Xray\Routing\Routing;
use XUI\Xray\Inbound\Inbound;
use XUI\Xray\Outbound\Outbound;

class Xray
{
    private Client $guzzle;
    public int $output;
    public int $response_output;
    public Inbound $inbound;
    public Outbound $outbound;
    public Routing $routing;
    public Reverse $reverse;
    public const OUTPUT_JSON = 111;
    public const OUTPUT_OBJECT = 112;
    public const OUTPUT_ARRAY = 113;
    public const UNIT_BYTE = 1;
    public const UNIT_KILOBYTE = 1024;
    public const UNIT_MEGABYTE = 1024 * self::UNIT_KILOBYTE;
    public const UNIT_GIGABYTE = 1024 * self::UNIT_MEGABYTE;
    public const UNIT_TERABYTE = 1024 * self::UNIT_GIGABYTE;

    public function __construct(Client $guzzle, int $output = self::OUTPUT_OBJECT, int $response_output = self::OUTPUT_OBJECT)
    {
        $this->guzzle = $guzzle;
        $this->output = $output;
        $this->response_output = $response_output;
        $this->inbound = new Inbound($this->guzzle, $this->output, $this->response_output);
        $this->outbound = new Outbound($this->guzzle, $this->output, $this->response_output);
        $this->routing = new Routing($this->guzzle, $this->output, $this->response_output);
        $this->reverse = new Reverse($this->guzzle, $this->output, $this->response_output);
    }

    /**
     * Returns Xray json config with inbound tags
     * @return mixed
     * @throws GuzzleException
     */
    public function get_settings(): mixed
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/xray");
            $body = $result->getBody();
            $response = $this->response_output(json::_in($body->getContents(), true));
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    /**
     * Returns only json Xray full config
     * @return mixed
     */
    public function get_full_config(): mixed
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/xray");
            $body = $result->getBody();
            $response = $this->response_output(json::_in(json::_in($body->getContents(), true)['obj'], true)['xraySetting']);
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    public function get_config(array|string $config): mixed
    {
        $st = microtime(true);
        $last_output = $this->output;
        $this->output = self::OUTPUT_ARRAY;
        $last_response_output = $this->response_output;
        $this->response_output = self::OUTPUT_ARRAY;
        $full_config = $this->get_full_config();
        if ($full_config['ok']) {
            $response = $full_config['response'];
            $xray_settings = new settings($response);
            $response = $xray_settings->get($config);
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $this->response_output = $last_response_output;
            $return = ['ok' => true, 'response' => $this->response_output($response), 'size' => null, 'time_taken' => $tt];
        } else {
            $return = ['ok' => false, 'error_code' => $full_config['error_code'], 'error' => $full_config['error']];
        }
        $this->output = $last_output;
        $this->response_output = $last_response_output;
        return $this->output($return);
    }

    public function update_config(array $update)
    {
        $st = microtime(true);
        $last_output = $this->output;
        $this->output = self::OUTPUT_ARRAY;
        $last_response_output = $this->response_output;
        $this->response_output = self::OUTPUT_ARRAY;
        $full_config = $this->get_full_config();
        if ($full_config['ok']) {
            $response = $full_config['response'];
            $xray_settings = new settings($response);
            $xray_settings->update($update);
            try {
                $result = $this->guzzle->post("panel/xray/update", [
                    'form_params' => [
                        'xraySetting' => json::_out($xray_settings->settings())
                    ]
                ]);
                $body = $result->getBody();
                $this->response_output = $last_response_output;
                $response = $this->response_output(json::_in($body->getContents(), true));
                $et = microtime(true);
                $tt = round($et - $st, 3);
                $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
            } catch (GuzzleException $err) {
                $error_code = $err->getCode();
                $error = $err->getMessage();
                $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
            }
        } else {
            $return = ['ok' => false, 'error_code' => $full_config['error_code'], 'error' => $full_config['error']];
        }
        $this->output = $last_output;
        $this->response_output = $last_response_output;
        return $this->output($return);
    }

    public function restart()
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/setting/restartXrayService");
            $body = $result->getBody();
            $response = $this->response_output(json::_in($body->getContents(), true));
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $response, 'size' => $body->getSize(), 'time_taken' => $tt];
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return $this->output($return);
    }

    private function output(array $data): mixed
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

    private function response_output(array $data): mixed
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