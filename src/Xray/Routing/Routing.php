<?php

namespace XUI\Xray\Routing;

use GuzzleHttp\Client;
use JSON\json;
use XUI\Panel\Panel;
use XUI\Xray\Xray;
use XUI\Xui;

/**
 * @method string|bool|null  domain_strategy(string|null $value = null)   Get/Set the domain strategy.<br/>Don't set $value to get value of domain strategy.
 * @method string|bool|null  domain_matcher(string|null $value = null)    Get/Set the domain matcher.<br/>Don't set $value to get value of domain matcher.
 * @method array|bool|null   balancers(array|null $value = null)          Get/Set the balancers list.<br/>Don't set $value to get value of balancers.
 * @method array|bool|null   rules(array|null $value = null)          Get/Set the rules.<br/>Don't set $value to get value of rules.
 */
class Routing
{
    private Client $guzzle;
    private Xray $xray;
    public int $output;
    public int $response_output;
    private string $domain_strategy;
    private string $domain_matcher;
    private array $rules;
    private array $balancers;

    public function __construct(Client $guzzle, int $output = Xui::OUTPUT_OBJECT, int $response_output = Xui::OUTPUT_OBJECT)
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
        $this->xray = new Xray($this->guzzle, Xui::OUTPUT_ARRAY, Xui::OUTPUT_ARRAY);
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

    /**
     *  Update routing configuration based on current configs.
     * @return object|string|array
     */
    public function update(): object|string|array
    {
        $st = microtime(true);
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

    public function __call($name, $args)
    {
        return empty($args) ? ($this->$name ?? null) : !!($this->$name = $args[0]);
    }

    /**
     * Add rule to routing
     * @param Rule $rule
     * @param bool $apply Apply changes to routing in xray config
     * @return true|object|array|string
     */
    public function add_rule(Rule $rule, bool $apply = true): true|object|array|string
    {
        $this->rules[] = $rule->rule();
        if ($apply) {
            return $this->update();
        } else {
            return true;
        }
    }

    /**
     * Get a rule from routing
     * @param string|array $rule_inbound_tag
     * @param string $rule_outbound_tag
     * @return object|array|string
     */
    public function get_rule(string|array $rule_inbound_tag, string $rule_outbound_tag): object|array|string
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

    /**
     * Update a rule of routing
     * @param string|array $rule_inbound_tag
     * @param string $rule_outbound_tag
     * @param Rule $rule
     * @param bool $apply Apply changes to routing in xray config
     * @return true|object|array|string
     */
    public function update_rule(string|array $rule_inbound_tag, string $rule_outbound_tag, Rule $rule, bool $apply = true): true|object|array|string
    {
        $rule_inbound_tag = (is_string($rule_inbound_tag)) ? [$rule_inbound_tag] : $rule_inbound_tag;
        $return = $this->output(['ok' => false, 'error_code' => 404, 'error' => 'routing rule not found']);
        foreach ($this->rules as $key => $a_rule):
            $is_same = true;
            foreach ($rule_inbound_tag as $a_inbound_tag):
                if (isset($a_rule['inboundTag']) && !in_array($a_inbound_tag, $a_rule['inboundTag'])) $is_same = false;
            endforeach;
            if ($rule_outbound_tag == $a_rule['outboundTag'] && $is_same):
                $this->rules[$key] = $rule->rule();
                if ($apply) {
                    $return = $this->update();
                } else {
                    $return = true;
                }
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Delete a rule from routing
     * @param string|array $rule_inbound_tag
     * @param string $rule_outbound_tag
     * @param bool $apply Apply changes to routing in xray config
     * @return true|object|array|string
     */
    public function delete_rule(string|array $rule_inbound_tag, string $rule_outbound_tag, bool $apply = true): true|object|array|string
    {
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
            if ($apply)
                $return = $this->update();
            else
                $return = true;
        } else {
            $return = $this->output(['ok' => false, 'error_code' => 404, 'error' => 'routing not found']);
        }
        return $return;
    }

    /**
     * Check a rule availability on routing
     * @param string|array $rule_inbound_tag
     * @param string $rule_outbound_tag
     * @return bool
     */
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

    /**
     * Configure a rule
     * @param array|string $inbound_tag
     * @param string $outbound_tag
     * @return Rule
     */
    public static function rule(array|string $inbound_tag, string $outbound_tag): Rule
    {
        return new Rule($inbound_tag, $outbound_tag);
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