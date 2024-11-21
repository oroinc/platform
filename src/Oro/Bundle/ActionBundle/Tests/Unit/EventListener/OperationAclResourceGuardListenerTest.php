<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Event\OperationAnnounceEvent;
use Oro\Bundle\ActionBundle\EventListener\OperationAclResourceGuardListener;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Resolver\OptionsResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OperationAclResourceGuardListenerTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private OptionsResolver|MockObject $optionsResolver;
    private OperationAclResourceGuardListener $listener;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->optionsResolver = $this->createMock(OptionsResolver::class);
        $this->listener = new OperationAclResourceGuardListener($this->actionExecutor, $this->optionsResolver);
    }

    public function testCheckAclWhenEventIsNotAllowed(): void
    {
        $data = new ActionData();
        $operationDefinition = new OperationDefinition();
        $operationDefinition->setName('operation_name');
        $event = new OperationAnnounceEvent($data, $operationDefinition);
        $event->setAllowed(false);

        $this->listener->checkAcl($event);

        $this->assertFalse($event->isAllowed());
    }

    public function testCheckAclWhenNoAclResource(): void
    {
        $data = new ActionData();
        $operationDefinition = new OperationDefinition();
        $operationDefinition->setName('operation_name');
        $event = new OperationAnnounceEvent($data, $operationDefinition);
        $event->setAllowed(true);

        $this->listener->checkAcl($event);

        $this->assertTrue($event->isAllowed());
    }

    public function testCheckAclWhenAclResourceIsNotArray(): void
    {
        $aclResource = 'acl_resource';

        $data = new ActionData();
        $operationDefinition = new OperationDefinition();
        $operationDefinition->setName('operation_name');
        $operationDefinition->setAclResource($aclResource);
        $event = new OperationAnnounceEvent($data, $operationDefinition);
        $event->setAllowed(true);

        $this->optionsResolver->expects($this->once())
            ->method('resolveOptions')
            ->with($event->getActionData(), [$aclResource])
            ->willReturn([$aclResource]);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('acl_granted', [$aclResource], $event->getErrors())
            ->willReturn(true);

        $this->listener->checkAcl($event);

        $this->assertTrue($event->isAllowed());
    }

    public function testCheckAclWhenAclResourceIsArray(): void
    {
        $aclResources = ['CREATE', '$.data'];

        $data = new ActionData();
        $operationDefinition = new OperationDefinition();
        $operationDefinition->setName('operation_name');
        $operationDefinition->setAclResource($aclResources);
        $event = new OperationAnnounceEvent($data, $operationDefinition);
        $event->setAllowed(true);

        $this->optionsResolver->expects($this->once())
            ->method('resolveOptions')
            ->with($event->getActionData(), $aclResources)
            ->willReturn($aclResources);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('acl_granted', $aclResources, $event->getErrors())
            ->willReturn(false);

        $this->listener->checkAcl($event);

        $this->assertFalse($event->isAllowed());
    }
}
