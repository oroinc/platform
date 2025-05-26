<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentActionAvailability;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ValidateParentActionAvailabilityTest extends GetSubresourceProcessorTestCase
{
    private ResourcesProvider&MockObject $resourcesProvider;
    private ValidateParentActionAvailability $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new ValidateParentActionAvailability($this->resourcesProvider);
    }

    public function testProcessWhenActionIsExcluded(): void
    {
        $this->expectException(ActionNotAllowedException::class);
        $parentEntityClass = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(['action1', 'action2']);

        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAction('action1');
        $this->processor->process($this->context);
    }

    public function testProcessWhenActionIsNotExcluded(): void
    {
        $parentEntityClass = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAction('action1');
        $this->processor->process($this->context);
    }
}
