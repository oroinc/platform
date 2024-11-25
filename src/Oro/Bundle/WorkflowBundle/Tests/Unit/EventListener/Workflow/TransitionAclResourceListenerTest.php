<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener\Workflow;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\PreAnnounceEvent;
use Oro\Bundle\WorkflowBundle\EventListener\Workflow\TransitionAclResourceListener;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory;
use Oro\Component\ConfigExpression\ExpressionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransitionAclResourceListenerTest extends TestCase
{
    private ExpressionFactory|MockObject $expressionFactory;
    private TransitionAclResourceListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->expressionFactory = $this->createMock(ExpressionFactory::class);
        $this->listener = new TransitionAclResourceListener($this->expressionFactory);
    }

    public function testOnPreAnnounceWhenNotAllowed(): void
    {
        $transition = $this->createMock(Transition::class);
        $event = new PreAnnounceEvent($this->createMock(WorkflowItem::class), $transition, false);

        $this->expressionFactory->expects($this->never())
            ->method('create');

        $this->listener->onPreAnnounce($event);
    }

    public function testOnPreAnnounceWhenNoAclResource(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->method('getAclResource')->willReturn(null);

        $event = new PreAnnounceEvent($this->createMock(WorkflowItem::class), $transition, true);

        $this->expressionFactory->expects($this->never())
            ->method('create');

        $this->listener->onPreAnnounce($event);
    }

    public function testOnPreAnnounceWithAclResourceAndMessage(): void
    {
        $aclResource = ['some_acl_resource'];
        $aclMessage = 'Access Denied';

        $transition = $this->createMock(Transition::class);
        $transition->method('getAclResource')->willReturn($aclResource);
        $transition->method('getAclMessage')->willReturn($aclMessage);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new PreAnnounceEvent($workflowItem, $transition, true);

        $expression = $this->createMock(ExpressionInterface::class);
        $expression->expects($this->once())
            ->method('evaluate')
            ->with($workflowItem, $event->getErrors())
            ->willReturn(false);

        $this->expressionFactory->expects($this->once())
            ->method('create')
            ->with(
                ConfigurableCondition::ALIAS,
                ['@acl_granted' => ['parameters' => $aclResource, 'message' => $aclMessage]]
            )
            ->willReturn($expression);

        $this->listener->onPreAnnounce($event);

        $this->assertFalse($event->isAllowed());
    }

    public function testOnPreAnnounceWithAclResourceWithoutMessage(): void
    {
        $aclResource = ['some_acl_resource'];

        $transition = $this->createMock(Transition::class);
        $transition->method('getAclResource')->willReturn($aclResource);
        $transition->method('getAclMessage')->willReturn(null);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new PreAnnounceEvent($workflowItem, $transition, true);

        $expression = $this->createMock(ExpressionInterface::class);
        $expression->expects($this->once())
            ->method('evaluate')
            ->with($workflowItem, $event->getErrors())
            ->willReturn(true);

        $this->expressionFactory->expects($this->once())
            ->method('create')
            ->with(
                ConfigurableCondition::ALIAS,
                ['@acl_granted' => ['parameters' => $aclResource]]
            )
            ->willReturn($expression);

        $this->listener->onPreAnnounce($event);

        $this->assertTrue($event->isAllowed());
    }
}
