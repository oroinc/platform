<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeEntities;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class NormalizeEntitiesTest extends GetListProcessorTestCase
{
    private ObjectNormalizer&MockObject $objectNormalizer;
    private NormalizeEntities $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectNormalizer = $this->createMock(ObjectNormalizer::class);

        $this->processor = new NormalizeEntities($this->objectNormalizer);
    }

    public function testProcessWhenNoData(): void
    {
        $this->objectNormalizer->expects(self::never())
            ->method('normalizeObjects');

        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWhenEmptyData(): void
    {
        $this->objectNormalizer->expects(self::never())
            ->method('normalizeObjects');

        $this->context->setResult([]);
        $this->processor->process($this->context);
        self::assertSame([], $this->context->getResult());
    }

    public function testProcess(): void
    {
        $data = [new \stdClass()];
        $normalizedData = [['key' => 'value']];
        $config = new EntityDefinitionConfig();

        $this->objectNormalizer->expects(self::once())
            ->method('normalizeObjects')
            ->with($data, $config, $this->context->getNormalizationContext())
            ->willReturn($normalizedData);

        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($normalizedData, $this->context->getResult());
    }

    public function testProcessWithNormalizedConfig(): void
    {
        $data = [new \stdClass()];
        $normalizedData = [['key' => 'value']];
        $config = new EntityDefinitionConfig();
        $normalizationContext = ['option' => 'value'];

        $context = $this->createMock(FormContext::class);
        $context->expects(self::once())
            ->method('hasResult')
            ->willReturn(true);
        $context->expects(self::once())
            ->method('getResult')
            ->willReturn($data);
        $context->expects(self::once())
            ->method('getNormalizedConfig')
            ->willReturn($config);
        $context->expects(self::once())
            ->method('getNormalizationContext')
            ->willReturn($normalizationContext);
        $context->expects(self::once())
            ->method('setResult')
            ->with($normalizedData);

        $this->objectNormalizer->expects(self::once())
            ->method('normalizeObjects')
            ->with($data, $config, $normalizationContext)
            ->willReturn($normalizedData);

        $this->processor->process($context);
    }
}
