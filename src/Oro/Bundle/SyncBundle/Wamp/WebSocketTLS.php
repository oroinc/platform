<?php

namespace Oro\Bundle\SyncBundle\Wamp;

use Oro\Bundle\SyncBundle\WebSocket\Client\Rfc6455;

class WebSocketTLS extends WebSocket
{
    /**
     * Initialize web socket connection
     *
     * @param string $host Host to connect to. Default is localhost (127.0.0.1).
     * @param int    $port Port to connect to. Default is 8080.
     * @param string $path Request path. Default is ""
     * @param string $transport Any registered socket transport returned by
     *  http://php.net/manual/en/function.stream-get-transports.php
     * @param array $contextOptions, Will be passed to a context create function
     *  http://php.net/manual/en/function.stream-context-create.php
     */
    public function __construct($host, $port, $path, string $transport, array $contextOptions)
    {
        $this->version = new Rfc6455();
        $this->version
            ->setTransport($transport)
            ->setContextOptions($contextOptions);

        $this->socket = $this->version->connect($host, $port, $path);
    }
}
