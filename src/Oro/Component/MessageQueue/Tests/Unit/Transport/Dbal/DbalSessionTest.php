<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageProducer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSchema;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;

class DbalSessionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalSession($this->createConnectionMock());
    }

    public function testShouldCreateMessage()
    {
        $session = new DbalSession($this->createConnectionMock());
        $message = $session->createMessage('body', ['pkey' => 'pval'], ['hkey' => 'hval']);

        $this->assertInstanceOf(DbalMessage::class, $message);
        $this->assertEquals('body', $message->getBody());
        $this->assertEquals(['pkey' => 'pval'], $message->getProperties());
        $this->assertEquals(['hkey' => 'hval'], $message->getHeaders());
        $this->assertSame(0, $message->getPriority());
        $this->assertFalse($message->isRedelivered());
    }

    public function testShouldCreateTopic()
    {
        $session = new DbalSession($this->createConnectionMock());
        $topic = $session->createTopic('topic');

        $this->assertInstanceOf(DbalDestination::class, $topic);
        $this->assertEquals('topic', $topic->getTopicName());
    }

    public function testShouldCreateQueue()
    {
        $session = new DbalSession($this->createConnectionMock());
        $queue = $session->createQueue('queue');

        $this->assertInstanceOf(DbalDestination::class, $queue);
        $this->assertEquals('queue', $queue->getQueueName());
    }

    public function testShouldCreateMessageProducer()
    {
        $session = new DbalSession($this->createConnectionMock());

        $this->assertInstanceOf(DbalMessageProducer::class, $session->createProducer());
    }

    public function testShouldCreateMessageConsumer()
    {
        $session = new DbalSession($this->createConnectionMock());

        $this->assertInstanceOf(DbalMessageConsumer::class, $session->createConsumer(new DbalDestination('')));
    }

    public function testShouldCreateMessageConsumerAndSetPollingInterval()
    {
        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->exactly(2))
            ->method('getOptions')
            ->will($this->returnValue(['polling_interval' => 123456]))
        ;

        $session = new DbalSession($connection);

        $consumer = $session->createConsumer(new DbalDestination(''));

        $this->assertInstanceOf(DbalMessageConsumer::class, $consumer);
        $this->assertEquals(123456, $consumer->getPollingInterval());
    }

    public function testShouldThrowIfDestinationIsInvalidInstanceType()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage(
            'The destination must be an instance of '.
            'Oro\Component\MessageQueue\Transport\Dbal\DbalDestination but it is '.
            'Oro\Component\MessageQueue\Transport\Null\NullQueue.'
        );

        $session = new DbalSession($this->createConnectionMock());

        $this->assertInstanceOf(DbalMessageConsumer::class, $session->createConsumer(new NullQueue('')));
    }

    public function testShouldReturnInstanceOfConnection()
    {
        $session = new DbalSession($this->createConnectionMock());

        $this->assertInstanceOf(DbalConnection::class, $session->getConnection());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalConnection
     */
    private function createConnectionMock()
    {
        return $this->createMock(DbalConnection::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalSchema
     */
    private function createDbalSchemaMock()
    {
        return $this->createMock(DbalSchema::class);
    }
}
