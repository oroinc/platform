<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;

class Router
{
    /**
     * @var RouteRegistryInterface
     */
    protected $routeRegistry;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var string
     */
    protected $defaultConsumerName = 'default';

    /**
     * @param Message $message
     */
    public function route(Message $message)
    {
        $messageName = $message->getProperty('messageName');
        if (false == $messageName) {
            throw new \LogicException('Got empty message name.');
        }

        foreach ($this->routeRegistry->getRoutes($messageName) as $route) {
            $this->processRoute($route, $message);
        }
    }

    /**
     * @param Route   $route
     * @param Message $message
     */
    protected function processRoute(Route $route, Message $message)
    {
        $message = $this->factory->createConsumerMessage(
            $route->getMessageName(),
            $route->getProcessorName(),
            $message->getBody()
        );
        $topic = $this->factory->createConsumerTopic();
        
        $consumerName = $route->getConsumerName() ?: $this->defaultConsumerName;

        $producer = $this->factory->createConsumerMessageProducer($consumerName);
        $producer->send($topic, $message);
    }
}
