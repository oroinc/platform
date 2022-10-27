<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Oro\Bundle\DataGridBundle\ImportExport\DatagridColumnsFromContextProviderInterface;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridDataConverter;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class DatagridDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridColumnsFromContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $datagridColumnsFromContextProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var Context|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var DatagridDataConverter */
    private $datagridDataConverter;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->datagridColumnsFromContextProvider = $this->createMock(
            DatagridColumnsFromContextProviderInterface::class
        );
        $this->context = $this->createMock(Context::class);

        $this->datagridDataConverter = new DatagridDataConverter(
            $this->datagridColumnsFromContextProvider,
            $this->translator,
            $this->createMock(FormatterProvider::class)
        );
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvertToExportFormat(array $columns, array $exportedRecord, array $expected): void
    {
        $this->datagridColumnsFromContextProvider->expects(self::any())
            ->method('getColumnsFromContext')
            ->with($this->context)
            ->willReturn($columns);

        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->datagridDataConverter->setImportExportContext($this->context);
        $result = $this->datagridDataConverter->convertToExportFormat($exportedRecord);
        self::assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function convertDataProvider(): array
    {
        return [
            'Columns with html type' => [
                'columns' => [
                    'c1' => [
                        'label' => 'Label',
                        'frontend_type' => 'html'
                    ],
                ],
                'exported_record' => [
                    'c1' => 'Carte d&#039;identité',
                    'id' => 1
                ],
                'expectedResult' => [
                    'Label' => 'Carte d\'identité',
                ]
            ],
            'Columns with same labels' => [
                'columns' => [
                    'c1' => [
                        'label' => 'Primary Email',
                        'frontend_type' => 'string'
                    ],
                    'c2' => [
                        'label' => 'Primary Email',
                        'frontend_type' => 'string'
                    ],
                    'c3' => [
                        'label' => 'Primary Email',
                        'frontend_type' => 'string'
                    ]
                ],
                'exported_record' => [
                    'c1' => 'test1@test.com',
                    'c2' => 'test2@test.com',
                    'c3' => null,
                    'id' => 3
                ],
                'expectedResult' => [
                    'Primary Email' => 'test1@test.com',
                    'Primary Email_c2' => 'test2@test.com',
                    'Primary Email_c3' => null,
                ]
            ],
            'Columns with same labels and empty values' => [
                'columns' => [
                    'c1' => [
                        'label' => 'Primary Email',
                        'frontend_type' => 'string'
                    ],
                    'c2' => [
                        'label' => 'Primary Email',
                        'frontend_type' => 'string'
                    ],
                    'c3' => [
                        'label' => 'Primary Email',
                        'frontend_type' => 'string'
                    ]
                ],
                'exported_record' => [
                    'c1' => null,
                    'c2' => 'test2@test.com',
                    'c3' => '',
                    'id' => 3
                ],
                'expectedResult' => [
                    'Primary Email' => null,
                    'Primary Email_c2' => 'test2@test.com',
                    'Primary Email_c3' => '',
                ]
            ],
            'Columns with different labels' => [
                'columns' => [
                    'c1' => [
                        'label' => 'Primary Email',
                        'frontend_type' => 'string'
                    ],
                    'c2' => [
                        'label' => 'Primary Phone',
                        'frontend_type' => 'string'
                    ],
                    'c3' => [
                        'label' => 'Status',
                        'frontend_type' => 'string'
                    ]
                ],
                'exported_record' => [
                    'c1' => 'test1@test.com',
                    'c2' => '08567845678',
                    'c3' => 'new',
                    'id' => 3
                ],
                'expectedResult' => [
                    'Primary Email' => 'test1@test.com',
                    'Primary Phone' => '08567845678',
                    'Status' => 'new'
                ]
            ]
        ];
    }
}
