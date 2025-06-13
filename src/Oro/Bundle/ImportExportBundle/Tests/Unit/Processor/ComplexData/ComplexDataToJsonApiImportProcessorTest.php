<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor\ComplexData;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\JsonApiImportConverter;
use Oro\Bundle\ImportExportBundle\Processor\ComplexData\ComplexDataToJsonApiImportProcessor;
use PHPUnit\Framework\TestCase;

class ComplexDataToJsonApiImportProcessorTest extends TestCase
{
    public function testProcess(): void
    {
        $dataConverter = $this->createMock(JsonApiImportConverter::class);
        $processor = new ComplexDataToJsonApiImportProcessor($dataConverter);

        $data = ['data'];
        $expected = ['converted' => 'data'];

        $dataConverter->expects($this->once())
            ->method('convert')
            ->with($data)
            ->willReturn($expected);

        self::assertEquals($expected, $processor->process($data));
    }
}
