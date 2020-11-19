<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityTypeFeature;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class ValidateParentEntityTypeFeatureTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FeatureChecker */
    private $featureChecker;

    /** @var ValidateParentEntityTypeFeature */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->processor = new ValidateParentEntityTypeFeature($this->featureChecker);
    }

    public function testProcessDisabled()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
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
