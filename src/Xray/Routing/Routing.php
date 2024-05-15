<?php

namespace XUI\Xray\Routing;

use GuzzleHttp\Client;
use JSON\json;
use XUI\Xray\Xray;

class Routing
{
    private Client $guzzle;
    private Xray $xray;
    public int $output;
    public int $response_output;
    public string $domain_strategy;
    public string $domain_matcher;
    public array $rules;
    public array $reverses;
    public array $balancers;
    public const OUTPUT_JSON = 111;
    public const OUTPUT_OBJECT = 112;
    public const OUTPUT_ARRAY = 113;

    public function __construct(Client $guzzle, int $output = self::OUTPUT_OBJECT, int $response_output = self::OUTPUT_OBJECT)
    {
        $this->guzzle = $guzzle;
        $this->output = $output;
        $this->response_output = $response_output;
    }

    /**
     * Load routing configurations from xray config
     * <h4>Must be called before using routing!</h4>
     * @return void
     */
    public function load(): bool
    {
        $this->xray = new Xray($this->guzzle, Xray::OUTPUT_ARRAY, Xray::OUTPUT_ARRAY);
        $result = $this->xray->get_config('routing');
        if ($result['ok'] && !empty($result['response'])) {
            $routing = $result['response'];
            if (isset($routing['domainStrategy'])) $this->domain_strategy = $routing['domainStrategy'];
            if (isset($routing['domainMatcher'])) $this->domain_matcher = $routing['domainMatcher'];
            if (isset($routing['rules'])) $this->rules = $routing['rules'];
            if (isset($routing['balancers'])) $this->balancers = $routing['balancers'];
            return true;
        } else {
            return false;
        }
    }

    public function add_rule(
        array|string $inbound_tag, string $outbound_tag, string $balancer_tag = null, array|string $user = null, string $network = null,
        array|string $protocol = null, string $domain_matcher = null, array|string $domain = null, array|string $ip = null, array|string $port = null,
        array|string $source = null, array|string $source_port = null, string $attrs = null, string $type = 'field'
    )
    {
        $st = microtime(true);
        $inbound_tag = (is_string($inbound_tag)) ? [$inbound_tag] : $inbound_tag;
        $rule = [
            'type' => $type,
            'inboundTag' => $inbound_tag,
            'outboundTag' => $outbound_tag
        ];
        if (!is_null($balancer_tag)) $rule['balancerTag'] = $balancer_tag;
        if (!is_null($user)) $rule['user'] = is_string($user) ? [$user] : $user;
        if (!is_null($network)) $rule['network'] = $network;
        if (!is_null($protocol)) $rule['protocol'] = is_string($protocol) ? [$protocol] : $protocol;
        if (!is_null($domain_matcher)) $rule['domainMatcher'] = $domain_matcher;
        if (!is_null($domain)) $rule['domain'] = is_string($domain) ? [$domain] : $domain;
        if (!is_null($ip)) $rule['ip'] = is_string($ip) ? [$ip] : $ip;
        if (!is_null($port)) $rule['port'] = is_array($port) ? implode(',', $port) : $port;
        if (!is_null($source)) $rule['source'] = is_string($source) ? [$source] : $source;
        if (!is_null($source_port)) $rule['sourcePort'] = is_array($source_port) ? implode(',', $source_port) : $source_port;
        if (!is_null($attrs)) $rule['attrs'] = $attrs;
        $this->rules[] = $rule;
        $routing = [
            'domainStrategy' => $this->domain_strategy,
            'rules' => $this->rules
        ];
        if (isset($this->domain_matcher)) $routing['domainMatcher'] = $this->domain_matcher;
        if (isset($this->balancers)) $routing['balancers'] = $this->balancers;
        $result = $this->xray->update_config([
            'routing' => $routing
        ]);
        if ($result['ok']) {
            $response = $result['response'];
            $et = microtime(true);
            $tt = round($et - $st, 3);
            $return = ['ok' => true, 'response' => $this->response_output($response), 'size' => $result['size'], 'time_taken' => $tt];
        } else {
            $return = $result;
        }
        return $this->output($return);
    }

    public function get_rule(string|array $rule_inbound_tag, string $rule_outbound_tag)
    {
        $st = microtime(true);
        $rule_inbound_tag = (is_string($rule_inbound_tag)) ? [$rule_inbound_tag] : $rule_inbound_tag;
        $return = ['ok' => false, 'error_code' => 404, 'error' => 'routing rule not found'];
        foreach ($this->rules as $rule):
            $is_same = true;
            foreach ($rule_inbound_tag as $a_inbound_tag):
                if (isset($rule['inboundTag']) && !in_array($a_inbound_tag, $rule['inboundTag'])) $is_same = false;
            endforeach;
            if ($rule_outbound_tag == $rule['outboundTag'] && $is_same):
                $et = microtime(true);
                $tt = round($et - $st, 3);
                $return = ['ok' => true, 'response' => $this->response_output($rule), 'size' => null, 'time_taken' => $tt];
                break;
            endif;
        endforeach;
        return $this->output($return);
    }

    public function update_rule(
        string|array $rule_inbound_tag, string $rule_outbound_tag,
        array|string $inbound_tag = null, string $outbound_tag = null, string $balancer_tag = null, array|string $user = null, string $network = null,
        array|string $protocol = null, string $domain_matcher = null, array|string $domain = null, array|string $ip = null, array|string $port = null,
        array|string $source = null, array|string $source_port = null, string $attrs = null, string $type = 'field'
    ): mixed
    {
        $st = microtime(true);
        $rule_inbound_tag = (is_string($rule_inbound_tag)) ? [$rule_inbound_tag] : $rule_inbound_tag;
        $return = ['ok' => false, 'error_code' => 404, 'error' => 'routing rule not found'];
        foreach ($this->rules as $key => $rule):
            $is_same = true;
            foreach ($rule_inbound_tag as $a_inbound_tag):
                if (isset($rule['inboundTag']) && !in_array($a_inbound_tag, $rule['inboundTag'])) $is_same = false;
            endforeach;
            if ($rule_outbound_tag == $rule['outboundTag'] && $is_same):
                if (!is_null($inbound_tag)) $rule['inboundTag'] = (is_string($inbound_tag)) ? [$inbound_tag] : $inbound_tag;
                if (!is_null($outbound_tag)) $rule['outboundTag'] = $outbound_tag;
                if (!is_null($balancer_tag)) $rule['balancerTag'] = $balancer_tag;
                if (!is_null($user)) $rule['user'] = is_string($user) ? [$user] : $user;
                if (!is_null($network)) $rule['network'] = $network;
                if (!is_null($protocol)) $rule['protocol'] = is_string($protocol) ? [$protocol] : $protocol;
                if (!is_null($domain_matcher)) $rule['domainMatcher'] = $domain_matcher;
                if (!is_null($domain)) $rule['domain'] = is_string($domain) ? [$domain] : $domain;
                if (!is_null($ip)) $rule['ip'] = is_string($ip) ? [$ip] : $ip;
                if (!is_null($port)) $rule['port'] = is_array($port) ? implode(',', $port) : $port;
                if (!is_null($source)) $rule['source'] = is_string($source) ? [$source] : $source;
                if (!is_null($source_port)) $rule['sourcePort'] = is_array($source_port) ? implode(',', $source_port) : $source_port;
                if (!is_null($attrs)) $rule['attrs'] = $attrs;
                $this->rules[$key] = $rule;
                $routing = [
                    'domainStrategy' => $this->domain_strategy,
                    'rules' => $this->rules
                ];
                if (isset($this->domain_matcher)) $routing['domainMatcher'] = $this->domain_matcher;
                if (isset($this->balancers)) $routing['balancers'] = $this->balancers;
                $result = $this->xray->update_config([
                    'routing' => $routing
                ]);
                if ($result['ok']) {
                    $response = $result['response'];
                    $et = microtime(true);
                    $tt = round($et - $st, 3);
                    $return = ['ok' => true, 'response' => $this->response_output($response), 'size' => $result['size'], 'time_taken' => $tt];
                } else {
                    $return = $result;
                }
                break;
            endif;
        endforeach;
        return $this->output($return);
    }

    public function delete_rule(string|array $rule_inbound_tag, string $rule_outbound_tag): mixed
    {
        $st = microtime(true);
        $rule_inbound_tag = (is_string($rule_inbound_tag)) ? [$rule_inbound_tag] : $rule_inbound_tag;
        $deleted = false;
        foreach ($this->rules as $key => $rule):
            $is_same = true;
            foreach ($rule_inbound_tag as $a_inbound_tag):
                if (isset($rule['inboundTag']) && !in_array($a_inbound_tag, $rule['inboundTag'])) $is_same = false;
            endforeach;
            if ($rule_outbound_tag == $rule['outboundTag'] && $is_same):
                unset($this->rules[$key]);
                $deleted = true;
                break;
            endif;
        endforeach;
        if ($deleted) {
            $routing = [
                'domainStrategy' => $this->domain_strategy,
                'rules' => $this->rules
            ];
            if (isset($this->domain_matcher)) $routing['domainMatcher'] = $this->domain_matcher;
            if (isset($this->balancers)) $routing['balancers'] = $this->balancers;
            $result = $this->xray->update_config([
                'routing' => $routing
            ]);
            if ($result['ok']) {
                $response = $result['response'];
                $et = microtime(true);
                $tt = round($et - $st, 3);
                $return = ['ok' => true, 'response' => $this->response_output($response), 'size' => $result['size'], 'time_taken' => $tt];
            } else {
                $return = $result;
            }
        } else {
            $return = ['ok' => false, 'error_code' => 404, 'error' => 'routing not found'];
        }
        return $this->output($return);
    }

    public function has_rule(string|array $rule_inbound_tag, string $rule_outbound_tag): bool
    {
        $rule_inbound_tag = (is_string($rule_inbound_tag)) ? [$rule_inbound_tag] : $rule_inbound_tag;
        $return = false;
        foreach ($this->rules as $rule):
            $is_same = true;
            foreach ($rule_inbound_tag as $a_inbound_tag):
                if (isset($rule['inboundTag']) && !in_array($a_inbound_tag, $rule['inboundTag'])) $is_same = false;
            endforeach;
            if ($rule_outbound_tag == $rule['outboundTag'] && $is_same):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
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