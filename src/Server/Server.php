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

    /**
     * Get Server&Panel&Xray-core status and information
     * @return object|array|string
     */
    public function status(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/status");
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
     * Get SQLite database of 3x-ui in file or raw string\
     * Only return raw database if path not set!\
     * Set `$path` to get file of database. `(Example: /www/wwwroot/xui.example.com/x-ui.db)`
     * @param string|null $path
     * @return array|object|string
     */
    public function get_db(string $path = null): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->get("server/getDb");
            $body = $result->getBody();
            $response = $body->getContents();
            if (isset($path)) {
                file_put_contents($path, $response);
                $et = microtime(true);
                $tt = round($et - $st, 3);
                $return = ['ok' => true, 'response' => null, 'size' => $body->getSize(), 'time_taken' => $tt];
            } else {
                $return = $response;
            }
        } catch (GuzzleException $err) {
            $error_code = $err->getCode();
            $error = $err->getMessage();
            $return = ['ok' => false, 'error_code' => $error_code, 'error' => $error];
        }
        return (is_array($return)) ? $this->output($return) : $return;
    }

    /**
     * Import SQLite 3x-ui database to panel\
     * Set `$path_or_db` path to database file or raw string of database.\
     * __Warning :__ Username/Password Or Panel access link can be changed based on imported database!\
     * __Alert :__ This action cannot be reversed!
     * @param string $path_or_db
     * @return object|array|string
     */
    public function import_db(string $path_or_db): object|array|string
    {
        $st = microtime(true);
        try {
            if (file_exists($path_or_db)) {
                $file = $path_or_db;
                $temp = false;
            } else {
                $file = __DIR__ . '/x-ui.db';
                file_put_contents($file, $path_or_db);
                chmod($file, 0600);
                $temp = true;
            }
            $file_name = basename($file);
            $result = $this->guzzle->post("server/importDB", [
                'multipart' => [
                    [
                        'name' => 'db',
                        'contents' => fopen($file, 'r'),
                        'filename' => $file_name,
                        'headers' => [
                            'Content-Type' => 'application/octet-stream'
                        ]
                    ]
                ]
            ]);
            if ($temp) unlink($file);
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
     * Restart Xray-core to apply any changes made on xray config
     * @return object|array|string
     */
    public function restart_xray(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/restartXrayService");
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
     * Stop Xray-core
     * @return object|array|string
     */
    public function stop_xray(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/stopXrayService");
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
     * Get full config of xray config
     * @link https://xtls.github.io/en/config/
     * @return object|array|string
     */
    public function get_xray_config(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/getConfigJson");
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
     * Get xui log
     * @param int $count
     * @param string $level
     * @param bool $syslog
     * @return object|array|string
     */
    public function get_xui_log(int $count = 10, string $level = 'notice', bool $syslog = true): object|array|string
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
     * Get x25519 certificate for reality
     * @return object|array|string
     */
    public function get_x25519_cert(): object|array|string
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("server/getNewX25519Cert");
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