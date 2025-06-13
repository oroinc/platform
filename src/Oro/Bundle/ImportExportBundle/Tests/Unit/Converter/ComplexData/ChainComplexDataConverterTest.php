<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ChainComplexDataConverter;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterInterface;
use PHPUnit\Framework\TestCase;

class ChainComplexDataConverterTest extends TestCase
{
    public function testConvert(): void
    {
        $sourceData = 'sourceData';

        $converter1 = $this->createMock(ComplexDataConverterInterface::class);
        $converter1->expects(self::once())
            ->method('convert')
            ->with(['key' => 'value'], $sourceData)
            ->willReturn(['key' => 'value', 'key1' => 'value1']);

        $converter2 = $this->createMock(ComplexDataConverterInterface::class);
        $converter2->expects(self::once())
            ->method('convert')
            ->with(['key' => 'value', 'key1' => 'value1'], $sourceData)
            ->willReturn(['key' => 'value', 'key1' => 'value1', 'key2' => 'value2']);

        $chainConverter = new ChainComplexDataConverter([$converter1, $converter2]);

        self::assertEquals(
            ['key' => 'value', 'key1' => 'value1', 'key2' => 'value2'],
            $chainConverter->convert(['key' => 'value'], $sourceData)
        );
    }
}
