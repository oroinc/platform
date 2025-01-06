<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Processor\Shared\ValidateActionAvailability;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class ValidateActionAvailabilityTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesProvider */
    private $resourcesProvider;

    /** @var ValidateActionAvailability */
    private $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new ValidateActionAvailability($this->resourcesProvider);
    }

    public function testProcessForPrimaryEntityWhenActionIsExcluded()
    {
        $this->expectException(ActionNotAllowedException::class);
        $this->expectExceptionMessage('The action is not allowed.');
        $entityClass = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(['action1', 'action2']);

        $this->context->setMasterRequest(true);
        $this->context->setClassName($entityClass);
        $this->context->setAction('action1');
        $this->processor->process($this->context);
    }

    public function testProcessForIncludedEntityWhenActionIsExcluded()
    {
        $this->expectException(ActionNotAllowedException::class);
        $this->expectExceptionMessage('The "action1" action is not allowed.');
        $entityClass = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(['action1', 'action2']);

        $this->context->setMasterRequest(false);
        $this->context->setClassName($entityClass);
        $this->context->setAction('action1');
        $this->processor->process($this->context);
    }

    public function testProcessWhenActionIsNotExcluded()
    {
        $entityClass = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with($entityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->context->setClassName($entityClass);
        $this->context->setAction('action1');
        $this->processor->process($this->context);
    }
}
