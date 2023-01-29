<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowEvents;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Oro\Bundle\WorkflowBundle\EventListener\WorkflowTransitionRecordListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WorkflowTransitionRecordListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject */
    private $args;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var WorkflowTransitionRecordListener */
    private $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->args = $this->createMock(LifecycleEventArgs::class);

        $this->listener = new WorkflowTransitionRecordListener($this->eventDispatcher, $this->tokenStorage);
    }

    public function testPostPersistDisabledListener()
    {
        $this->listener->setEnabled(false);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->listener->postPersist($this->createMock(WorkflowTransitionRecord::class), $this->args);
    }

    /**
     * @dataProvider postPersistDataProvider
     */
    public function testPostPersist(
        WorkflowTransitionRecord $transitionRecord,
        ?TokenInterface $token,
        WorkflowNotificationEvent $expected
    ) {
        $this->listener->setEnabled(true);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($expected, WorkflowEvents::NOTIFICATION_TRANSIT_EVENT);

        $this->listener->postPersist($transitionRecord, $this->args);
    }

    public function postPersistDataProvider(): array
    {
        $entity = new \stdClass();

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);

        $transitionRecord = $this->createMock(WorkflowTransitionRecord::class);
        $transitionRecord->expects($this->any())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        $user = new User();

        $token = $this->createMock(TokenInterface::class);

        $tokenWithUser = $this->createMock(TokenInterface::class);
        $tokenWithUser->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        return [
            'without token' => [
                'transitionRecord' => $transitionRecord,
                'token' => null,
                'expected' => new WorkflowNotificationEvent($entity, $transitionRecord)
            ],
            'without user' => [
                'transitionRecord' => $transitionRecord,
                'token' => $token,
                'expected' => new WorkflowNotificationEvent($entity, $transitionRecord)
            ],
            'with user' => [
                'transitionRecord' => $transitionRecord,
                'token' => $tokenWithUser,
                'expected' => new WorkflowNotificationEvent($entity, $transitionRecord, $user)
            ],
        ];
    }
}
