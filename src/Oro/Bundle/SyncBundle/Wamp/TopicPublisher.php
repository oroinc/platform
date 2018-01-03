<?php

namespace Oro\Bundle\SyncBundle\Wamp;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProvider;
use Oro\Bundle\SyncBundle\Exception\WebSocket\Rfc6455Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ratchet\Wamp\ServerProtocol;

class TopicPublisher implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Web socket server host
     *
     * @var string
     */
    protected $host;

    /**
     * Web socket server port
     *
     * @var int
     */
    protected $port;

    /**
     * Web socket url path
     *
     * @var string
     */
    protected $path = '';

    /**
     * @var WebSocket
     */
    protected $ws = null;

    /** @var TicketProvider */
    private $ticketProvider;

    /**
     *
     * @param string $host Host to connect to. Default is localhost (127.0.0.1).
     * @param int $port Port to connect to. Default is 8080.
     * @param string $path Request path. Default is ""
     */
    public function __construct($host = '127.0.0.1', $port = 8080, $path = '')
    {
        if ('*' === $host) {
            $host = '127.0.0.1';
        }

        $this->host = $host;
        $this->port = (int)$port;
        $this->path = $path;

        $this->setLogger(new NullLogger());
    }

    /**
     * Sets the TicketProvider instance.
     *
     * @param TicketProvider $ticketProvider
     */
    public function setTicketProvider(TicketProvider $ticketProvider)
    {
        $this->ticketProvider = $ticketProvider;
    }

    /**
     * Publish (broadcast) message
     *
     * @param  string $topic Topic id (or channel), for example "acme/demo-channel"
     * @param  string|array $msg Message
     * @return bool         True on success, false otherwise
     */
    public function send($topic, $msg)
    {
        $ws = $this->getWs();

        if (!$ws) {
            return false;
        }

        $ws->sendData(
            json_encode(
                [
                    ServerProtocol::MSG_PUBLISH,
                    $topic,
                    $msg,
                ]
            )
        );

        return true;
    }

    /**
     * Check if WebSocket server is running
     *
     * @return bool True on success, false otherwise
     */
    public function check()
    {
        $ws = $this->getWs();

        return !is_null($ws) && $ws !== false;
    }

    /**
     * @return null|WebSocket
     * @throws \Exception
     */
    protected function getWs()
    {
        if (null === $this->ws) {
            try {
                // add the ticket parameter to the URL
                $this->path = sprintf(
                    '%s?ticket=%s',
                    $this->path,
                    urlencode($this->ticketProvider->generateTicket(true))
                );
                $this->ws = new WebSocket($this->host, $this->port, $this->path);
            } catch (Rfc6455Exception $e) {
                $this->logger->warning(
                    'Websocket backend exception: {message}',
                    ['exception' => $e, 'message' => $e->getMessage()]
                );
                $this->ws = false;
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $this->ws;
    }
}
