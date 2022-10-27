<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageConsumer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageProducer;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;
use Oro\Component\MessageQueue\Transport\Queue;

class DbalSessionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DbalConnection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var DbalSession */
    private $session;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->connection = $this->createMock(DbalConnection::class);
        $this->session = new DbalSession($this->connection);
    }

    public function testCreateMessage(): void
    {
        $message = $this->session->createMessage(
            'message body',
            ['propertyKey' => 'propertyValue'],
            ['headerKey' => 'headerValue']
        );

        $expectedMessage = new DbalMessage();
        $expectedMessage->setBody('message body');
        $expectedMessage->setProperties(['propertyKey' => 'propertyValue']);
        $expectedMessage->setHeaders(['headerKey' => 'headerValue']);

        $this->assertEquals($expectedMessage, $message);
    }

    public function testCreateQueue(): void
    {
        $queue = $this->session->createQueue('queue name');
        $expectedQueue = new Queue('queue name');
        $this->assertEquals($expectedQueue, $queue);
    }

    public function testCreateConsumer(): void
    {
        $queue = new Queue('queue name');

        $this->connection
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn([]);

        /** @var DbalMessageConsumer $consumer */
        $consumer = $this->session->createConsumer($queue);
        $expectedConsumer = new DbalMessageConsumer($this->session, $queue);

        $this->assertInstanceOf(DbalMessageConsumer::class, $consumer);
        $this->assertEquals($expectedConsumer->getPollingInterval(), $consumer->getPollingInterval());
    }

    public function testCreateConsumerWithPolingInterval(): void
    {
        $queue = new Queue('queue name');

        $this->connection
            ->expects($this->once())
            ->method('getOptions')
            ->willReturn([
                'polling_interval' => 2000
            ]);

        /** @var DbalMessageConsumer $consumer */
        $consumer = $this->session->createConsumer($queue);
        $expectedConsumer = new DbalMessageConsumer($this->session, $queue);
        $expectedConsumer->setPollingInterval(2000);

        $this->assertInstanceOf(DbalMessageConsumer::class, $consumer);
        $this->assertEquals($expectedConsumer->getPollingInterval(), $consumer->getPollingInterval());
    }

    public function testCreateProducer(): void
    {
        $this->session->createProducer();
        $producer = $this->session->createProducer();
        $expectedProducer = new DbalMessageProducer($this->connection);
        $this->assertEquals($expectedProducer, $producer);
    }

    public function testGetConnection(): void
    {
        $this->assertEquals($this->connection, $this->session->getConnection());
    }
}
