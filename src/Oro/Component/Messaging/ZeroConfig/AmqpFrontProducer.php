<?php
namespace Oro\Component\Messaging\ZeroConfig;

class AmqpFrontProducer implements FrontProducerInterface
{
    /**
     * @var AmqpFactory
     */
    protected $factory;

    /**
     * @param AmqpFactory $factory
     */
    public function __construct(AmqpFactory $factory)
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
