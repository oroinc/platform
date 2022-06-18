<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\ValidateEntityTypeFeature;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateEntityTypeFeatureTest extends GetListProcessorTestCase
{
    /** @var ResourcesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $resourcesProvider;

    /** @var ValidateEntityTypeFeature */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new ValidateEntityTypeFeature($this->resourcesProvider);
    }

    public function testProcessDisabled(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $className = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $className,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(false);

        $this->context->setClassName($className);
        $this->processor->process($this->context);
    }

    public function testProcessEnabled(): void
    {
        $className = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $className,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(true);

        $this->context->setClassName($className);
        $this->processor->process($this->context);
    }
}
