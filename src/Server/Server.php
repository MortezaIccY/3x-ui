<?php

namespace XUI\Server;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Xui;

class Server
{
    private Client $guzzle;
    public int $output;
    public int $response_output;

    public function __construct(Client $guzzle, int $output = Xui::OUTPUT_OBJECT, int $response_output = Xui::OUTPUT_OBJECT)
    {
        $this->guzzle = $guzzle;
        $this->output = $output;
        $this->response_output = $response_output;
    }

    public function status()
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/status");
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

    public function restart_xray()
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/restartXrayService");
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

    public function stop_xray()
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/stopXrayService");
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

    public function get_xray_config()
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/getConfigJson");
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

    public function get_xui_log(int $count = 10, string $level = 'notice', bool $syslog = true)
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/logs/$count", [
                'form_params' => [
                    'level' => $level,
                    'syslog' => $syslog,
                ]
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
    public function get_x25519_cert()
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/getNewX25519Cert");
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
            case Xui::OUTPUT_JSON:
                $return = json::_out($data, true);
            break;
            case Xui::OUTPUT_OBJECT:
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
            case Xui::OUTPUT_JSON:
                $return = json::_out($data, true);
            break;
            case Xui::OUTPUT_OBJECT:
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