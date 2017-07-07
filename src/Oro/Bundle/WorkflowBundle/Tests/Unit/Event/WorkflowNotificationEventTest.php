<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;

class WorkflowNotificationEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowTransitionRecord|\PHPUnit_Framework_MockObject_MockObject */
    private $transitionRecord;

    /** @var AbstractUser */
    private $user;

    /** @var WorkflowNotificationEvent */
    private $event;

    public function setUp()
    {
        $this->transitionRecord = $this->createMock(WorkflowTransitionRecord::class);
        $this->user = new User();

        $this->event = new WorkflowNotificationEvent(new \stdClass(), $this->transitionRecord, $this->user);
    }

    public function testGetTransitionRecord()
    {
        $this->assertSame($this->transitionRecord, $this->event->getTransitionRecord());
    }

    public function testGetTransitionUser()
    {
        $this->assertSame($this->user, $this->event->getTransitionUser());
    }
}
