<?php

namespace XUI\Xray\Outbound;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JSON\json;
use XUI\Xray\Outbound\Protocols\Blackhole\Blackhole;
use XUI\Xray\Outbound\Protocols\Dns\Dns;
use XUI\Xray\Outbound\Protocols\Freedom\Freedom;
use XUI\Xray\Outbound\Protocols\Http\Http;
use XUI\Xray\Outbound\Protocols\Shadowsocks\Shadowsocks;
use XUI\Xray\Outbound\Protocols\Socks\Socks;
use XUI\Xray\Outbound\Protocols\Trojan\Trojan;
use XUI\Xray\Outbound\Protocols\Vless\Vless;
use XUI\Xray\Outbound\Protocols\Vmess\Vmess;
use XUI\Xray\Xray;

class Outbound
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

    public function add(
        string $tag, Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config, array $proxy_settings = null,
        string $send_through = '0.0.0.0', array $mux = []
    )
    {
        $st = microtime(true);
        $protocol = $config->protocol;
        $xray = new Xray($this->guzzle, Xray::OUTPUT_ARRAY, Xray::OUTPUT_ARRAY);
        $xray_outbounds = $xray->get_config('outbounds');
        if ($xray_outbounds['ok']) {
            $xray_outbounds = $xray_outbounds['response'];
            if (isset($config->stream_settings))
                $stream_settings = $config->stream_settings->stream_settings();
            else
                $stream_settings = [];
            $outbound = [
                'sendThrough' => $send_through,
                'protocol' => $protocol,
                'settings' => $config->settings->settings(),
                'tag' => $tag,
                'streamSettings' => $stream_settings,
            ];
            if (!empty($proxy_settings)) $outbound['proxySettings'] = $proxy_settings;
            if (!empty($mux)) $outbound['mux'] = $mux;
            $xray_outbounds[] = $outbound;
            $result = $xray->update_config(['outbounds' => $xray_outbounds]);
            if ($result['ok']) {
                if ($result['response']['success']) {
                    $response = $result['response'];
                    $et = microtime(true);
                    $tt = round($et - $st, 3);
                    $return = ['ok' => true, 'response' => $response, 'size' => $result['size'], 'time_taken' => $tt];
                } else {
                    $return = ['ok' => false, 'error_code' => 500, 'error' => $result['response']['msg']];
                }
            } else {
                $return = ['ok' => false, 'error_code' => $result['error_code'], 'error' => $result['error']];
            }
        } else {
            $return = $xray_outbounds;
        }
        return $this->output($return);
    }

    public function list()
    {
        $xray = new Xray($this->guzzle, $this->output, $this->response_output);
        return $xray->get_config('outbounds');
    }

    public function get(string $outbound_tag)
    {
        $st = microtime(true);
        $xray = new Xray($this->guzzle, Xray::OUTPUT_ARRAY, Xray::OUTPUT_ARRAY);
        $xray_outbounds = $xray->get_config('outbounds');
        if ($xray_outbounds['ok']) {
            $xray_outbounds = $xray_outbounds['response'];
            $found = false;
            foreach ($xray_outbounds as $outbound):
                if ($outbound['tag'] == $outbound_tag):
                    $et = microtime(true);
                    $tt = round($et - $st, 3);
                    $return = [
                        'ok' => true,
                        'response' => $this->response_output($outbound),
                        'size' => null,
                        'time_taken' => $tt
                    ];
                    $found = true;
                    break;
                endif;
            endforeach;
            if (!$found) $return = ['ok' => false, 'error_code' => 404, 'error' => 'Outbound tag not found'];
        } else {
            $return = $xray_outbounds;
        }
        return $this->output($return);
    }

    public function exist(string $outbound_tag): bool
    {
        $xray = new Xray($this->guzzle, Xray::OUTPUT_ARRAY, Xray::OUTPUT_ARRAY);
        $xray_outbounds = $xray->get_config('outbounds');
        if ($xray_outbounds['ok']) {
            $xray_outbounds = $xray_outbounds['response'];
            $exist = false;
            foreach ($xray_outbounds as $outbound):
                if ($outbound['tag'] == $outbound_tag):
                    $exist = true;
                    break;
                endif;
            endforeach;
        } else {
            $exist = false;
        }
        return $exist;
    }

    public function update(
        string $outbound_tag, string $tag = null, Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config = null,
        array  $proxy_settings = null, string $send_through = null, array $mux = null
    )
    {
        $st = microtime(true);
        $xray = new Xray($this->guzzle, Xray::OUTPUT_ARRAY, Xray::OUTPUT_ARRAY);
        $xray_outbounds = $xray->get_config('outbounds');
        if ($xray_outbounds['ok']) {
            $xray_outbounds = $xray_outbounds['response'];
            $updated = false;
            foreach ($xray_outbounds as $key => $outbound):
                if ($outbound['tag'] == $outbound_tag):
                    $tag = (is_null($tag)) ? $outbound['tag'] : $tag;
                    if (!is_null($config)) {
                        $protocol = $config->protocol;
                        $settings = $config->settings->settings();
                        if (isset($config->stream_settings))
                            $stream_settings = $config->stream_settings->stream_settings();
                        else
                            $stream_settings = [];
                    } else {
                        $protocol = $outbound['protocol'];
                        $settings = $outbound['settings'];
                        $stream_settings = $outbound['streamSettings'];
                    }
                    $outbound = [
                        'sendThrough' => $send_through,
                        'protocol' => $protocol,
                        'settings' => $settings,
                        'tag' => $tag,
                        'streamSettings' => $stream_settings,
                        'proxySettings' => $proxy_settings,
                        'mux' => $mux
                    ];
                    if (!is_null($proxy_settings)) $outbound['proxySettings'] = $proxy_settings;
                    if (!is_null($send_through)) $outbound['sendThrough'] = $send_through;
                    if (!is_null($mux)) $outbound['mux'] = $mux;
                    $xray_outbounds[$key] = $outbound;
                    $updated = true;
                    break;
                endif;
            endforeach;
            if ($updated) {
                $result = $xray->update_config(['outbounds' => $xray_outbounds]);
                if ($result['ok']) {
                    if ($result['response']['success']) {
                        $response = $result['response'];
                        $et = microtime(true);
                        $tt = round($et - $st, 3);
                        $return = ['ok' => true, 'response' => $response, 'size' => $result['size'], 'time_taken' => $tt];
                    } else {
                        $return = ['ok' => false, 'error_code' => 500, 'error' => $xray_outbounds['response']['msg']];
                    }
                } else {
                    $return = ['ok' => false, 'error_code' => $result['error_code'], 'error' => $result['error']];
                }
            } else {
                $return = ['ok' => false, 'error_code' => 404, 'error' => 'Outbound tag not found'];
            }
        } else {
            $return = $xray_outbounds;
        }
        return $this->output($return);
    }

    public function delete(string $outbound_tag): mixed
    {
        $st = microtime(true);
        $xray = new Xray($this->guzzle, Xray::OUTPUT_ARRAY, Xray::OUTPUT_ARRAY);
        $xray_outbounds = $xray->get_config('outbounds');
        if ($xray_outbounds['ok']) {
            $xray_outbounds = $xray_outbounds['response'];
            $deleted = false;
            foreach ($xray_outbounds as $key => $outbound):
                if ($outbound['tag'] == $outbound_tag):
                    unset($xray_outbounds[$key]);
                    $deleted = true;
                    break;
                endif;
            endforeach;
            if ($deleted) {
                $result = $xray->update_config(['outbounds' => $xray_outbounds]);
                if ($result['ok']) {
                    if ($result['response']['success']) {
                        $response = $result['response'];
                        $et = microtime(true);
                        $tt = round($et - $st, 3);
                        $return = ['ok' => true, 'response' => $response, 'size' => $result['size'], 'time_taken' => $tt];
                    } else {
                        $return = ['ok' => false, 'error_code' => 500, 'error' => $xray_outbounds['response']['msg']];
                    }
                } else {
                    $return = ['ok' => false, 'error_code' => $result['error_code'], 'error' => $result['error']];
                }
            } else {
                $return = ['ok' => false, 'error_code' => 404, 'error' => 'Outbound tag not found'];
            }
        } else {
            $return = $xray_outbounds;
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