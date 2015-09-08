<?php

namespace Oro\Bundle\SyncBundle\Wamp;

use Oro\Bundle\SyncBundle\WebSocket\Client\Rfc6455;

class WebSocket
{
    /** @var resource */
    protected $socket = null;

    /** @var Rfc6455 */
    protected $version;

    /**
     * Initialize web socket connection
     *
     * @param string $host Host to connect to. Default is localhost (127.0.0.1).
     * @param int    $port Port to connect to. Default is 8080.
     */
    public function __construct($host = '127.0.0.1', $port = 8080)
    {
        $this->version = new Rfc6455();
        $this->socket = $this->version->connect($host, $port);
    }

    public function __destruct()
    {
        $this->version->disconnect();
    }

    /**
     * Send raw data to a WebSocket server
     *
     * @param  string            $data
     * @return string            Server response
     * @throws \RuntimeException
     */
    public function sendData($data)
    {
        if (!@fwrite($this->socket, $this->version->createFrame($data))) {
            throw new \RuntimeException('WebSocket write error');
        }

        // This is a bit weird since the the mode on stream is non-blocking
        $wsData = fread($this->socket, 2000);

        return trim($wsData, "\x00\xff");
    }
}
