<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ParentActionAvailabilityCheck;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class ParentActionAvailabilityCheckTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ResourcesProvider */
    private $resourcesProvider;

    /** @var ParentActionAvailabilityCheck */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new ParentActionAvailabilityCheck($this->resourcesProvider);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException
     */
    public function testProcessWhenActionIsExcluded()
    {
        $parentEntityClass = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(['action1', 'action2']);

        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAction('action1');
        $this->processor->process($this->context);
    }

    public function testProcessWhenActionIsNotExcluded()
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
