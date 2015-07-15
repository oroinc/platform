<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\EmailUserListener;
use Oro\Bundle\EmailBundle\Entity\EmailUser;

class EmailUserListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailUserListener */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $processor;

    public function setUp()
    {
        $this->processor = $this->getMockBuilder('Oro\Bundle\EmailBundle\Model\WebSocket\WebSocketSendProcessor')
                ->disableOriginalConstructor()
                ->getMock();

        $this->listener = new EmailUserListener($this->processor);
    }

    public function testFlush()
    {
        $emailUserArray = [new EmailUser(), new EmailUser()];

        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->setMethods(['getEntityManager', 'getUnitOfWork', 'getScheduledEntityInsertions'])
            ->disableOriginalConstructor()
            ->getMock();
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($onFlushEventArgs));
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($onFlushEventArgs));
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue($emailUserArray));
        $this->processor
            ->expects($this->exactly(2))
            ->method('send');
        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener->onFlush($onFlushEventArgs);
        $this->listener->postFlush($postFlushEventArgs);
    }
}
