<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityTypeFeature;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class ValidateEntityTypeFeatureTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FeatureChecker */
    private $featureChecker;

    /** @var ValidateEntityTypeFeature */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->processor = new ValidateEntityTypeFeature($this->featureChecker);
    }

    public function testProcessDisabled()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $className = 'Test\Class';

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($className, 'api_resources')
            ->willReturn(false);

        $this->context->setClassName($className);
        $this->processor->process($this->context);
    }

    public function testProcessEnabled()
    {
        $className = 'Test\Class';

        $this->featureChecker->expects(self::once())
            ->method('isResourceEnabled')
            ->with($className, 'api_resources')
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->processor->process($this->context);
    }
}
