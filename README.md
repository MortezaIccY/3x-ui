# An easy to use php library for [3x-ui](https://github.com/es-taheri/3x-ui#an-easy-to-use-php-library-for-mhsanaei3x-ui)

<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="./media/php_3x-ui.png">
    <img alt="3x-ui" src="./media/php_3x-ui.png">
  </picture>
</p>

[![Static Badge](https://img.shields.io/badge/php-v8.2-blue)](https://www.php.net/releases/8.2/en.php)
[![Static Badge](https://img.shields.io/badge/3x--ui-v2.4.8-%2305564F)](https://github.com/MHSanaei/3x-ui/releases/tag/v2.4.8)
[![Static Badge](https://img.shields.io/badge/Xray-v24.11.21-darkred)](https://github.com/XTLS/Xray-core/releases/tag/v24.11.21)
[![Total Downloads](https://img.shields.io/packagist/dt/estaheri/3x-ui.svg)](https://github.com/es-taheri/3x-ui/releases/latest)
[![Static Badge](https://img.shields.io/badge/License-MIT-%23007EC6)](https://github.com/es-taheri/3x-ui/blob/master/LICENSE)

### PHP 3X-UI

**Simple open-source PHP library for managing MHSanaei 3x-ui panel without its official rest api.**

> [!IMPORTANT]\
> This library is only for personal using, please do not use it for illegal purposes, please do not use it in a
> production environment.

## Quick Start

* Require library in your project 📁

```
composer require estaheri/3x-ui
```

* Require composer autoload in your php code ⚓

```php
require_once __DIR__.'/vendor/autoload.php';
```

* Instantiating `Xui` class and set connection credentials 📡

```php
$xui = new Xui($xui_host, $xui_port, $xui_path, $xui_ssl);
```

* Login to panel using username & password 🔐

```php
$xui->login($username, $password);
```

* Now you can do anything ✅

```php
$xui->server; // Accessing to server methods (Status,Database,Stop/Restart Xray,...)
$xui->xray; // Accessing to xray methods (Inbounds,Outbounds,Routing,...)
$xui->panel; // Accessing to panel methods (Restart panel,Update/Get settings,...)
```

## Full Documentation

[New Xui](#new-xui)

* [Login](#login)
* [Random](#random)
* [Uuid](#uuid)

[Xray](#xray)

* [Inbound](#inbound)
* [Outbound](#outbound)
* [Routing](#routing)
* [Reverse](#reverse)
* [Configs](#configs)
* [Restart](#restart)

[Server](#server)

* [Status](#status)
* [Database](#database)
* [Xray-Start-Stop](#xray-start-stop)
* [Xui-log](#xui-log)

[Panel](#panel)

* [Settings](#settings)
* [Restart](#restart)
* [Default-Xray-Config](#default-xray-config)

### New Xui

- #### Login
- #### Random
- #### Uuid

### Xray

- #### Inbound
- #### Outbound
- #### Routing
- #### Reverse
- #### Configs
- #### Restart

### Server

- #### Status
- #### Database
- #### Xray-Start-Stop
- #### Xui-log

### Panel

- #### Settings
- #### Restart
- #### Default-Xray-Config

## Special Thanks to

- [MHSanaei](https://github.com/MHSanaei)
- [alireza0](https://github.com/alireza0/)

## Support project

### Give Star ⭐

**If this library is helpful to you, you may wish to give it a STAR**

### Donate 💵

**Help me improve this library by a donate** ❤️

- TRX : `TXFE1je6Ed7fADvxAQXXo2g45eQtXvwith`
- TON : `UQDb44qyae9n0hmgay3Bs_oom6RR8cZbLF5_9UCei0q13T0b`
- USDT (TRON): `TCTyFGJVkCgruAYmvPpetF6jVybuZSpTg6`
- USDT (TRX): `UQBnnLMdbAH6Pq86lsH9jEySH-D5___ctqUFKiuBXnd74FTD`