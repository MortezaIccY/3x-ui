<?php

namespace XUI\Xray\Inbound\Protocols\Http;

use JSON\json;
use stdClass;

class Settings

{
    public array $accounts;
    public int $timeout;

    public function __construct(array $accounts = [], int $timeout = 30)
    {
        $this->accounts = $accounts;
        $this->timeout = $timeout;
    }

    public function add_account(string $username = null, string $password = null): string
    {
        $username = (is_null($username)) ? self::random(6) : $username;
        $password = (is_null($password)) ? self::random(12) : $password;
        $this->accounts[] = [
            'username' => $username,
            'password' => $password,
        ];
        return $username;
    }

    public function update_account(string $account_username, string $username = null, string $password = null): bool
    {
        $updated = false;
        foreach ($this->accounts as $key => $account):
            if ($account['username'] == $account_username):
                if (!is_null($username)) $account['username'] = $username;
                if (!is_null($password)) $account['password'] = $password;
                $this->accounts[$key] = $account;
                $updated = true;
                break;
            endif;
        endforeach;
        return $updated;
    }

    public function get_account(string $account_username): array|false
    {
        $return = false;
        foreach ($this->accounts as $key => $account):
            if ($account['username'] == $account_username):
                $return = $this->accounts[$key];
                break;
            endif;
        endforeach;
        return $return;
    }

    public function has_account(string $account_username): bool
    {
        $return = false;
        foreach ($this->accounts as $account):
            if ($account['username'] == $account_username):
                $return = true;
                break;
            endif;
        endforeach;
        return $return;
    }

    public function remove_account(string $account_username): bool
    {
        $removed = false;
        foreach ($this->accounts as $key => $account):
            if ($account['username'] == $account_username):
                unset($this->accounts[$key]);
                $removed = true;
                break;
            endif;
        endforeach;
        $this->accounts = array_values($this->accounts);
        return $removed;
    }

    /**
     * Returns fully structured settings for xray json config
     * @return array
     */
    public function settings(): array
    {
        return [
            'timeout' => $this->timeout,
            'accounts' => $this->accounts,
        ];
    }

    public static function random(int $length = 32): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 1; $i <= $length; $i++) :
            $out .= $chars[rand(0, strlen($chars) - 1)];
        endfor;
        return $out;
    }

}