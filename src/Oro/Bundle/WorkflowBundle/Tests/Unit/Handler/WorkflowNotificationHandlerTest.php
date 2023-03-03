<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Event\Handler\TemplateEmailNotificationAdapter;
use Oro\Bundle\NotificationBundle\Event\NotificationEvent;
use Oro\Bundle\NotificationBundle\Helper\WebsiteAwareEntityHelper;
use Oro\Bundle\NotificationBundle\Manager\EmailNotificationManager;
use Oro\Bundle\NotificationBundle\Provider\ChainAdditionalEmailAssociationProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Event\WorkflowNotificationEvent;
use Oro\Bundle\WorkflowBundle\Handler\WorkflowNotificationHandler;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\EmailNotificationStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WorkflowNotificationHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const WORKFLOW_NAME = 'test_workflow_name';
    private const TRANSITION_NAME = 'transition_name';

    /** @var \stdClass */
    private $entity;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var WorkflowNotificationEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var EmailNotificationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var ChainAdditionalEmailAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $additionalEmailAssociationProvider;

    /** @var WorkflowNotificationHandler */
    private $handler;

    /** @var WebsiteAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject  */
    private $websiteAwareHelper;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->entity = new \stdClass();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->event = $this->createMock(WorkflowNotificationEvent::class);
        $this->event->expects($this->any())
            ->method('getEntity')
            ->willReturn($this->entity);

        $this->manager = $this->createMock(EmailNotificationManager::class);
        $this->websiteAwareHelper = $this->createMock(WebsiteAwareEntityHelper::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($this->em);

        $this->additionalEmailAssociationProvider = $this->createMock(ChainAdditionalEmailAssociationProvider::class);

        $this->handler = new WorkflowNotificationHandler(
            $this->manager,
            $doctrine,
            PropertyAccess::createPropertyAccessor(),
            $this->eventDispatcher,
            $this->additionalEmailAssociationProvider,
            $this->websiteAwareHelper
        );
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $notifications, array $expected)
    {
        $expected = array_map(
            function (EmailNotification $notification) {
                return new TemplateEmailNotificationAdapter(
                    $this->entity,
                    $notification,
                    $this->em,
                    PropertyAccess::createPropertyAccessor(),
                    $this->eventDispatcher,
                    $this->additionalEmailAssociationProvider,
                    $this->websiteAwareHelper
                );
            },
            $expected
        );

        $record = $this->getTransitionRecord();
        $user = new User();

        $this->manager->expects($expected ? $this->once() : $this->never())
            ->method('process')
            ->with(
                $expected,
                null,
                [
                    'transitionRecord' => $record,
                    'transitionUser' => $user
                ]
            );

        $this->event->expects($this->once())
            ->method('getTransitionRecord')
            ->willReturn($record);
        $this->event->expects($this->any())
            ->method('getTransitionUser')
            ->willReturn($user);
        $this->event->expects($this->once())
            ->method('stopPropagation');

        $this->handler->handle($this->event, $notifications);
    }

    public function handleDataProvider(): array
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
        $this->manager->expects($this->never())
            ->method('process');

        $event = $this->createMock(NotificationEvent::class);
        $event->expects($this->never())
            ->method('stopPropagation');

        $this->handler->handle($event, []);
    }

    public function testHandleInvalidTransitionRecord()
    {
        $this->manager->expects($this->never())
            ->method('process')
            ->with($this->entity, []);

        $this->event->expects($this->once())
            ->method('getTransitionRecord')
            ->willReturn($this->getTransitionRecord());
        $this->event->expects($this->once())
            ->method('stopPropagation');

        $this->handler->handle($this->event, [new EmailNotificationStub()]);
    }

    private function getTransitionRecord(): WorkflowTransitionRecord
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn(self::WORKFLOW_NAME);

        $transitionRecord = $this->createMock(WorkflowTransitionRecord::class);
        $transitionRecord->expects($this->any())
            ->method('getTransitionName')
            ->willReturn(self::TRANSITION_NAME);
        $transitionRecord->expects($this->any())
            ->method('getWorkflowItem')
            ->willReturn($workflowItem);

        return $transitionRecord;
    }
}
