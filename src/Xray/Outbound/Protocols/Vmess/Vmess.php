<?php

namespace XUI\Xray\Outbound\Protocols\Vmess;

use JSON\json;
use XUI\Xray\Outbound\Protocols\StreamSettings;

class Vmess
{
    public string $protocol = 'vmess';
    public Settings $settings;
    public StreamSettings $stream_settings;
    public const NETWORK_TCP = 'tcp';
    public const NETWORK_KCP = 'kcp';
    public const NETWORK_WS = 'ws';
    public const NETWORK_HTTP = 'http';
    public const NETWORK_QUICK = 'quic';
    public const NETWORK_GRPC = 'grpc';
    public const SECURITY_NONE = 'none';
    public const SECURITY_TLS = 'tls';

    public function __construct(string $settings = null, string $stream_settings = null)
    {
        if (!is_null($settings)):
            $settings = json::_in($settings, true);
            $this->settings = new Settings($settings['vnext'][0]['address'], $settings['vnext'][0]['port'], $settings['vnext'][0]['users']);
        endif;
        if (!is_null($stream_settings)):
            $stream_settings = json::_in($stream_settings, true);
            $this->stream_settings = new StreamSettings($stream_settings['network'], $stream_settings['security']);
            if (isset($stream_settings['tlsSettings'])) $this->stream_settings->tls_settings = $stream_settings['tlsSettings'];
            if (isset($stream_settings['tcpSettings'])) $this->stream_settings->tcp_settings = $stream_settings['tcpSettings'];
            if (isset($stream_settings['kcpSettings'])) $this->stream_settings->kcp_settings = $stream_settings['kcpSettings'];
            if (isset($stream_settings['wsSettings'])) $this->stream_settings->ws_settings = $stream_settings['wsSettings'];
            if (isset($stream_settings['httpSettings'])) $this->stream_settings->http_settings = $stream_settings['httpSettings'];
            if (isset($stream_settings['quicSettings'])) $this->stream_settings->quic_settings = $stream_settings['quicSettings'];
            if (isset($stream_settings['dsSettings'])) $this->stream_settings->ds_settings = $stream_settings['dsSettings'];
            if (isset($stream_settings['grpcSettings'])) $this->stream_settings->grpc_settings = $stream_settings['grpcSettings'];
            if (isset($stream_settings['sockopt'])) $this->stream_settings->sockopt = $stream_settings['sockopt'];
        endif;
    }

    public function generate(
        Settings $settings, StreamSettings $stream_settings
    ): void
    {
        $this->settings = $settings;
        $this->stream_settings = $stream_settings;
    }

    public function modify(
        Settings $settings = null, StreamSettings $stream_settings = null
    ): void
    {
        if (!is_null($settings)) $this->settings = $settings;
        if (!is_null($stream_settings)) $this->stream_settings = $stream_settings;
    }
}