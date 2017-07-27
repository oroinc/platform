<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\EntityTypeFeatureCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class EntityTypeFeatureCheckTest extends GetListProcessorTestCase
{
    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    protected $featureChecker;

    /** @var EntityTypeFeatureCheck */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->featureChecker = $this->getMockBuilder(FeatureChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new EntityTypeFeatureCheck($this->featureChecker);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessDisabled()
    {
        $className = 'Test\Class';

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($className, 'api_resources')
            ->willReturn(false);

        $this->context->setClassName($className);
        $this->processor->process($this->context);
    }

    public function testProcessEnabled()
    {
        $className = 'Test\Class';

        $this->featureChecker->expects($this->once())
            ->method('isResourceEnabled')
            ->with($className, 'api_resources')
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->processor->process($this->context);
    }
}
