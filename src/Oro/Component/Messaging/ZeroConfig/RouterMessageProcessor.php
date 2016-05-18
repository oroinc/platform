<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Session;

class RouterMessageProcessor implements MessageProcessor
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var RouteRegistryInterface
     */
    protected $routeRegistry;

    /**
     * @param SessionInterface       $session
     * @param RouteRegistryInterface $routeRegistry
     */
    public function __construct(SessionInterface $session, RouteRegistryInterface $routeRegistry)
    {
        $this->session = $session;
        $this->routeRegistry = $routeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Session $session)
    {
        $messageName = $message->getProperty('messageName');
        if (false == $messageName) {
            throw new \LogicException('Got message without "messageName" parameter');
        }

        foreach ($this->routeRegistry->getRoutes($messageName) as $route) {
            $properties = $message->getProperties();
            $properties['processorName'] = $route->getProcessorName();
            $properties['queueName'] = $route->getQueueName();

            $queueMessage = $this->session->createMessage();
            $queueMessage->setProperties($properties);
            $queueMessage->setHeaders($message->getHeaders());
            $queueMessage->setBody($message->getBody());

            $this->session->createQueueProducer()->send($queueMessage);
        }
    }
}
