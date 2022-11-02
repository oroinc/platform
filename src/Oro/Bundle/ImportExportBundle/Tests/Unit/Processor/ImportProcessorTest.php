<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Processor\ContextAwareProcessor;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;
use Oro\Bundle\ImportExportBundle\Serializer\SerializerInterface;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\Stub\EntityNameAwareDataConverter;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Stub\EntityNameAwareStrategy;
use Oro\Component\Testing\ReflectionUtil;

class ImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $serializer;

    /** @var Context|\PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    /** @var ContextAwareProcessor */
    protected $processor;

    private array $item = ['test' => 'test'];
    private \stdClass $object;

    protected function setUp(): void
    {
        $this->object = new \stdClass();
        $this->context = $this->createMock(Context::class);
        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->processor = new ImportProcessor();
        $this->processor->setSerializer($this->serializer);
        $this->processor->setImportExportContext($this->context);
    }

    private function setProcessExpects(): void
    {
        $this->context->expects(self::once())
            ->method('getOption')
            ->with('entityName')
            ->willReturn(\stdClass::class);

        $this->context->expects(self::any())
            ->method('setValue')
            ->withConsecutive(
                ['rawItemData', self::anything()],
                ['itemData', $this->item]
            );

        $this->context->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([]);

        $this->serializer->expects(self::once())
            ->method('denormalize')
            ->with($this->item, \stdClass::class, '')
            ->willReturn($this->object);
    }

    public function testProcessMinimum(): void
    {
        $this->setProcessExpects();

        self::assertEquals($this->object, $this->processor->process($this->item));
    }

    public function testProcess(): void
    {
        $this->setProcessExpects();

        $this->context->expects(self::never())
            ->method('addFailureException');

        $converter = $this->createMock(DataConverterInterface::class);
        $converter->expects(self::once())
            ->method('convertToImportFormat')
            ->with($this->item)
            ->willReturnArgument(0);

        $strategy = $this->createMock(StrategyInterface::class);
        $strategy->expects(self::once())
            ->method('process')
            ->with($this->object)
            ->willReturnArgument(0);

        $this->processor->setDataConverter($converter);
        $this->processor->setStrategy($strategy);
        self::assertEquals($this->object, $this->processor->process($this->item));
    }

    public function testSetEntityName(): void
    {
        $entityName = 'TestEntity';

        $dataConverter = $this->createMock(EntityNameAwareDataConverter::class);
        $dataConverter->expects(self::once())
            ->method('setEntityName')
            ->with($entityName);

        $strategy = $this->createMock(EntityNameAwareStrategy::class);
        $strategy->expects(self::once())
            ->method('setEntityName')
            ->with($entityName);

        $this->processor->setDataConverter($dataConverter);
        $this->processor->setStrategy($strategy);
        $this->processor->setEntityName($entityName);

        self::assertEquals($entityName, ReflectionUtil::getPropertyValue($this->processor, 'entityName'));
    }
}
