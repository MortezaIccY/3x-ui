<?php

namespace XUI\Xray\Outbound;

use GuzzleHttp\Client;
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
use XUI\Xray\Inbound\Protocols\Http\Http as ib_Http;
use XUI\Xray\Inbound\Protocols\Shadowsocks\Shadowsocks as ib_Shadowsocks;
use XUI\Xray\Inbound\Protocols\Socks\Socks as ib_Socks;
use XUI\Xray\Inbound\Protocols\Trojan\Trojan as ib_Trojan;
use XUI\Xray\Inbound\Protocols\Vless\Vless as ib_Vless;
use XUI\Xray\Inbound\Protocols\Vmess\Vmess as ib_Vmess;
use XUI\Xray\Inbound\Protocols\StreamSettings as ib_StreamSettings;
use XUI\Xray\Xray;
use XUI\Xui;

class Outbound
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
     * Add outbound
     * @param string $tag
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config
     * @param array|null $proxy_settings
     * @param string $send_through
     * @param array $mux
     * @return object|array|string
     */
    public function add(
        string $tag, Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config, array $proxy_settings = null,
        string $send_through = '0.0.0.0', array $mux = []
    ): object|array|string
    {
        $st = microtime(true);
        $protocol = $config->protocol;
        $xray = new Xray($this->guzzle, Xui::OUTPUT_ARRAY, Xui::OUTPUT_ARRAY);
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

    /**
     * List all outbounds from xray config.\
     * Similar $xray->get_config('outbounds')
     * @return mixed
     */
    public function list(): mixed
    {
        $xray = new Xray($this->guzzle, $this->output, $this->response_output);
        return $xray->get_config('outbounds');
    }

    /**
     * Get an outbound
     * @param string $outbound_tag
     * @return object|array|string
     */
    public function get(string $outbound_tag): object|array|string
    {
        $st = microtime(true);
        $xray = new Xray($this->guzzle, Xui::OUTPUT_ARRAY, Xui::OUTPUT_ARRAY);
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

    /**
     * Check outbound availability.
     * @param string $outbound_tag
     * @return bool
     */
    public function exist(string $outbound_tag): bool
    {
        $xray = new Xray($this->guzzle, Xui::OUTPUT_ARRAY, Xui::OUTPUT_ARRAY);
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

    /**
     * Update an outbound
     * @param string $outbound_tag
     * @param string|null $tag
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole|null $config
     * @param array|null $proxy_settings
     * @param string|null $send_through
     * @param array|null $mux
     * @return object|array|string
     */
    public function update(
        string $outbound_tag, string $tag = null, Vmess|Vless|Trojan|Shadowsocks|Socks|Http|Freedom|Dns|Blackhole $config = null,
        array  $proxy_settings = null, string $send_through = null, array $mux = null
    ): object|array|string
    {
        $st = microtime(true);
        $xray = new Xray($this->guzzle, Xui::OUTPUT_ARRAY, Xui::OUTPUT_ARRAY);
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

    /**
     * Delete an outbound
     * @param string $outbound_tag
     * @return mixed
     */
    public function delete(string $outbound_tag): mixed
    {
        $st = microtime(true);
        $xray = new Xray($this->guzzle, Xui::OUTPUT_ARRAY, Xui::OUTPUT_ARRAY);
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

    /**
     * Read outbound
     * @param array|string|object $inbound
     * @return Http|Socks|Vless|false|Trojan|Shadowsocks|Vmess
     */
    public static function read(array|string|object $outbound): Http|Socks|Vless|false|Trojan|Shadowsocks|Vmess
    {
        $outbound = json::to_object($outbound);
        if (is_object($outbound)) {
            switch ($outbound->protocol):
                case 'vmess':
                    $settings = $outbound->settings;
                    $stream = $outbound->streamSettings;
                    $config = new Vmess(json::_out($settings), json::_out($stream));
                break;
                case 'vless':
                    $settings = $outbound->settings;
                    $stream = $outbound->streamSettings;
                    $config = new Vless(json::_out($settings), json::_out($stream));
                break;
                case 'trojan':
                    $settings = $outbound->settings;
                    $stream = $outbound->streamSettings;
                    $config = new Trojan(json::_out($settings), json::_out($stream));
                break;
                case 'shadowsocks':
                    $settings = $outbound->settings;
                    $stream = $outbound->streamSettings;
                    $config = new Shadowsocks(json::_out($settings), json::_out($stream));
                break;
                case 'socks':
                    $settings = $outbound->settings;
                    $config = new Socks(json::_out($settings));
                break;
                case 'http':
                    $settings = $outbound->settings;
                    $config = new Http(json::_out($settings));
                break;
            endswitch;
            return $config ?? false;
        } else {
            return false;
        }
    }


    /**
     * Convert outbound to inbound
     * @param Vmess|Vless|Trojan|Shadowsocks|Socks|Http $outbound_config
     * @param string $listen
     * @param int|null $port
     * @return ib_Vmess|ib_Vless|ib_Trojan|ib_Shadowsocks|ib_Socks|ib_Http|false
     */
    public static function to_inbound(
        Vmess|Vless|Trojan|Shadowsocks|Socks|Http $outbound_config, string $listen = '', int|null $port = null
    ): ib_Vmess|ib_Vless|ib_Trojan|ib_Shadowsocks|ib_Socks|ib_Http|false
    {
        $port = $port ?? $outbound_config->settings->port;
        switch ($outbound_config->protocol):
            case 'vmess':
                $settings = $outbound_config->settings;
                $stream = $outbound_config->stream_settings;
                $config = new ib_Vmess($listen, $port);
                $config_settings = new \XUI\Xray\Inbound\Protocols\Vmess\Settings();
                $config_settings->add_client(true, $settings->users[0]['id']);
            break;
            case 'vless':
                $settings = $outbound_config->settings;
                $stream = $outbound_config->stream_settings;
                $config = new ib_Vless($listen, $port);
                $config_settings = new \XUI\Xray\Inbound\Protocols\Vless\Settings();
                $config_settings->add_client(true, $settings->users[0]['id']);
            break;
            case 'trojan':
                $settings = $outbound_config->settings;
                $stream = $outbound_config->stream_settings;
                $config = new ib_Trojan($listen, $port);
                $config_settings = new \XUI\Xray\Inbound\Protocols\Trojan\Settings();
                $config_settings->add_client(true, $settings->users[0]['email'], $settings->users[0]['password']);
            break;
            case 'shadowsocks':
                $settings = $outbound_config->settings;
                $stream = $outbound_config->stream_settings;
                $config = new ib_Shadowsocks($listen, $port);
                $config_settings = new \XUI\Xray\Inbound\Protocols\Shadowsocks\Settings(
                    [], $port, $settings->password, $settings->method, $settings->email
                );
                $config_settings->add_client(true, $settings->method, $settings->password, $settings->email);
            break;
            case 'socks':
                $settings = $outbound_config->settings;
                $config = new ib_Socks($listen, $port);
                $config_settings = new \XUI\Xray\Inbound\Protocols\Socks\Settings();
                $config_settings->add_account($settings->users[0]['username'], $settings->users[0]['password']);
            break;
            case 'http':
                $settings = $outbound_config->settings;
                $config = new ib_Http($listen, $port);
                $config_settings = new \XUI\Xray\Inbound\Protocols\Http\Settings();
                $config_settings->add_account($settings->users[0]['username'], $settings->users[0]['password']);
            break;
        endswitch;
        if (isset($config_settings))
            $config->settings = $config_settings;
        if (isset($stream)):
            $config_stream = new ib_StreamSettings($stream->network, $stream->security);
            switch ($stream->network):
                case 'tcp':
                    $accept_proxy_protocol = $stream->tcp_settings['acceptProxyProtocol'] ?? false;
                    $header_type = $stream->tcp_settings['header']['type'];
                    $header_request = ($header_type == 'http') ? [
                        'version' => $stream->tcp_settings['header']['request']['version'],
                        'method' => $stream->tcp_settings['header']['request']['method'],
                        'path' => $stream->tcp_settings['header']['request']['path'],
                    ] : [];
                    $header_response = ($header_type == 'http') ? [
                        'version' => $stream->tcp_settings['header']['request']['version'],
                        'status' => 200,
                        'reason' => 'OK',
                    ] : [];
                    $config_stream->tcp_settings($accept_proxy_protocol, $header_type, $header_request, $header_response);
                break;
                case 'ws':
                    $accept_proxy_protocol = $stream->tcp_settings['acceptProxyProtocol'] ?? false;
                    $config_stream->ws_settings($accept_proxy_protocol, $stream->ws_settings['path']);
                break;
                case 'kcp':
                    $config_stream->kcp_settings(
                        $stream->kcp_settings['header']['type'],
                        $stream->kcp_settings['seed'],
                        $stream->kcp_settings['mtu'],
                        $stream->kcp_settings['tti'],
                        $stream->kcp_settings['uplinkCapacity'],
                        $stream->kcp_settings['downLinkCapacity'],
                        $stream->kcp_settings['congestion'],
                        $stream->kcp_settings['readBufferSize'],
                        $stream->kcp_settings['writeBufferSize'],
                    );
                break;
                case 'http':
                    $config_stream->http_settings(
                        $stream->http_settings['method'],
                        $stream->http_settings['path'],
                        $stream->http_settings['host'],
                        $stream->http_settings['read_idle_timeout'],
                        $stream->http_settings['health_check_timeout']
                    );
                break;
                case 'domainsocket':
                    $config_stream->ds_settings($stream->ds_settings['path'], $stream->ds_settings['abstract'], $stream->ds_settings['padding']);
                break;
                case 'quic':
                    $config_stream->quic_settings($stream->quic_settings['security'], $stream->quic_settings['key'], $stream->quic_settings['header']['type']);
                break;
                case 'grpc':
                    $config_stream->grpc_settings(
                        $stream->grpc_settings['serviceName'],
                        $stream->grpc_settings['multiMode'],
                        $stream->grpc_settings['idle_timeout'],
                        $stream->grpc_settings['health_check_timeout'],
                        $stream->grpc_settings['permit_without_stream'],
                        $stream->grpc_settings['initial_windows_size']
                    );
                break;
            endswitch;
            switch ($stream->security):
                case 'none':
                    $config_stream->security = 'none';
                break;
            endswitch;
            $config->stream_settings = $config_stream;
        endif;
        return $config ?? false;
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