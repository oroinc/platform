<?php
namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\DBAL\Types\Type;
use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\Exception\Exception;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\MessageQueue\Util\JSON;

class DbalMessageProducer implements MessageProducerInterface
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
     *
     * @param DbalDestination $destination
     * @param DbalMessage     $message
     *
     * @throws Exception
     */
    public function send(DestinationInterface $destination, MessageInterface $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, DbalDestination::class);

        $body = $message->getBody();
        if (is_scalar($body) || is_null($body)) {
            $body = (string)$body;
        } else {
            throw new InvalidMessageException(sprintf(
                'The message body must be a scalar or null. Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }

        $dbalMessage = [
            'body' => $body,
            'headers' => JSON::encode($message->getHeaders()),
            'properties' => JSON::encode($message->getProperties()),
            'priority' => $message->getPriority(),
            'queue' => $destination->getQueueName(),
        ];

        $delay = $message->getDelay();
        if ($delay) {
            if (! is_int($delay)) {
                throw new \LogicException(sprintf(
                    'Delay must be integer but got: "%s"',
                    is_object($delay) ? get_class($delay) : gettype($delay)
                ));
            }

            if ($delay <= 0) {
                throw new \LogicException(sprintf('Delay must be positive integer but got: "%s"', $delay));
            }

            $dbalMessage['delayed_until'] = time() + $delay;
        }

        try {
            $this->connection->getDBALConnection()->insert($this->connection->getTableName(), $dbalMessage, [
                'body' => Type::TEXT,
                'headers' => Type::TEXT,
                'properties' => Type::TEXT,
                'priority' => Type::SMALLINT,
                'queue' => Type::STRING,
                'delayed_until' => Type::INTEGER,
            ]);
        } catch (\Exception $e) {
            throw new Exception('The transport fails to send the message due to some internal error.', null, $e);
        }
    }
}
