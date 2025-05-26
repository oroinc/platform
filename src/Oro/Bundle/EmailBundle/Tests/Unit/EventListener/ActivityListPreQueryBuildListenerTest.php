<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActivityListBundle\Event\ActivityListPreQueryBuildEvent;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\EventListener\ActivityListPreQueryBuildListener;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActivityListPreQueryBuildListenerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private ActivityListPreQueryBuildListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->listener = new ActivityListPreQueryBuildListener($this->doctrineHelper);
    }

    public function testPrepareIdsForEmailThreadEventSkippEntity(): void
    {
        $targetClass = 'testClass';
        $targetId = 1;

        $event = new ActivityListPreQueryBuildEvent($targetClass, $targetId);
        $this->listener->prepareIdsForEmailThreadEvent($event);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntity');
    }

    public function testPrepareIdsForEmailThreadWithoutThreadsEvent(): void
    {
        $targetClass = Email::class;
        $targetId = 1;

        $email = $this->createMock(Email::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->willReturn($email);

        $event = new ActivityListPreQueryBuildEvent($targetClass, $targetId);
        $this->listener->prepareIdsForEmailThreadEvent($event);

        $this->assertEquals([$targetId], $event->getTargetIds());
    }

    public function testPrepareIdsForEmailThreadWithThreadsEvent(): void
    {
        $targetClass = Email::class;
        $targetId = 1;
        $expectedResult = [2, 3];

        $email = $this->createMock(Email::class);
        $thread = $this->createMock(EmailThread::class);

        $email1 = $this->createMock(Email::class);
        $email2 = $this->createMock(Email::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->willReturn($email);

        $email->expects($this->exactly(2))
            ->method('getThread')
            ->willReturn($thread);

        $email1->expects($this->once())
            ->method('getId')
            ->willReturn(2);
        $email2->expects($this->once())
            ->method('getId')
            ->willReturn(3);

        $thread->expects($this->once())
            ->method('getEmails')
            ->willReturn(new ArrayCollection([$email1, $email2]));

        $event = new ActivityListPreQueryBuildEvent($targetClass, $targetId);
        $this->listener->prepareIdsForEmailThreadEvent($event);

        $this->assertEquals($expectedResult, $event->getTargetIds());
    }
}
