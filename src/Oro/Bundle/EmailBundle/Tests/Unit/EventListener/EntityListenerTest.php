<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\EntityListener;

class EntitySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailOwnerManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailActivityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $emailThreadManager;

    protected function setUp()
    {
        $this->emailOwnerManager    =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailOwnerManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->emailActivityManager =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->emailThreadManager =
            $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->listener             = new EntityListener(
            $this->emailOwnerManager,
            $this->emailActivityManager,
            $this->emailThreadManager
        );
    }

    public function testOnFlush()
    {
        $eventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailOwnerManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($eventArgs));
        $this->emailActivityManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($eventArgs));
        $this->emailThreadManager->expects($this->once())
            ->method('handleOnFlush')
            ->with($this->identicalTo($eventArgs));

        $this->listener->onFlush($eventArgs);
    }
}
