<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessageProducer;
use Oro\Component\MessageQueue\Transport\Exception\Exception;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;

class DbalMessageProducerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalMessageProducer($this->createConnectionMock());
    }

    public function testShouldThrowIfBodyOfInvalidType()
    {
        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message body must be a scalar or null. Got: stdClass');

        $producer = new DbalMessageProducer($this->createConnectionMock());

        $message = new DbalMessage();
        $message->setBody(new \stdClass());

        $producer->send(new DbalDestination(''), $message);
    }

    public function testShouldThrowIfDestinationOfInvalidType()
    {
        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage(
            'The destination must be an instance of '.
            'Oro\Component\MessageQueue\Transport\Dbal\DbalDestination but it is '.
            'Oro\Component\MessageQueue\Transport\Null\NullQueue.'
        );

        $producer = new DbalMessageProducer($this->createConnectionMock());

        $producer->send(new NullQueue(''), new DbalMessage());
    }

    public function testShouldThrowIfInsertMessageFailed()
    {
        $dbal = $this->createDBALConnectionMock();
        $dbal
            ->expects($this->once())
            ->method('insert')
            ->will($this->throwException(new \Exception('error message')))
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('getDBALConnection')
            ->will($this->returnValue($dbal))
        ;

        $destination = new DbalDestination('queue-name');
        $message = new DbalMessage();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The transport fails to send the message due to some internal error.');
        $producer = new DbalMessageProducer($connection);
        $producer->send($destination, $message);
    }

    public function testShouldSendMessage()
    {
        $expectedMessage = [
            'body' => 'body',
            'headers' => '{"hkey":"hvalue"}',
            'properties' => '{"pkey":"pvalue"}',
            'priority' => 123,
            'queue' => 'queue-name',
        ];

        $dbal = $this->createDBALConnectionMock();
        $dbal
            ->expects($this->once())
            ->method('insert')
            ->with('tableName', $expectedMessage)
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('getDBALConnection')
            ->will($this->returnValue($dbal))
        ;
        $connection
            ->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('tableName'))
        ;

        $destination = new DbalDestination('queue-name');
        $message = new DbalMessage();
        $message->setBody('body');
        $message->setHeaders(['hkey' => 'hvalue']);
        $message->setProperties(['pkey' => 'pvalue']);
        $message->setPriority(123);

        $producer = new DbalMessageProducer($connection);
        $producer->send($destination, $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DbalConnection
     */
    private function createConnectionMock()
    {
        return $this->createMock(DbalConnection::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createDBALConnectionMock()
    {
        return $this->createMock(Connection::class);
    }
}
