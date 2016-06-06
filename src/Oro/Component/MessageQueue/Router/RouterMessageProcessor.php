<?php
namespace Oro\Component\MessageQueue\Router;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface as TransportSession;

class RouterMessageProcessor implements MessageProcessorInterface
{
    /**
     * @var object
     */
    private $router;

    /**
     * @param object $router
     */
    public function __construct($router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, TransportSession $session)
    {
        if ($this->router instanceof RecipientListRouterInterface) {
            $this->routeOneToMany($this->router, $message, $session);
        } else {
            throw new \LogicException(sprintf('Unsupported router given %s', get_class($this->router)));
        }

        return self::ACK;
    }

    /**
     * @param RecipientListRouterInterface $router
     * @param MessageInterface $message
     * @param TransportSession $session
     */
    protected function routeOneToMany(
        RecipientListRouterInterface $router,
        MessageInterface $message,
        TransportSession $session
    ) {
        $producer = $session->createProducer();
        foreach ($router->route($message) as $recipient) {
            $producer->send($recipient->getDestination(), $recipient->getMessage());
        }
    }
}
