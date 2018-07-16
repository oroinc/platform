<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\IntegrationBundle\ImportExport\DataConverter\AbstractTreeDataConverter;

class AbstractTreeDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractTreeDataConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dataConverter;

    protected function setUp()
    {
        $this->dataConverter = $this->getMockForAbstractClass(
            'Oro\Bundle\IntegrationBundle\ImportExport\DataConverter\AbstractTreeDataConverter'
        );
    }

    protected function tearDown()
    {
        unset($this->dataConverter);
    }

    public function testSetImportExportContext()
    {
        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');

        $awareConverter = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\ImportExport\DataConverter\IntegrationAwareDataConverter')
            ->setMethods(['setImportExportContext', 'convertToImportFormat', 'convertToExportFormat'])
            ->getMockForAbstractClass();
        $awareConverter->expects($this->once())
            ->method('setImportExportContext')
            ->with($context);
        $simpleConverter = $this->createMock('Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface');

        $this->dataConverter->addNodeDataConverter('test1', $awareConverter);
        $this->dataConverter->addNodeDataConverter('test2', $simpleConverter);

        $this->dataConverter->setImportExportContext($context);
    }

    /**
     * @dataProvider importDataDataProvider
     * @param bool $isMany
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
            'key' => 'cKey',
            $nodeKey => 'nKey'
        ];
        $nodeDataConverter = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface')
            ->setMethods(['convertToImportFormat'])
            ->getMockForAbstractClass();
        $this->dataConverter->expects($this->once())
            ->method('getHeaderConversionRules')
            ->will($this->returnValue($rules));

        if ($isMany) {
            $rowsCount = count($input[$nodeKey]);
            $nodeDataConverter->expects($this->exactly($rowsCount))
                ->method('convertToImportFormat')
                ->will($this->returnValue($converted));
            for ($i = 0; $i < $rowsCount; $i++) {
                $nodeDataConverter->expects($this->at($i))
                    ->method('convertToImportFormat')
                    ->with($input[$nodeKey][$i]);
            }
        } else {
            $nodeDataConverter->expects($this->once())
                ->method('convertToImportFormat')
                ->with($input[$nodeKey])
                ->will($this->returnValue($converted));
        }

        $this->dataConverter->addNodeDataConverter($nodeKey, $nodeDataConverter, $isMany);

        $this->assertEquals($expected, $this->dataConverter->convertToImportFormat($input));
    }

    /**
     * @return array
     */
    public function importDataDataProvider()
    {
        return [
            [
                false,
                [
                    'key' => 'val',
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
                    'key' => 'val',
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
     * @param bool $isMany
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
            'key' => 'cKey',
            $nodeKey => 'nKey'
        ];
        $nodeDataConverter = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface')
            ->setMethods(['convertToImportFormat'])
            ->getMockForAbstractClass();
        $this->dataConverter->expects($this->atLeastOnce())
            ->method('getHeaderConversionRules')
            ->will($this->returnValue($rules));
        $this->dataConverter->expects($this->once())
            ->method('getBackendHeader')
            ->will($this->returnValue(array_values($rules)));

        if ($isMany) {
            $rowsCount = count($input[$rules[$nodeKey]]);
            $nodeDataConverter->expects($this->exactly($rowsCount))
                ->method('convertToExportFormat')
                ->will($this->returnValue($converted));
            for ($i = 0; $i < $rowsCount; $i++) {
                $nodeDataConverter->expects($this->at($i))
                    ->method('convertToExportFormat')
                    ->with($input[$rules[$nodeKey]][$i]);
            }
        } else {
            $nodeDataConverter->expects($this->once())
                ->method('convertToExportFormat')
                ->with($input[$rules[$nodeKey]])
                ->will($this->returnValue($converted));
        }

        $this->dataConverter->addNodeDataConverter($nodeKey, $nodeDataConverter, $isMany);

        $this->assertEquals($expected, $this->dataConverter->convertToExportFormat($input));
    }

    /**
     * @return array
     */
    public function exportDataDataProvider()
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
                    'key' => 'val',
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
                    'key' => 'val',
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
