<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Session as TransportSession;

class RouterMessageProcessor implements MessageProcessor
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var RouteRegistryInterface
     */
    protected $routeRegistry;

    /**
     * @param Session                $session
     * @param RouteRegistryInterface $routeRegistry
     */
    public function __construct(Session $session, RouteRegistryInterface $routeRegistry)
    {
        $this->session = $session;
        $this->routeRegistry = $routeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, TransportSession $session)
    {
        $messageName = $message->getProperty(Config::PARAMETER_MESSAGE_NAME);
        if (false == $messageName) {
            throw new \LogicException(sprintf('Got message without required parameter: "%s"', Config::PARAMETER_MESSAGE_NAME));
        }

        foreach ($this->routeRegistry->getRoutes($messageName) as $route) {
            $properties = $message->getProperties();
            $properties[Config::PARAMETER_PROCESSOR_NAME] = $route->getProcessorName();
            $properties[Config::PARAMETER_QUEUE_NAME] = $route->getQueueName();

            $queueMessage = $this->session->createMessage();
            $queueMessage->setProperties($properties);
            $queueMessage->setHeaders($message->getHeaders());
            $queueMessage->setBody($message->getBody());

            $this->session->createQueueProducer()->send($queueMessage);
        }
    }
}
