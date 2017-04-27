<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowNotificationHandler;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\EmailNotificationStub;

class WorkflowNotificationHandlerTest extends \PHPUnit_Framework_TestCase
{
    const WORKFLOW_NAME = 'test_workflow_name';
    const TRANSITION_NAME = 'transition_name';

    /** @var \stdClass */
    private $entity;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    private $em;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $configProvider;

    /** @var WorkflowNotificationEvent|\PHPUnit_Framework_MockObject_MockObject */
    private $event;

    /** @var EmailNotificationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var WorkflowNotificationHandler */
    private $handler;

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->entity = new \stdClass();

        $this->event = $this->createMock(WorkflowNotificationEvent::class);
        $this->event->expects($this->any())->method('getEntity')->willReturn($this->entity);

        $this->manager = $this->createMock(EmailNotificationManager::class);

        $this->handler = new WorkflowNotificationHandler($this->manager, $this->em, $this->configProvider);
    }

    /**
     * @dataProvider handleDataProvider
     *
     * @param array $notifications
     * @param array $expected
     */
    public function testHandle(array $notifications, array $expected)
    {
        $expected = array_map(
            function (EmailNotification $notification) {
                return new EmailNotificationAdapter(
                    $this->entity,
                    $notification,
                    $this->em,
                    $this->configProvider
                );
            },
            $expected
        );

        $this->manager->expects($expected ? $this->once() : $this->never())
            ->method('process')
            ->with($this->entity, $expected);

        $this->event->expects($this->once())->method('getTransitionRecord')->willReturn($this->getTransitionRecord());
        $this->event->expects($this->once())->method('stopPropagation');

        $this->handler->handle($this->event, $notifications);
    }

    /**
     * @return array
     */
    public function handleDataProvider()
    {
        $notification1 = new EmailNotificationStub('unknown_workflow', self::TRANSITION_NAME);
        $notification2 = new EmailNotificationStub(self::WORKFLOW_NAME, 'unknown_transition');
        $notification3 = new EmailNotificationStub(self::WORKFLOW_NAME, self::TRANSITION_NAME);

        return [
            'no notifications' => [
                'notifications' => [],
                'expected' => [],
            ],
            'with notifications' => [
                'notifications' => [$notification1, $notification2, $notification3],
                'expected' => [$notification3],
            ]
        ];
    }

    public function testHandleNotSupportedNotification()
    {
        $this->manager->expects($this->never())->method('process');

        /** @var NotificationEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->createMock(NotificationEvent::class);
        $event->expects($this->never())->method('stopPropagation');

        $this->handler->handle($event, []);
    }

    public function testHandleInvalidTransitionRecord()
    {
        $this->manager->expects($this->never())->method('process')->with($this->entity, []);

        $this->event->expects($this->once())->method('getTransitionRecord')->willReturn($this->getTransitionRecord());
        $this->event->expects($this->once())->method('stopPropagation');

        $this->handler->handle($this->event, [new EmailNotificationStub()]);
    }

    /**
     * @return WorkflowTransitionRecord|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTransitionRecord()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getWorkflowName')->willReturn(self::WORKFLOW_NAME);

        /** @var WorkflowTransitionRecord|\PHPUnit_Framework_MockObject_MockObject $transitionRecord */
        $transitionRecord = $this->createMock(WorkflowTransitionRecord::class);
        $transitionRecord->expects($this->any())->method('getTransitionName')->willReturn(self::TRANSITION_NAME);
        $transitionRecord->expects($this->any())->method('getWorkflowItem')->willReturn($workflowItem);

        return $transitionRecord;
    }
}
