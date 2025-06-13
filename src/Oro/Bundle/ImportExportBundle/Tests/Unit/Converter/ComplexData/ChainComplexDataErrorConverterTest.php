<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ChainComplexDataErrorConverter;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataErrorConverterInterface;
use PHPUnit\Framework\TestCase;

class ChainComplexDataErrorConverterTest extends TestCase
{
    public function testConvertError(): void
    {
        $converter1 = $this->createMock(ComplexDataErrorConverterInterface::class);
        $converter1->expects(self::once())
            ->method('convertError')
            ->with('error', 'propertyPath')
            ->willReturn('error 1');

        $converter2 = $this->createMock(ComplexDataErrorConverterInterface::class);
        $converter2->expects(self::once())
            ->method('convertError')
            ->with('error 1', 'propertyPath')
            ->willReturn('error 2');

        $chainConverter = new ChainComplexDataErrorConverter([$converter1, $converter2]);

        self::assertEquals(
            'error 2',
            $chainConverter->convertError('error', 'propertyPath')
        );
    }
}
