<?php

namespace XUI\Xray\Inbound;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Xray\Inbound\Protocols\DokodemoDoor\DokodemoDoor;
use XUI\Xray\Inbound\Protocols\Http\Http;
use XUI\Xray\Inbound\Protocols\Shadowsocks\Shadowsocks;
use XUI\Xray\Inbound\Protocols\Socks\Socks;
use XUI\Xray\Inbound\Protocols\Trojan\Trojan;
use XUI\Xray\Inbound\Protocols\Vless\Vless;
use XUI\Xray\Inbound\Protocols\Vmess\Vmess;

class Inbound
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

    /**
     * Add a new inbound
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|DokodemoDoor $config
     * @param string|null $remark
     * @param int $total_traffic
     * @param int|string $expiry_time
     * @param int $download
     * @param int $upload
     * @param bool $enable
     * @return array|false|mixed|string
     */
    public function add(
        Vmess|Vless|Trojan|Shadowsocks|Socks|Http|DokodemoDoor $config, string $remark = null, int $total_traffic = 0,
        int|string                                             $expiry_time = '', int $download = 0, int $upload = 0, bool $enable = true
    ): mixed
    {
        $st = microtime(true);
        $protocol = $config->protocol;
        $request_data = [
            'up' => $upload,
            'down' => $download,
            'total' => $total_traffic,
            'remark' => $remark,
            'enable' => $enable,
            'expiryTime' => $expiry_time . '000',
            'listen' => $config->listen,
            'port' => $config->port,
            'protocol' => $config->protocol,
        ];
        switch ($protocol):
            case 'vmess':
            case 'vless':
            case 'trojan':
            case 'shadowsocks':
                $request_data['settings'] = json::_out($config->settings->settings());
                $request_data['streamSettings'] = json::_out($config->stream_settings->stream_settings());
                $request_data['sniffing'] = json::_out($config->sniffing->sniffing());
            break;
            case 'socks':
            case 'http':
            case 'dokodemo-door':
                $request_data['settings'] = json::_out($config->settings->settings());
            break;
        endswitch;
        try {
            $result = $this->guzzle->post("panel/inbound/add", [
                'form_params' => $request_data
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

    /**
     * Get a list of inbounds
     * @return array|false|mixed|string
     * @throws GuzzleException
     */
    public function list(): mixed
    {
        $st = microtime(true);
        $result = $this->guzzle->post('panel/inbound/list');
        try {
            $result = $this->guzzle->post("panel/inbound/list");
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
     * Get an inbound config.\
     * Only return inbound in `response`!
     * @param int $inbound_id
     * @return array|false|mixed|string
     * @throws GuzzleException
     */
    public function get(int $inbound_id): mixed
    {
        $st = microtime(true);
        $last_output = $this->output;
        $last_response_output = $this->response_output;
        $this->output = self::OUTPUT_OBJECT;
        $this->response_output = self::OUTPUT_OBJECT;
        $result = $this->list();
        if ($result->ok) {
            $response = $result->response;
            if ($response->success) {
                $return = [
                    'ok' => false,
                    'error_code' => 500,
                    'error' => 'inbound not found'
                ];
                $inbounds_list = $response->obj;
                foreach ($inbounds_list as $inbound):
                    if ($inbound->id == $inbound_id):
                        $this->response_output = $last_response_output;
                        $inbound = json::_in(json::_out($inbound), true);
                        $et = microtime(true);
                        $tt = round($et - $st, 3);
                        $return = [
                            'ok' => true,
                            'response' => $this->response_output($inbound),
                            'size' => null,
                            'time_taken' => $tt
                        ];
                    endif;
                endforeach;
            } else {
                $return = [
                    'ok' => false,
                    'error_code' => 500,
                    'error' => 'Fetching inbounds list error: ' . $response->msg
                ];
            }
        } else {
            $return = [
                'ok' => false,
                'error_code' => $result->error_code,
                'error' => 'Fetching inbounds list error: ' . $result->error
            ];
        }
        $this->output = $last_output;
        $this->response_output = $last_response_output;
        return $this->output($return);
    }

    /**
     * Update an inbound
     * @param int $inbound_id
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|DokodemoDoor|null $config
     * @param string|null $remark
     * @param int|null $total_traffic
     * @param int|string|null $expiry_time
     * @param int|null $download
     * @param int|null $upload
     * @param bool|null $enable
     * @return array|false|mixed|string
     * @throws GuzzleException
     */
    public function update(
        int $inbound_id, Vmess|Vless|Trojan|Shadowsocks|Socks|Http|DokodemoDoor $config = null, string $remark = null,
        int $total_traffic = null, int|string $expiry_time = null, int $download = null, int $upload = null, bool $enable = null
    ): mixed
    {
        $st = microtime(true);
        $last_output = $this->output;
        $last_response_output = $this->response_output;
        $this->output = self::OUTPUT_OBJECT;
        $this->response_output = self::OUTPUT_OBJECT;
        $result = $this->list();
        if ($result->ok) {
            $response = $result->response;
            if ($response->success) {
                $return = [
                    'ok' => false,
                    'error_code' => 500,
                    'error' => 'inbound not found'
                ];
                $inbounds_list = $response->obj;
                foreach ($inbounds_list as $inbound):
                    if ($inbound->id == $inbound_id):
                        $inbound_protocol = $inbound->protocol;
                        $protocol = (is_null($config)) ? $inbound_protocol : $config->protocol;
                        if ($protocol == $inbound_protocol) {
                            $remark = (is_null($remark)) ? $inbound->remark : $remark;
                            $total_traffic = (is_null($total_traffic)) ? $inbound->total : $total_traffic;
                            $expiry_time = (is_null($expiry_time)) ? $inbound->expiryTime : $expiry_time . '000';
                            $download = (is_null($download)) ? $inbound->down : $download;
                            $upload = (is_null($upload)) ? $inbound->up : $upload;
                            $enable = (is_null($enable)) ? $inbound->enable : $enable;
                            $listen = (is_null($config)) ? $inbound->listen : $config->listen;
                            $port = (is_null($config)) ? $inbound->port : $config->port;
                            $request_data = [
                                'up' => $upload,
                                'down' => $download,
                                'total' => $total_traffic,
                                'remark' => $remark,
                                'enable' => $enable,
                                'expiryTime' => $expiry_time,
                                'listen' => $listen,
                                'port' => $port,
                                'protocol' => $protocol,
                            ];
                            switch ($protocol):
                                case 'vmess':
                                case 'vless':
                                case 'trojan':
                                case 'shadowsocks':
                                    $request_data['settings'] = (is_null($config)) ? $inbound->settings : json::_out($config->settings->settings());
                                    $request_data['streamSettings'] = (is_null($config)) ? $inbound->streamSettings : json::_out($config->stream_settings->stream_settings());
                                    $request_data['sniffing'] = (is_null($config)) ? $inbound->sniffing : json::_out($config->sniffing->sniffing());
                                break;
                                case 'socks':
                                case 'http':
                                case 'dokodemo-door':
                                    $request_data['settings'] = (is_null($config)) ? $inbound->settings : json::_out($config->settings->settings());
                                break;
                            endswitch;
                            $this->response_output = $last_response_output;
                            try {
                                $result = $this->guzzle->post("panel/inbound/update/$inbound_id", [
                                    'form_params' => $request_data
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
                        } else {
                            $return = [
                                'ok' => false,
                                'error_code' => 500,
                                'error' => 'inbound current protocol and $config protocol must be the same'
                            ];
                        }
                        break;
                    endif;
                endforeach;
            } else {
                $return = [
                    'ok' => false,
                    'error_code' => 500,
                    'error' => 'Fetching inbounds list error: ' . $response->msg
                ];
            }
        } else {
            $return = [
                'ok' => false,
                'error_code' => $result->error_code,
                'error' => 'Fetching inbounds list error: ' . $result->error
            ];
        }
        $this->output = $last_output;
        $this->response_output = $last_response_output;
        return $this->output($return);
    }

    /**
     * Delete an inbound
     * @param int $inbound_id
     * @return array|false|mixed|string
     */
    public function delete(int $inbound_id): mixed
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/del/$inbound_id");
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
     * Export an inbound.\
     * Only return json encoded exported inbound in `response`!
     * @param int $inbound_id
     * @return mixed
     * @throws GuzzleException
     */
    public function export(int $inbound_id): mixed
    {
        $st = microtime(true);
        $last_output = $this->output;
        $last_response_output = $this->response_output;
        $this->output = self::OUTPUT_OBJECT;
        $this->response_output = self::OUTPUT_OBJECT;
        $result = $this->get($inbound_id);
        if ($result->ok) {
            $inbound = $result->response;
            $this->response_output = $last_response_output;
            $inbound = json::_out($inbound);
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = [
                'ok' => true,
                'response' => $inbound,
                'size' => null,
                'time_taken' => $tt
            ];
        } else {
            $return = [
                'ok' => false,
                'error_code' => $result->error_code,
                'error' => 'Fetching inbound error: ' . $result->error
            ];
        }
        $this->output = $last_output;
        $this->response_output = $last_response_output;
        return $this->output($return);
    }

    /**
     * Import an inbound.\
     * Only json encoded exported inbound accepted!
     * @param string $exported_inbound
     * @return array|false|mixed|string
     * @throws GuzzleException
     */
    public function import(string $exported_inbound): mixed
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/import", [
                'form_params' => ['data' => $exported_inbound]
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

    public function get_client_ips($client_email)
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/clientIps/$client_email");
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

    public function onlines()
    {
        $st = microtime(true);
        try {
            $result = $this->guzzle->post("panel/inbound/onlines");
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