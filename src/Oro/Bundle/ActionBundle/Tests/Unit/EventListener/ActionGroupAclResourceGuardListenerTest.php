<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Event\ActionGroupGuardEvent;
use Oro\Bundle\ActionBundle\EventListener\ActionGroupAclResourceGuardListener;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActionGroupAclResourceGuardListenerTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private OptionsResolver|MockObject $optionsResolver;
    private ActionGroupAclResourceGuardListener $listener;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->optionsResolver = $this->createMock(OptionsResolver::class);
        $this->listener = new ActionGroupAclResourceGuardListener($this->actionExecutor, $this->optionsResolver);
    }

    public function testCheckAclWhenEventIsNotAllowed(): void
    {
        $event = $this->createMock(ActionGroupGuardEvent::class);

        $event->expects($this->once())
            ->method('isAllowed')
            ->willReturn(false);

        $event->expects($this->never())
            ->method('getActionGroupDefinition');

        $this->listener->checkAcl($event);
    }

    public function testCheckAclWhenNoAclResource(): void
    {
        $event = $this->createMock(ActionGroupGuardEvent::class);
        $actionGroupDefinition = $this->createMock(ActionGroupDefinition::class);

        $event->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getActionGroupDefinition')
            ->willReturn($actionGroupDefinition);

        $actionGroupDefinition->expects($this->once())
            ->method('getAclResource')
            ->willReturn(null);

        $this->listener->checkAcl($event);
    }

    public function testCheckAclWhenAclResourceIsNotArray(): void
    {
        $event = $this->createMock(ActionGroupGuardEvent::class);
        $actionGroupDefinition = $this->createMock(ActionGroupDefinition::class);

        $aclResource = 'acl_resource';
        $event->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getActionGroupDefinition')
            ->willReturn($actionGroupDefinition);

        $actionGroupDefinition->expects($this->once())
            ->method('getAclResource')
            ->willReturn($aclResource);

        $this->optionsResolver->expects($this->once())
            ->method('resolveOptions')
            ->with($event->getActionData(), [$aclResource])
            ->willReturn([$aclResource]);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('acl_granted', [$aclResource], $event->getErrors())
            ->willReturn(true);

        $event->expects($this->once())
            ->method('setAllowed')
            ->with(true);

        $this->listener->checkAcl($event);
    }

    public function testCheckAclWhenAclResourceIsArray(): void
    {
        $event = $this->createMock(ActionGroupGuardEvent::class);
        $actionGroupDefinition = $this->createMock(ActionGroupDefinition::class);

        $aclResources = ['CREATE', '$.data'];
        $event->expects($this->once())
            ->method('isAllowed')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getActionGroupDefinition')
            ->willReturn($actionGroupDefinition);

        $actionGroupDefinition->expects($this->once())
            ->method('getAclResource')
            ->willReturn($aclResources);

        $this->optionsResolver->expects($this->once())
            ->method('resolveOptions')
            ->with($event->getActionData(), $aclResources)
            ->willReturn($aclResources);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('acl_granted', $aclResources, $event->getErrors())
            ->willReturn(true);

        $event->expects($this->once())
            ->method('setAllowed')
            ->with(true);

        $this->listener->checkAcl($event);
    }
}
