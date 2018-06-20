Client
======

Table of content
----------------
- [Overview](#overview)
- [Publish messages](#publish)
- [Connection checker](#checker)

Overview
--------
OroSyncBundle provides a websocket client - `oro_sync.websocket_client`, which in its turn is based on
Gos WebSocketClient component - `Gos\Component\WebSocketClient\Wamp\Client`.

Websocket client makes use of Sync authentication tickets mechanism, so you should not worry about authentication on
backend side. Websocket client `oro_sync.websocket_client` uses anonymous Sync authentication tickets, so when you
connect to websocket server, it treats you as an anonymous.

Publish messages
----------------
You can publish messages to channels using `publish()` method of websocket client, e.g.

```php
    $websocketClient = $this->get('oro_sync.websocket_client');
    $websocketClient->publish('oro/custom-channel', ['foo' => 'bar']);
```

Checker
-------
It is strongly recommended to use connection checker `oro_sync.client.connection_checker` before trying to connect or
publish to websocket server, e.g.:

```php
    $websocketConnectionChecker = $this->get('oro_sync.client.connection_checker');
    if ($websocketConnectionChecker->checkConnection()) {
        $websocketClient = $this->get('oro_sync.websocket_client');
        $websocketClient->publish('oro/custom-channel', ['foo' => 'bar']);
    }
```
