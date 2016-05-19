<?php
namespace Oro\Component\Messaging\Router;

use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Session as TransportSession;

class RouterMessageProcessor implements MessageProcessor
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, TransportSession $session)
    {
        $producer = $session->createProducer();
        foreach ($this->router->route($message) as $recipient) {
            $producer->send($recipient->getDestination(), $recipient->getMessage());
        }

        return self::ACK;
    }
}
