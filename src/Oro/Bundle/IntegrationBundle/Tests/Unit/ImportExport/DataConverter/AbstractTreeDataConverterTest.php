<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\IntegrationBundle\ImportExport\DataConverter\AbstractTreeDataConverter;
use Oro\Bundle\IntegrationBundle\ImportExport\DataConverter\IntegrationAwareDataConverter;

class AbstractTreeDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractTreeDataConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $dataConverter;

    protected function setUp(): void
    {
        $this->dataConverter = $this->getMockForAbstractClass(AbstractTreeDataConverter::class);
    }

    public function testSetImportExportContext()
    {
        $context = $this->createMock(ContextInterface::class);

        $awareConverter = $this->createMock(IntegrationAwareDataConverter::class);
        $awareConverter->expects($this->once())
            ->method('setImportExportContext')
            ->with($context);
        $simpleConverter = $this->createMock(DataConverterInterface::class);

        $this->dataConverter->addNodeDataConverter('test1', $awareConverter);
        $this->dataConverter->addNodeDataConverter('test2', $simpleConverter);

        $this->dataConverter->setImportExportContext($context);
    }

    /**
     * @dataProvider importDataDataProvider
     *
     * @param bool  $isMany
     * @param array $input
     * @param array $expected
     */
    public function testConvertToImportFormat($isMany, array $input, array $expected)
    {
        $nodeKey = 'test_key';
        $converted = [
            'key1' => 'val1',
            'key2' => 'val2'
        ];
        $rules = [
            'key'    => 'cKey',
            $nodeKey => 'nKey'
        ];
        $nodeDataConverter = $this->createMock(DataConverterInterface::class);
        $this->dataConverter->expects($this->once())
            ->method('getHeaderConversionRules')
            ->willReturn($rules);

        if ($isMany) {
            $nodeDataConverter->expects($this->exactly(count($input[$nodeKey])))
                ->method('convertToImportFormat')
                ->willReturn($converted);
            $nodes = [];
            foreach ($input[$nodeKey] as $node) {
                $nodes[] = [$node];
            }
            $nodeDataConverter->expects($this->exactly(count($input[$nodeKey])))
                ->method('convertToImportFormat')
                ->withConsecutive(...$nodes);
        } else {
            $nodeDataConverter->expects($this->once())
                ->method('convertToImportFormat')
                ->with($input[$nodeKey])
                ->willReturn($converted);
        }

        $this->dataConverter->addNodeDataConverter($nodeKey, $nodeDataConverter, $isMany);

        $this->assertEquals($expected, $this->dataConverter->convertToImportFormat($input));
    }

    public function importDataDataProvider(): array
    {
        return [
            [
                false,
                [
                    'key'      => 'val',
                    'test_key' => [
                        'key_1' => 'val1'
                    ]
                ],
                [
                    'cKey' => 'val',
                    'nKey' => [
                        'key1' => 'val1',
                        'key2' => 'val2'
                    ]
                ]
            ],
            [
                true,
                [
                    'key'      => 'val',
                    'test_key' => [
                        ['key_1' => 'val1'],
                        ['key_1' => 'val2'],
                    ]
                ],
                [
                    'cKey' => 'val',
                    'nKey' => [
                        [
                            'key1' => 'val1',
                            'key2' => 'val2'
                        ],
                        [
                            'key1' => 'val1',
                            'key2' => 'val2'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider exportDataDataProvider
     *
     * @param bool  $isMany
     * @param array $input
     * @param array $expected
     */
    public function testConvertToExportFormat($isMany, array $input, array $expected)
    {
        $nodeKey = 'test_key';
        $converted = [
            'key1' => 'val1',
            'key2' => 'val2'
        ];
        $rules = [
            'key'    => 'cKey',
            $nodeKey => 'nKey'
        ];
        $nodeDataConverter = $this->createMock(DataConverterInterface::class);
        $this->dataConverter->expects($this->atLeastOnce())
            ->method('getHeaderConversionRules')
            ->willReturn($rules);
        $this->dataConverter->expects($this->once())
            ->method('getBackendHeader')
            ->willReturn(array_values($rules));

        if ($isMany) {
            $nodeDataConverter->expects($this->exactly(count($input[$rules[$nodeKey]])))
                ->method('convertToExportFormat')
                ->willReturn($converted);
            $nodes = [];
            foreach ($input[$rules[$nodeKey]] as $node) {
                $nodes[] = [$node];
            }
            $nodeDataConverter->expects($this->exactly(count($input[$rules[$nodeKey]])))
                ->method('convertToExportFormat')
                ->withConsecutive(...$nodes);
        } else {
            $nodeDataConverter->expects($this->once())
                ->method('convertToExportFormat')
                ->with($input[$rules[$nodeKey]])
                ->willReturn($converted);
        }

        $this->dataConverter->addNodeDataConverter($nodeKey, $nodeDataConverter, $isMany);

        $this->assertEquals($expected, $this->dataConverter->convertToExportFormat($input));
    }

    public function exportDataDataProvider(): array
    {
        return [
            [
                false,
                [
                    'cKey' => 'val',
                    'nKey' => [
                        'key_1' => 'val1'
                    ]
                ],
                [
                    'key'      => 'val',
                    'test_key' => [
                        'key1' => 'val1',
                        'key2' => 'val2'
                    ]
                ]
            ],
            [
                true,
                [
                    'cKey' => 'val',
                    'nKey' => [
                        ['key_1' => 'val1'],
                        ['key_1' => 'val2'],
                    ]
                ],
                [
                    'key'      => 'val',
                    'test_key' => [
                        [
                            'key1' => 'val1',
                            'key2' => 'val2'
                        ],
                        [
                            'key1' => 'val1',
                            'key2' => 'val2'
                        ]
                    ]
                ]
            ]
        ];
    }
}
