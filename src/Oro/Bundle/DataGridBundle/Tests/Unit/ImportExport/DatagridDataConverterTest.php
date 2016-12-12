<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\ImportExport;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridDataConverter;
use Oro\Bundle\DataGridBundle\Tools\ColumnsHelper;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class DatagridDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
    protected $datagridManager;

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var ColumnsHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $columnsHelper;

    /** @var DatagridDataConverter */
    protected $datagridDataConverter;

    /** @var Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    public function setUp()
    {
        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datagridManager = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->columnsHelper = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Tools\ColumnsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $formatterProvider = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $serviceLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->datagridManager);

        $this->datagridDataConverter = new DatagridDataConverter(
            $serviceLink,
            $this->translator,
            $this->columnsHelper,
            $formatterProvider
        );
    }

    /**
     * array $columns
     * array $exportedRecord
     * array $gridParameters
     * array $expected
     *
     * @dataProvider convertDataProvider
     */
    public function testConvertToExportFormat($columns, $exportedRecord, $gridParameters, $expected)
    {
        $this->context->expects($this->any())
            ->method('getValue')
            ->with('columns')
            ->willReturn($columns);

        if (empty($gridParameters)) {
            $this->context->expects($this->any())
                ->method('hasOption')
                ->with('gridParameters')
                ->willReturn(false);
        }

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnCallback(
                function ($parameter) {
                    return $parameter;
                }
            ));

        $this->datagridDataConverter->setImportExportContext($this->context);
        $result = $this->datagridDataConverter->convertToExportFormat($exportedRecord);
        $this->assertEquals($expected, $result);
    }

    public function convertDataProvider()
    {
        return [
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
                'grid_parameters' => [],
                'expectedResult' => [
                    'Primary Email' => 'test1@test.com',
                    'Primary Email_c2' => 'test2@test.com',
                    'Primary Email_c3' => null,
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
                'grid_parameters' => [],
                'expectedResult' => [
                    'Primary Email' => 'test1@test.com',
                    'Primary Phone' => '08567845678',
                    'Status' => 'new'
                ]
            ]
        ];
    }
}
