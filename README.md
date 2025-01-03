# An easy to use php library for MHSanaei/3x-ui
### Complitale with `v2.4.8` 3x-ui & `v24.11.21` Xray based on latest update
### There is no readme doc for this library but all methods has phpdoc!
### Readme documention will come soon...
## Usage Example:
```php
use XUI\Xray\Inbound\Protocols\Vmess\Vmess;
use XUI\Xui;

require_once __DIR__.'/vendor/autoload.php';
$xui = new Xui($xui_host, $xui_port, $xui_path, $xui_ssl);
$result = $xui->login($username, $password);
@$response = $result->repsonse;
if ($result->ok && $response->success) {
    $xui_inbound = $xui->xray->inbound;
    $config = new Vmess();
    $config->settings->add_client();
    $config->stream_settings->ws_settings(false, '/3x-ui');
    $result = $xui_inbound->add($config, 'Test 3x-ui', 100 * Xui::UNIT_GIGABYTE, 86400);
    @$response = $result->repsonse;
    if ($result->ok && $response->success) {
        $inbound_id = $response->obj->id;
        var_dump("Inbound added : #$inbound_id");
    } else {
        $error = $result->error ?? $response->msg;
        var_dump("Add inbound failed! (Error: $error)");
    }
} else {
    $error = $result->error ?? $response->msg;
    var_dump("Login failed! (Error: $error)");
}
```
