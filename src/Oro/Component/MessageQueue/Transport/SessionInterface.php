<?php
namespace Oro\Component\MessageQueue\Transport;

interface SessionInterface
{
    /**
     * @param string $body
     * @param array  $properties
     * @param array  $headers
     *
     * @return MessageInterface
     */
    public function createMessage($body = null, array $properties = [], array $headers = []);

    /**
     * @param string $name
     *
     * @return QueueInterface
     */
    public function createQueue($name);

    /**
     * @param string $name
     *
     * @return TopicInterface
     */
    public function createTopic($name);

    /**
     * @param DestinationInterface $destination
     *
     * @return MessageConsumerInterface
     */
    public function createConsumer(DestinationInterface $destination);

    /**
     * @return MessageProducerInterface
     */
    public function createProducer();

    /**
     * @param DestinationInterface $destination
     */
    public function declareTopic(DestinationInterface $destination);

    /**
     * @param DestinationInterface $destination
     */
    public function declareQueue(DestinationInterface $destination);

    /**
     * @param DestinationInterface $source
     * @param DestinationInterface $target
     */
    public function declareBind(DestinationInterface $source, DestinationInterface $target);
    
    public function close();
}
