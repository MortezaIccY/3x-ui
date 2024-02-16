<?php

namespace XUI\Panel;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;

class Panel
{
    private Client $guzzle;
    public int $output;
    public int $response_output;
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
    }

    public function all_settings()
    {
        $st = microtime(true);
        $result = $this->guzzle->post('panel/setting/all');
        try {
            $result = $this->guzzle->post("panel/setting/all");
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

    public function get_setting(array|string $setting)
    {
        $panel_settings = new settings($this->all_settings());
        return $panel_settings->get($setting);
    }

    public function update_setting(array $settings)
    {
        $st = microtime(true);
        $panel_settings = new settings($this->all_settings());
        $panel_settings->update($settings);
        try {
            $result = $this->guzzle->post("panel/setting/update", [
                'form_params' => $panel_settings->settings
            ]);
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
    public function restart()
    {
        $st = microtime(true);
        $result = $this->guzzle->post('panel/setting/restartPanel');
        try {
            $result = $this->guzzle->post("panel/setting/restartPanel");
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