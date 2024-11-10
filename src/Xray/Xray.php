<?php

namespace XUI\Xray;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Xray\Reverse\Reverse;
use XUI\Xray\Routing\Routing;
use XUI\Xray\Inbound\Inbound;
use XUI\Xray\Outbound\Outbound;
use XUI\Xui;

class Xray
{
    private Client $guzzle;
    public int $output;
    public int $response_output;
    public Inbound $inbound;
    public Outbound $outbound;
    public Routing $routing;
    public Reverse $reverse;

    public function __construct(Client $guzzle, int $output = Xui::OUTPUT_OBJECT, int $response_output = Xui::OUTPUT_OBJECT)
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
     * @return string|array|object
     * @throws GuzzleException
     */
    public function get_settings(): string|array|object
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/xray");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
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
     * @return string|array|object
     */
    public function get_full_config(): string|array|object
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

    public function get_config(array|string $config): string|array|object
    {
        $st = microtime(true);
        $last_output = $this->output;
        $this->output = Xui::OUTPUT_ARRAY;
        $last_response_output = $this->response_output;
        $this->response_output = Xui::OUTPUT_ARRAY;
        $full_config = $this->get_full_config();
        if ($full_config['ok']) {
            $response = $full_config['response'];
            $xray_settings = new Settings($response);
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

    public function update_config(array $update): object|array|string
    {
        $st = microtime(true);
        $last_output = $this->output;
        $this->output = Xui::OUTPUT_ARRAY;
        $last_response_output = $this->response_output;
        $this->response_output = Xui::OUTPUT_ARRAY;
        $full_config = $this->get_full_config();
        if ($full_config['ok']) {
            $response = $full_config['response'];
            $xray_settings = new Settings($response);
            $xray_settings->update($update);
            try {
                $result = $this->guzzle->post("panel/xray/update", [
                    'form_params' => [
                        'xraySetting' => json::_out($xray_settings->settings())
                    ]
                ]);
                $body = $result->getBody();
                $this->response_output = $last_response_output;
                $response = $this->response_output($body->getContents());
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

    public function restart(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/setting/restartXrayService");
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
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

    public function set_config(array|string $full_config): object|array|string
    {
        $st = microtime(true);
        try {
            error_log($full_config);
            $result = $this->guzzle->post("panel/xray/update", [
                'form_params' => [
                    'xraySetting' => is_array($full_config) ? json::_out($full_config) : $full_config
                ]
            ]);
            $body = $result->getBody();
            $response = $this->response_output($body->getContents());
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

    private function output(array|object|string $data): object|array|string
    {
        switch ($this->output):
            case Xui::OUTPUT_JSON:
                $return = json::to_json($data);
            break;
            case Xui::OUTPUT_OBJECT:
                $return = json::to_object($data);
            break;
            case Xui::OUTPUT_ARRAY:
                $return = json::to_array($data);
            break;
        endswitch;
        return $return;
    }

    private function response_output(array|object|string $data): object|array|string
    {
        switch ($this->response_output):
            case Xui::OUTPUT_JSON:
                $return = json::to_json($data);
            break;
            case Xui::OUTPUT_OBJECT:
                $return = json::to_object($data);
            break;
            case Xui::OUTPUT_ARRAY:
                $return = json::to_array($data);
            break;
        endswitch;
        return $return;
    }
}