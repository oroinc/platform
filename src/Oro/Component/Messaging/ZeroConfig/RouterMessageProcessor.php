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
     * @var string
     */
    protected $defaultQueueName;

    /**
     * @param SessionInterface       $session
     * @param RouteRegistryInterface $routeRegistry
     * @param string                 $defaultQueueName
     */
    public function __construct(SessionInterface $session, RouteRegistryInterface $routeRegistry, $defaultQueueName)
    {
        $this->session = $session;
        $this->routeRegistry = $routeRegistry;
        $this->defaultQueueName = $defaultQueueName;
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
            $queueName = $route->getQueueName() ?: $this->defaultQueueName;

            $properties = $message->getProperties();
            $properties['processorName'] = $route->getProcessorName();
            $properties['queueName'] = $queueName;

            $queueMessage = $this->session->createMessage();
            $queueMessage->setProperties($properties);
            $queueMessage->setBody($message->getBody());

            $this->session->createQueueProducer($queueName)->send($queueMessage);
        }
    }
}
