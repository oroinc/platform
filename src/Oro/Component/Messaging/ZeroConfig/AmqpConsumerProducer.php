<?php
namespace Oro\Component\Messaging\ZeroConfig;


class AmqpConsumerProducer implements ConsumerProducerInterface
{
    /**
     * @var AmqpFactory
     */
    protected $factory;

    /**
     * {@inheritdoc}
     */
    public function sendMessage($consumerName, $messageHandler, $messageName, $messageBody)
    {
        $message = $this->factory->createConsumerMessage($messageName, $messageHandler, $messageBody);

        $topic = $this->factory->createConsumerTopic($consumerName);

        $producer = $this->factory->createConsumerMessageProducer($consumerName);
        $producer->send($topic, $message);
    }
}
