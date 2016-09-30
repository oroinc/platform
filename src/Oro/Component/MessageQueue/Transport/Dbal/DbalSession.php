<?php
namespace Oro\Component\MessageQueue\Transport\Dbal;

use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DbalSession implements SessionInterface
{
    /**
     * @var DbalConnection
     */
    private $connection;

    /**
     * @param DbalConnection $connection
     */
    public function __construct(DbalConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage($body = null, array $properties = [], array $headers = [])
    {
        $message = new DbalMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalDestination
     */
    public function createQueue($name)
    {
        return new DbalDestination($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalDestination
     */
    public function createTopic($name)
    {
        return new DbalDestination($name);
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalDestination $destination
     */
    public function createConsumer(DestinationInterface $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, DbalDestination::class);

        $consumer = new DbalMessageConsumer($this, $destination);

        if (isset($this->connection->getOptions()['polling_interval'])) {
            $consumer->setPollingInterval($this->connection->getOptions()['polling_interval']);
        }

        return $consumer;
    }

    /**
     * {@inheritdoc}
     */
    public function createProducer()
    {
        return new DbalMessageProducer($this->connection);
    }

    /**
     * {@inheritdoc}
     */
    public function declareTopic(DestinationInterface $destination)
    {
        // does nothing, installer creates all required tables
    }

    /**
     * {@inheritdoc}
     */
    public function declareQueue(DestinationInterface $destination)
    {
        // does nothing, installer creates all required tables
    }

    /**
     * {@inheritdoc}
     */
    public function declareBind(DestinationInterface $source, DestinationInterface $target)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }

    /**
     * @return DbalConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
