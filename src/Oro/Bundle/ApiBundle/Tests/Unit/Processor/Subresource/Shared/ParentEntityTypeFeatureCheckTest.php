<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ParentEntityTypeFeatureCheck;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ParentEntityTypeFeatureCheckTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $featureChecker;

    /**
     * @var ParentEntityTypeFeatureCheck
     */
    protected $processor;

    protected function setUp()
    {
        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new ParentEntityTypeFeatureCheck($this->featureChecker);
    }

    public function testProcessDisabled()
    {
        $className = 'TestClass';

        $context = $this->getMockBuilder(SubresourceContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('getParentClassName')
            ->willReturn($className);
        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($className, 'api_resources')
            ->willReturn(false);
        $this->setExpectedException(AccessDeniedException::class);

        $this->processor->process($context);
    }

    public function testProcessEnabled()
    {
        $className = 'TestClass';

        $context = $this->getMockBuilder(SubresourceContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('getParentClassName')
            ->willReturn($className);
        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($className, 'api_resources')
            ->willReturn(true);

        $this->processor->process($context);
    }
}
