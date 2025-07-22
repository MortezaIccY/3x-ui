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
     */
    private function get_full_config(): string|array|object
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
     * Return only inbound tags in response
     * @return array|object|string
     */
    public function get_inbound_tags(): object|array|string
    {
        $result = json::to_array($this->get_full_config());
        if ($result['ok']) {
            $response = json::to_array($result['response']);
            if ($response['success']) {
                $obj = json::to_array($response['obj']);
                $return = ['ok' => true, 'response' => $this->response_output($obj['inboundTags']), 'size' => $result['size'], 'time_taken' => $result['time_taken']];
            } else {
                $return = ['ok' => true, 'response' => $this->response_output($response), 'size' => $result['size'], 'time_taken' => $result['time_taken']];
            }
        } else {
            $return = $result;
        }
        return $this->output($return);
    }

    /**
     * Returns only Xray configs in response
     * @return string|array|object
     */
    public function get_configs(): string|array|object
    {
        $result = json::to_array($this->get_full_config());
        if ($result['ok']) {
            $response = json::to_array($result['response']);
            if ($response['success']) {
                $obj = json::to_array($response['obj']);
                $return = ['ok' => true, 'response' => $this->response_output($obj['xraySetting']), 'size' => $result['size'], 'time_taken' => $result['time_taken']];
            } else {
                $return = ['ok' => true, 'response' => $this->response_output($response), 'size' => $result['size'], 'time_taken' => $result['time_taken']];
            }
        } else {
            $return = $result;
        }
        return $this->output($return);
    }

    /**
     * Get a config/configs from xray configs
     * @param array|string $config
     * @return string|array|object
     */
    public function get_config(array|string $config): string|array|object
    {
        $st = microtime(true);
        $full_config = json::to_array($this->get_configs());
        if ($full_config['ok']) {
            $response = json::to_array($full_config['response']);
            if (!isset($response['success'])):
                $xray_settings = new Settings($response);
                $response = $xray_settings->get($config);
            endif;
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $this->response_output($response), 'size' => null, 'time_taken' => $tt];
        } else {
            $return = ['ok' => false, 'error_code' => $full_config['error_code'], 'error' => $full_config['error']];
        }
        return $this->output($return);
    }

    /**
     * Update a config/configs from xray configs
     * @param array $update
     * @return object|array|string
     */
    public function update_config(array $update): object|array|string
    {
        $st = microtime(true);
        $full_config = json::to_array($this->get_configs());
        if ($full_config['ok']) {
            $response = json::to_array($full_config['response']);
            if (!isset($response['success'])) {
                $xray_settings = new Settings($response);
                $xray_settings->update($update);
                try {
                    $result = $this->guzzle->post("panel/xray/update", [
                        'form_params' => [
                            'xraySetting' => json::_out($xray_settings->settings())
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
            } else {
                $return = ['ok' => false, 'error_code' => 503, 'error' => $response['msg']];
            }
        } else {
            $return = ['ok' => false, 'error_code' => $full_config['error_code'], 'error' => $full_config['error']];
        }
        return $this->output($return);
    }

    /**
     * Restart Xray-core
     * @return object|array|string
     */
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

    /**
     * change full config of xray config\
     * Most uses for reset xray configs to default.
     * @param array|string|object $full_config
     * @return object|array|string
     */
    public function set_config(array|string|object $full_config): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/xray/update", [
                'form_params' => [
                    'xraySetting' => json::to_json($full_config)
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