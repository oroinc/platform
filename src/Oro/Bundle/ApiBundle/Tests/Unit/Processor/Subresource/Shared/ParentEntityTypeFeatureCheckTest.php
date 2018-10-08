<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ParentEntityTypeFeatureCheck;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class ParentEntityTypeFeatureCheckTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FeatureChecker */
    private $featureChecker;

    /** @var ParentEntityTypeFeatureCheck */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->processor = new ParentEntityTypeFeatureCheck($this->featureChecker);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testProcessDisabled()
    {
        $parentClassName = 'Test\Class';

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($parentClassName, 'api_resources')
            ->willReturn(false);

        $this->context->setParentClassName($parentClassName);
        $this->processor->process($this->context);
    }

    public function testProcessEnabled()
    {
        $parentClassName = 'Test\Class';

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($parentClassName, 'api_resources')
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->processor->process($this->context);
    }
}
