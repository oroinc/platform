<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ParentActionAvailabilityCheck;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class ParentActionAvailabilityCheckTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $resourcesProvider;

    /** @var ParentActionAvailabilityCheck */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->resourcesProvider = $this->getMockBuilder(ResourcesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ParentActionAvailabilityCheck($this->resourcesProvider);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\ActionNotAllowedException
     */
    public function testProcessWhenActionIsExcluded()
    {
        $parentEntityClass = 'Test\Class';

        $this->resourcesProvider->expects($this->once())
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

        $this->resourcesProvider->expects($this->once())
            ->method('getResourceExcludeActions')
            ->with($parentEntityClass, $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([]);

        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAction('action1');
        $this->processor->process($this->context);
    }
}
