<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\ImportExport\DataConverter\IntegrationAwareDataConverter;

class IntegrationAwareDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var IntegrationAwareDataConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $dataConverter;

    protected function setUp(): void
    {
        $this->dataConverter = $this->getMockForAbstractClass(IntegrationAwareDataConverter::class);
    }

    /**
     * @dataProvider inputDataProvider
     */
    public function testConvertToImportFormat(array $input, array $expected, ?ContextInterface $context)
    {
        $this->dataConverter->expects($this->once())
            ->method('getHeaderConversionRules')
            ->willReturn(['key' => 'cKey']);

        if ($context) {
            $this->dataConverter->setImportExportContext($context);
        }
        $this->assertEquals($expected, $this->dataConverter->convertToImportFormat($input));
    }

    public function inputDataProvider(): array
    {
        $emptyContext = $this->createMock(ContextInterface::class);
        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->any())
            ->method('hasOption')
            ->with('channel')
            ->willReturn(true);
        $context->expects($this->any())
            ->method('getOption')
            ->with('channel')
            ->willReturn(2);

        return [
            [
                ['key' => 'val'],
                ['cKey' => 'val'],
                null
            ],
            [
                ['key' => 'val'],
                ['cKey' => 'val'],
                $emptyContext
            ],
            [
                ['key' => 'val'],
                ['cKey' => 'val', 'channel' => ['id' => 2]],
                $context
            ]
        ];
    }
}
