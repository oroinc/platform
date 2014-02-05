<?php
namespace Oro\Bundle\SyncBundle\Tests\Unit\Wamp;

use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Oro\Bundle\SyncBundle\Wamp\WebSocket;

class TopicPublisherTest extends \PHPUnit_Framework_TestCase
{
    const TOPIC = 'Topic';
    const MESSAGE = 'Message';

    /** @var  TopicPublisher */
    protected $wamp;

    /** @var WebSocket */
    protected $socket;
    public function setUp()
    {
        $this->socket = $this->getMock('WebSocket', array('sendData'), array(), '', false);

        $this->wamp = new TopicPublisher();
        /** @var  \ReflectionClass $reflection */
        $reflection = new \ReflectionClass('Oro\Bundle\SyncBundle\Wamp\TopicPublisher');
        $reflection_property = $reflection->getProperty('ws');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->wamp, $this->socket);
    }

    public function testSend()
    {
        $this->socket->expects($this->any())
            ->method('sendData')
            ->with(
                json_encode(
                    array(
                          \Ratchet\Wamp\ServerProtocol::MSG_PUBLISH,
                          self::TOPIC,
                          self::MESSAGE
                    )
                )
            );
        $this->assertTrue($this->wamp->send(self::TOPIC, self::MESSAGE));
    }

    public function testCheckTrue()
    {
        $this->assertTrue($this->wamp->check());
    }

    public function testCheckFalse()
    {
        $this->wamp = new TopicPublisher();
        $this->assertFalse($this->wamp->check());
    }
}
