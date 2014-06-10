<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\EntityListener;

class EntitySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailOwnerManager;

    protected function setUp()
    {
        $this->emailOwnerManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new EntityListener($this->emailOwnerManager);
    }

    public function testOnFlush()
    {
        $eventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailOwnerManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($eventArgs));

        $this->listener->onFlush($eventArgs);
    }
}
