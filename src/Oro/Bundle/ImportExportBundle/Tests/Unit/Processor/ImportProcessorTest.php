<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\Stub\EntityNameAwareDataConverter;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Serializer\SerializerInterface;

class ImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImportProcessor */
    protected $processor;

    /** @var SerializerInterface|MockObject */
    protected $serializer;

    /** @var Context|MockObject */
    protected $context;

    /** @var array */
    protected $item = ['test' => 'test'];

    /** @var object */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new \stdClass();
        $this->context = $this->createMock(Context::class);
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)->getMockForAbstractClass();

        $this->processor = new class() extends ImportProcessor {
            public function xgetEntityName(): string
            {
                return $this->entityName;
            }
        };

        $this->processor->setSerializer($this->serializer);
        $this->processor->setImportExportContext($this->context);
    }

    protected function setProcessExpects()
    {
        $this->context->expects(static::once())
            ->method('getOption')
            ->with('entityName')
            ->willReturn('\stdClass');
        $this->context->method('setValue')
            ->withConsecutive(
                ['rawItemData', static::anything()],
                ['itemData', $this->item]
            );
        $this->context->expects(static::any())->method('getConfiguration')->willReturn([]);

        $this->serializer->expects(static::once())
            ->method('deserialize')
            ->with($this->item, '\stdClass', null)
            ->willReturn($this->object);
    }

    public function testProcessMinimum()
    {
        $this->setProcessExpects();

        static::assertEquals($this->object, $this->processor->process($this->item));
    }

    public function testProcess()
    {
        $this->setProcessExpects();

        $this->context->expects(static::never())->method('addFailureException');

        /** @var DataConverterInterface|MockObject $converter */
        $converter = $this->getMockBuilder(DataConverterInterface::class)
            ->onlyMethods(['convertToImportFormat'])
            ->getMockForAbstractClass();
        $converter->expects(static::once())
            ->method('convertToImportFormat')
            ->with($this->item)
            ->willReturnArgument(0);

        /** @var StrategyInterface|MockObject $strategy */
        $strategy = $this->getMockBuilder(StrategyInterface::class)
            ->onlyMethods(['process'])
            ->getMockForAbstractClass();
        $strategy->expects(static::once())
            ->method('process')
            ->with($this->object)
            ->willReturnArgument(0);

        $this->processor->setDataConverter($converter);
        $this->processor->setStrategy($strategy);
        static::assertEquals($this->object, $this->processor->process($this->item));
    }

    public function testSetEntityName()
    {
        $entityName = 'TestEntity';

        $dataConverter = $this->createMock(EntityNameAwareDataConverter::class);
        $dataConverter->expects(static::once())->method('setEntityName')->with($entityName);

        $strategy = $this->createMock('Oro\Bundle\ImportExportBundle\Tests\Unit\Strategy\Stub\EntityNameAwareStrategy');
        $strategy->expects(static::once())->method('setEntityName')->with($entityName);

        $this->processor->setDataConverter($dataConverter);
        $this->processor->setStrategy($strategy);
        $this->processor->setEntityName($entityName);

        static::assertEquals($entityName, $this->processor->xgetEntityName());
    }
}
