<?php
namespace Oro\Component\Messaging\ZeroConfig;

/**
 * Facade
 */
class ZeroConfig
{
    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string $messageName
     * @param string $messageBody
     */
    public function sendMessage($messageName, $messageBody)
    {
        $message = $this->factory->createRouterMessage($messageName, $messageBody);

        $topic = $this->factory->createRouterTopic();
        $producer = $this->factory->createRouterMessageProducer();

        $producer->send($topic, $message);
    }
}
