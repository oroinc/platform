<?php
namespace Oro\Component\MessageQueue\Transport\Dbal;

use Doctrine\DBAL\Schema\Table;
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
        $this->declareQueue($destination);
    }

    /**
     * {@inheritdoc}
     */
    public function declareQueue(DestinationInterface $destination)
    {
        $sm = $this->connection->getDBALConnection()->getSchemaManager();

        if ($sm->tablesExist([$this->connection->getTableName()])) {
            return;
        }

        $table = new Table($this->connection->getTableName());
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true,]);
        $table->addColumn('body', 'text', ['notnull' => false,]);
        $table->addColumn('headers', 'text', ['notnull' => false,]);
        $table->addColumn('properties', 'text', ['notnull' => false,]);
        $table->addColumn('consumer_id', 'string', ['notnull' => false,]);
        $table->addColumn('redelivered', 'boolean', ['notnull' => false,]);
        $table->addColumn('queue', 'string');
        $table->addColumn('priority', 'smallint');
        $table->addColumn('delivered_at', 'integer', ['notnull' => false,]);
        $table->addColumn('delayed_until', 'integer', ['notnull' => false,]);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['consumer_id']);
        $table->addIndex(['queue']);
        $table->addIndex(['priority']);
        $table->addIndex(['delivered_at']);
        $table->addIndex(['delayed_until']);

        $sm->createTable($table);
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
