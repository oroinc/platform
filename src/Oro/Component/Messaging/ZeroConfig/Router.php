<?php
namespace Oro\Component\Messaging\ZeroConfig;

class Router
{
    /**
     * @var RouteRegistryInterface
     */
    protected $routeRegistry;

    /**
     * @var ConsumerProducerInterface
     */
    protected $consumerProducer;

    /**
     * @var string
     */
    protected $defaultConsumerName;

    /**
     * @param string $messageName
     * @param string $messageBody
     */
    public function route($messageName, $messageBody)
    {
        foreach ($this->routeRegistry->getRoutes($messageName) as $route) {
            $consumerName = $route->getConsumerName() ?: $this->defaultConsumerName;

            $this->consumerProducer->sendMessage($consumerName, $route->getHandlerName(), $messageName, $messageBody);
        }
    }
}
