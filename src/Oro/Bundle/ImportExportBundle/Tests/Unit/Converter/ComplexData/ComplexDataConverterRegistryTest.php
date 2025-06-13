<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ChainComplexDataConverter;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ChainComplexDataErrorConverter;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ChainComplexDataReverseConverter;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterRegistry;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataErrorConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataReverseConverterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ComplexDataConverterRegistryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private ComplexDataConverterRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);

        $this->registry = new ComplexDataConverterRegistry(
            [
                ['converter_service_1', 'entity_type_1'],
                ['converter_service_2', 'entity_type_1'],
                ['converter_service_3', 'entity_type_2'],
                ['converter_service_4', 'entity_type_2']
            ],
            $this->container
        );
    }

    public function testGetConverterForEntityWhenNoConverter(): void
    {
        $this->container->expects(self::never())
            ->method('get');

        self::assertNull($this->registry->getConverterForEntity('another_entity_type'));
    }

    public function testGetConverterForEntityWhenOneConverter(): void
    {
        $converter = $this->createMock(ComplexDataConverterInterface::class);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['converter_service_3'], ['converter_service_4'])
            ->willReturnOnConsecutiveCalls($converter, $this->createMock(ComplexDataReverseConverterInterface::class));

        self::assertSame($converter, $this->registry->getConverterForEntity('entity_type_2'));
    }

    public function testGetConverterForEntityWhenSeveralConverters(): void
    {
        $converter1 = $this->createMock(ComplexDataConverterInterface::class);
        $converter2 = $this->createMock(ComplexDataConverterInterface::class);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['converter_service_1'], ['converter_service_2'])
            ->willReturnOnConsecutiveCalls($converter1, $converter2);

        $converter = $this->registry->getConverterForEntity('entity_type_1');
        self::assertEquals(
            new ChainComplexDataConverter([$converter1, $converter2]),
            $converter
        );
    }

    public function testGetReverseConverterForEntityWhenNoConverter(): void
    {
        $this->container->expects(self::never())
            ->method('get');

        self::assertNull($this->registry->getReverseConverterForEntity('another_entity_type'));
    }

    public function testGetReverseConverterForEntityWhenOneConverter(): void
    {
        $converter = $this->createMock(ComplexDataReverseConverterInterface::class);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['converter_service_3'], ['converter_service_4'])
            ->willReturnOnConsecutiveCalls($converter, $this->createMock(ComplexDataConverterInterface::class));

        self::assertSame($converter, $this->registry->getReverseConverterForEntity('entity_type_2'));
    }

    public function testGetReverseConverterForEntityWhenSeveralConverters(): void
    {
        $converter1 = $this->createMock(ComplexDataReverseConverterInterface::class);
        $converter2 = $this->createMock(ComplexDataReverseConverterInterface::class);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['converter_service_1'], ['converter_service_2'])
            ->willReturnOnConsecutiveCalls($converter1, $converter2);

        $converter = $this->registry->getReverseConverterForEntity('entity_type_1');
        self::assertEquals(
            new ChainComplexDataReverseConverter([$converter1, $converter2]),
            $converter
        );
    }

    public function testGetErrorConverterForEntityWhenNoConverter(): void
    {
        $this->container->expects(self::never())
            ->method('get');

        self::assertNull($this->registry->getErrorConverterForEntity('another_entity_type'));
    }

    public function testGetErrorConverterForEntityWhenOneConverter(): void
    {
        $converter = $this->createMock(ComplexDataErrorConverterInterface::class);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['converter_service_3'], ['converter_service_4'])
            ->willReturnOnConsecutiveCalls($converter, $this->createMock(ComplexDataConverterInterface::class));

        self::assertSame($converter, $this->registry->getErrorConverterForEntity('entity_type_2'));
    }

    public function testGetErrorConverterForEntityWhenSeveralConverters(): void
    {
        $converter1 = $this->createMock(ComplexDataErrorConverterInterface::class);
        $converter2 = $this->createMock(ComplexDataErrorConverterInterface::class);

        $this->container->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(['converter_service_1'], ['converter_service_2'])
            ->willReturnOnConsecutiveCalls($converter1, $converter2);

        $converter = $this->registry->getErrorConverterForEntity('entity_type_1');
        self::assertEquals(
            new ChainComplexDataErrorConverter([$converter1, $converter2]),
            $converter
        );
    }
}
