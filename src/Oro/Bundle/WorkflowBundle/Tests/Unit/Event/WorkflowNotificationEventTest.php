<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowNotificationEventTest extends TestCase
{
    private WorkflowTransitionRecord&MockObject $transitionRecord;
    private AbstractUser $user;
    private WorkflowNotificationEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->transitionRecord = $this->createMock(WorkflowTransitionRecord::class);
        $this->user = new User();

        $this->event = new WorkflowNotificationEvent(new \stdClass(), $this->transitionRecord, $this->user);
    }

    public function testGetTransitionRecord(): void
    {
        $this->assertSame($this->transitionRecord, $this->event->getTransitionRecord());
    }

    public function testGetTransitionUser(): void
    {
        $this->assertSame($this->user, $this->event->getTransitionUser());
    }
}
