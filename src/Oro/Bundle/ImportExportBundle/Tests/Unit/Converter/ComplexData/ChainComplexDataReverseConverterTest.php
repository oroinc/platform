<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ChainComplexDataReverseConverter;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataReverseConverterInterface;
use PHPUnit\Framework\TestCase;

class ChainComplexDataReverseConverterTest extends TestCase
{
    public function testReverseConvert(): void
    {
        $sourceEntity = new \stdClass();

        $converter1 = $this->createMock(ComplexDataReverseConverterInterface::class);
        $converter1->expects(self::once())
            ->method('reverseConvert')
            ->with(['key' => 'value'], self::identicalTo($sourceEntity))
            ->willReturn(['key' => 'value', 'key1' => 'value1']);

        $converter2 = $this->createMock(ComplexDataReverseConverterInterface::class);
        $converter2->expects(self::once())
            ->method('reverseConvert')
            ->with(['key' => 'value', 'key1' => 'value1'], self::identicalTo($sourceEntity))
            ->willReturn(['key' => 'value', 'key1' => 'value1', 'key2' => 'value2']);

        $chainConverter = new ChainComplexDataReverseConverter([$converter1, $converter2]);

        self::assertEquals(
            ['key' => 'value', 'key1' => 'value1', 'key2' => 'value2'],
            $chainConverter->reverseConvert(['key' => 'value'], $sourceEntity)
        );
    }
}
