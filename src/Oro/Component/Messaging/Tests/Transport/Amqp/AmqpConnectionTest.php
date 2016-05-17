<?php
namespace Oro\Component\Messaging\Tests\Transport\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpConnection;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use Oro\Component\Testing\ClassExtensionTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

class AmqpConnectionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\Connection',
            'Oro\Component\Messaging\Transport\Amqp\AmqpConnection'
        );
    }

    public function testCouldBeConstructedWithLibAmqpConnection()
    {
        new AmqpConnection($this->createAMQPLibConnection());
    }

    public function testShouldAllowCreateSession()
    {
        $libConnection = $this->createAMQPLibConnection();
        $libConnection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($this->createAMQPLibChannel())
        ;

        $connection = new AmqpConnection($libConnection);

        $session = $connection->createSession();

        $this->assertInstanceOf(AmqpSession::class, $session);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractConnection
     */
    protected function createAMQPLibConnection()
    {
        return $this->getMock(AbstractConnection::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    protected function createAMQPLibChannel()
    {
        return $this->getMock(AMQPChannel::class, [], [], '', false);
    }

}
