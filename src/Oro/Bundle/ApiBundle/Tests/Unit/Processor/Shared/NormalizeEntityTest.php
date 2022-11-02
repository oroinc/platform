<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class NormalizeEntityTest extends GetProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectNormalizer */
    private $objectNormalizer;

    /** @var NormalizeEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectNormalizer = $this->createMock(ObjectNormalizer::class);

        $this->processor = new NormalizeEntity($this->objectNormalizer);
    }

    public function testProcessWhenNoData()
    {
        $this->objectNormalizer->expects(self::never())
            ->method('normalizeObjects');

        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWhenNullData()
    {
        $this->objectNormalizer->expects(self::never())
            ->method('normalizeObjects');

        $this->context->setResult(null);
        $this->processor->process($this->context);
        self::assertNull($this->context->getResult());
    }

    public function testProcess()
    {
        $data = new \stdClass();
        $normalizedData = ['key' => 'value'];
        $config = new EntityDefinitionConfig();

        $this->objectNormalizer->expects(self::once())
            ->method('normalizeObjects')
            ->with([$data], $config, $this->context->getNormalizationContext())
            ->willReturn([$normalizedData]);

        $this->context->setConfig($config);
        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertSame($normalizedData, $this->context->getResult());
    }
}
