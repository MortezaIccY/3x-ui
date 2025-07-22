<?php

namespace XUI\Xray\Reverse;

use GuzzleHttp\Client;
use JSON\json;
use XUI\Xray\Xray;
use XUI\Xui;

class Reverse
{
    private Client $guzzle;
    private Xray $xray;
    public int $output;
    public int $response_output;
    public array $bridges = [];
    public array $portals = [];

    public function __construct(Client $guzzle, int $output = Xui::OUTPUT_OBJECT, int $response_output = Xui::OUTPUT_OBJECT)
    {
        $this->guzzle = $guzzle;
        $this->output = $output;
        $this->response_output = $response_output;
    }

    /**
     * Load reverse configurations from xray config
     * <h4>Must be called before using reverse!</h4>
     * @return void
     */

    public function load(): bool
    {
        $this->xray = new Xray($this->guzzle, Xui::OUTPUT_ARRAY, Xui::OUTPUT_ARRAY);
        $result = $this->xray->get_configs()['response'];
        if (isset($result['reverse'])) {
            $reverse = $result['reverse'];
            if (isset($reverse['bridges'])) $this->bridges = $reverse['bridges'];
            if (isset($reverse['portals'])) $this->portals = $reverse['portals'];
            return true;
        } else {
            return false;
        }
    }

    /**
     *  Update reverse configuration based on current configs.
     * @return object|string|array
     */
    public function update(): object|string|array
    {
        $st = microtime(true);
        $reverse = [];
        if (!empty($this->portals)) $reverse['portals'] = $this->portals;
        if (!empty($this->bridges)) $reverse['bridges'] = $this->bridges;
        $result = $this->xray->update_config([
            'reverse' => $reverse
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

    /**
     * Add bridge to reverse
     * @param string $tag
     * @param string $domain
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|object|array|string
     */
    public function add_bridge(string $tag, string $domain = 'reverse.xui', bool $apply = true): true|object|array|string
    {
        $this->bridges[] = [
            'tag' => $tag,
            'domain' => $domain
        ];
        if ($apply)
            return $this->update();
        else
            return true;
    }

    /**
     * Get a bridge from reverse
     * @param string $bridge_tag
     * @return object|array|string
     */
    public function get_bridge(string $bridge_tag): object|array|string
    {
        $st = microtime(true);
        $return = ['ok' => false, 'error_code' => 404, 'error' => 'reverse bridge not found'];
        foreach ($this->bridges as $bridge):
            if ($bridge_tag == $bridge['tag']):
                $et = microtime(true);
                $tt = round($et - $st, 3);
                $return = ['ok' => true, 'response' => $this->response_output($bridge), 'size' => null, 'time_taken' => $tt];
                break;
            endif;
        endforeach;
        return $this->output($return);
    }

    /**
     * Update a bridge from reverse
     * @param string $bridge_tag
     * @param string|null $tag
     * @param string|null $domain
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|object|array|string
     */
    public function update_bridge(string $bridge_tag, string $tag = null, string $domain = null, bool $apply = true): true|object|array|string
    {
        $return = $this->output(['ok' => false, 'error_code' => 404, 'error' => 'reverse bridge not found']);
        foreach ($this->bridges as $key => $bridge):
            if ($bridge_tag == $bridge['tag']):
                if (!is_null($tag)) $bridge['tag'] = $tag;
                if (!is_null($tag)) $bridge['domain'] = $domain;
                $this->bridges[$key] = $bridge;
                if ($apply)
                    $return = $this->update();
                else
                    $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Delete a bridge from reverse
     * @param string $bridge_tag
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|object|array|string
     */
    public function delete_bridge(string $bridge_tag, bool $apply = true): true|object|array|string
    {
        $deleted = false;
        foreach ($this->bridges as $key => $bridge):
            if ($bridge_tag == $bridge['tag']):
                unset($this->bridges[$key]);
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
     * Check the bridge availability
     * @param string $bridge_tag
     * @return bool
     */
    public function has_bridge(string $bridge_tag): bool
    {
        $return = false;
        foreach ($this->bridges as $key => $bridge):
            if ($bridge_tag == $bridge['tag']):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Add portal to reverse
     * @param string $tag
     * @param string $domain
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|object|array|string
     */
    public function add_portal(string $tag, string $domain = 'reverse.xui', bool $apply = true): true|object|array|string
    {
        $this->portals[] = [
            'tag' => $tag,
            'domain' => $domain
        ];
        if ($apply)
            return $this->update();
        else
            return true;
    }

    /**
     * Get a portal from reverse
     * @param string $portal_tag
     * @return object|array|string
     */
    public function get_portal(string $portal_tag): object|array|string
    {
        $st = microtime(true);
        $return = ['ok' => false, 'error_code' => 404, 'error' => 'reverse portal not found'];
        foreach ($this->portals as $portal):
            if ($portal_tag == $portal['tag']):
                $et = microtime(true);
                $tt = round($et - $st, 3);
                $return = ['ok' => true, 'response' => $this->response_output($portal), 'size' => null, 'time_taken' => $tt];
                break;
            endif;
        endforeach;
        return $this->output($return);
    }

    /**
     * Update a portal from reverse
     * @param string $portal_tag
     * @param string|null $tag
     * @param string|null $domain
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|object|array|string
     */
    public function update_portal(string $portal_tag, string $tag = null, string $domain = null, bool $apply = true): true|object|array|string
    {
        $return = $this->output(['ok' => false, 'error_code' => 404, 'error' => 'reverse portal not found']);
        foreach ($this->portals as $key => $portal):
            if ($portal_tag == $portal['tag']):
                if (!is_null($tag)) $portal['tag'] = $tag;
                if (!is_null($tag)) $portal['domain'] = $domain;
                $this->portals[$key] = $portal;
                if ($apply)
                    $return = $this->update();
                else
                    $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }

    /**
     * Delete a portal from reverse
     * @param string $portal_tag
     * @param bool $apply Apply changes to reverse in xray config
     * @return true|object|array|string
     */
    public function delete_portal(string $portal_tag, bool $apply = true): true|object|array|string
    {
        $deleted = false;
        foreach ($this->portals as $key => $portal):
            if ($portal_tag == $portal['tag']):
                unset($this->portals[$key]);
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
     * Check the portal availability on reverse
     * @param string $portal_tag
     * @return bool
     */
    public function has_portal(string $portal_tag): bool
    {
        $return = false;
        foreach ($this->portals as $key => $portal):
            if ($portal_tag == $portal['tag']):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
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