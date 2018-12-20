<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Liuggio\ExcelBundle\Factory;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\XlsxFileReader;

class XlsxFileReaderTest extends \PHPUnit\Framework\TestCase
{
    const MOCK_FILE_NAME = 'mock_file_for_initialize.xlsx';

    /** @var XlsxFileReader */
    protected $reader;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextRegistry;

    /** @var  Factory|\PHPUnit\Framework\MockObject\MockObject */
    protected $phpExcelFactory;

    /** @var \PHPExcel|\PHPUnit\Framework\MockObject\MockObject */
    protected $phpExcel;

    /** @var  \PHPExcel_Worksheet|\PHPUnit\Framework\MockObject\MockObject */
    protected $phpExcelWorksheet;

    /** @var \PHPExcel_Worksheet_RowIterator|\PHPUnit\Framework\MockObject\MockObject */
    protected $phpExcelWorksheetRowIterator;

    /** @var \PHPExcel_Worksheet_Row|\PHPUnit\Framework\MockObject\MockObject */
    protected $phpExcelWorksheetRow;

    /** @var \PHPExcel_Worksheet_RowCellIterator|\PHPUnit\Framework\MockObject\MockObject */
    protected $phpExcelWorksheetRowCellIterator;

    /** @var \PHPExcel_Cell|\PHPUnit\Framework\MockObject\MockObject  */
    protected $phpExcelCell;

    /** @var int */
    protected $readCount;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->phpExcelCell = $this->createMock(\PHPExcel_Cell::class);

        $this->phpExcelWorksheetRowCellIterator = $this->createMock(\PHPExcel_Worksheet_RowCellIterator::class);

        $this->phpExcelWorksheetRow = $this->createMock(\PHPExcel_Worksheet_Row::class);
        $this->phpExcelWorksheetRow
             ->expects($this->any())
             ->method('getCellIterator')
             ->willReturn($this->phpExcelWorksheetRowCellIterator);

        $this->phpExcelWorksheetRowIterator = $this->createMock(\PHPExcel_Worksheet_RowIterator::class);
        $this->phpExcelWorksheetRowIterator
             ->expects($this->any())
             ->method('current')
             ->willReturn($this->phpExcelWorksheetRow);

        $this->phpExcelWorksheet = $this->createMock(\PHPExcel_Worksheet::class);
        $this->phpExcelWorksheet
             ->expects($this->any())
             ->method('getRowIterator')
             ->willReturn($this->phpExcelWorksheetRowIterator);

        $this->phpExcel = $this->createMock(\PHPExcel::class);
        $this->phpExcel
             ->expects($this->any())
             ->method('getActiveSheet')
             ->willReturn($this->phpExcelWorksheet);

        $this->phpExcelFactory = $this->createMock(Factory::class);
        $this->phpExcelFactory
             ->expects($this->any())
             ->method('createPHPExcelObject')
             ->willReturn($this->phpExcel);

        $this->reader = new XlsxFileReader($this->contextRegistry, $this->phpExcelFactory);
    }

    /** {@inheritdoc} */
    public function tearDown()
    {
        unset(
            $this->reader,
            $this->contextRegistry,
            $this->phpExelFactory,
            $this->phpExel,
            $this->phpExcelWorksheet,
            $this->phpExcelWorksheetRowIterator,
            $this->phpExcelWorksheetRow,
            $this->phpExcelWorksheetRowCellIterator,
            $this->phpExcelCell,
            $this->readCount
        );

        parent::tearDown();
    }

    /**
     * @dataProvider excelDataProvider
     *
     * @param array $options
     * @param array $returned
     * @param int   $rows
     * @param int   $cells
     * @param array $expected
     */
    public function testEnsureThatHeaderIsCleared(
        $options,
        $returned,
        $rows,
        $cells,
        $expected
    ) {
        $this->readCount = 0;

        $this->prepareExcelIteratorsMock($returned, $rows, $cells);

        $context = $this->getContextWithOptionsMock($options);

        $stepExecution = $this->getMockStepExecution($context);

        $this->reader->setStepExecution($stepExecution);
        $this->reader->initializeByContext($context);


        $stepExecution->expects($this->never())
                      ->method('addReaderWarning');
        //ensure that header is cleared before read
        $this->assertNull($this->reader->getHeader());

        $data = [];
        while (($dataRow = $this->reader->read($stepExecution)) !== null) {
            $data[] = $dataRow;
        }

        $this->assertNull($this->reader->getHeader()); //ensured that previous data was cleared
        $this->assertEquals($expected, $data);
    }

    /** @return array */
    public function excelDataProvider()
    {
        return [
            [
                'option' => ['filePath' => __DIR__ . '/fixtures/' . self::MOCK_FILE_NAME],
                'returned' => [
                    'field_one',
                    'field_two',
                    'field_three',
                    'field_four',
                    '1',
                    '2',
                    '3',
                    '4',
                    'test1',
                    'test2',
                    'test3',
                    'test4',
                    null,
                    null,
                    null,
                    null,
                    'after_new1',
                    'after_new2',
                    'after_new3',
                    'after_new4',
                ],
                'rows' => 5,
                'cells' => 4,
                'expected' => [
                    [1, 2, 3, 4],
                    ['test1', 'test2', 'test3', 'test4'],
                    [null, null, null, null],
                    ['after_new1', 'after_new2', 'after_new3', 'after_new4'],
                ]
            ]
        ];
    }

    /**
     * @param array $data
     * @param int $rows
     * @param int $cells
     */
    protected function prepareExcelIteratorsMock(
        array $data,
        $rows,
        $cells
    ) {
        foreach ($data as $key => $item) {
            $this->phpExcelCell
                ->expects($this->at($key))
                ->method('getValue')
                ->willReturn($item);
        }

        $this->prepareRowIteratorMock($rows);
        $this->prepareCellIteratorMock($cells);
    }

    protected function prepareCellIteratorMock($cells)
    {
        $cellIteratorData = new \stdClass();
        $cellIteratorData->position = 0;
        $cellIteratorData->array = [];

        for ($i = 0; $i < $cells; $i++) {
            $cellIteratorData->array[] = $this->phpExcelCell;
        }

        $this->phpExcelWorksheetRowCellIterator
            ->expects($this->any())
            ->method('valid')
            ->will(
                $this->returnCallback(
                    function () use ($cellIteratorData) {
                        return isset($cellIteratorData->array[$cellIteratorData->position]);
                    }
                )
            );

        $this->phpExcelWorksheetRowCellIterator
            ->expects($this->any())
            ->method('next')
            ->will(
                $this->returnCallback(
                    function () use ($cellIteratorData) {
                        return $cellIteratorData->position++;
                    }
                )
            );

        $this->phpExcelWorksheetRowCellIterator
            ->expects($this->any())
            ->method('current')
            ->will(
                $this->returnCallback(
                    function () use ($cellIteratorData) {
                        return $cellIteratorData->array[$cellIteratorData->position];
                    }
                )
            );

        $this->phpExcelWorksheetRowCellIterator
            ->expects($this->any())
            ->method('rewind')
            ->will(
                $this->returnCallback(
                    function () use ($cellIteratorData) {
                        return $cellIteratorData->position = 0;
                    }
                )
            );
    }

    /**
     * @param int $rows
     */
    protected function prepareRowIteratorMock($rows)
    {
        $rowIteratorData = new \stdClass();
        $rowIteratorData->position = 0;
        $rowIteratorData->array = [];

        for ($i = 0; $i < $rows; $i++) {
            $rowIteratorData->array[] = $this->phpExcelWorksheetRow;
        }

        $this->phpExcelWorksheetRowIterator
            ->expects($this->any())
            ->method('valid')
            ->will(
                $this->returnCallback(
                    function () use ($rowIteratorData) {
                        return isset($rowIteratorData->array[$rowIteratorData->position]);
                    }
                )
            );

        $this->phpExcelWorksheetRowIterator
            ->expects($this->any())
            ->method('rewind')
            ->will(
                $this->returnCallback(
                    function () use ($rowIteratorData) {
                        return $rowIteratorData->position = 0;
                    }
                )
            );

        $this->phpExcelWorksheetRowIterator
            ->expects($this->any())
            ->method('resetStart')
            ->will(
                $this->returnCallback(
                    function () use ($rowIteratorData) {
                        return $rowIteratorData->position = 0;
                    }
                )
            );

        $this->phpExcelWorksheetRowIterator
            ->expects($this->any())
            ->method('current')
            ->will(
                $this->returnCallback(
                    function () use ($rowIteratorData) {
                        return $rowIteratorData->array[$rowIteratorData->position];
                    }
                )
            );
        $this->phpExcelWorksheetRowIterator
            ->expects($this->any())
            ->method('next')
            ->will(
                $this->returnCallback(
                    function () use ($rowIteratorData) {
                        $rowIteratorData->position++;
                    }
                )
            );
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $context
     * @return \PHPUnit\Framework\MockObject\MockObject|StepExecution
     */
    protected function getMockStepExecution($context)
    {
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->setMethods(['addReaderWarning', 'getByStepExecution'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        return $stepExecution;
    }

    protected function getContextWithOptionsMock($options)
    {
        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->any())
            ->method('hasOption')
            ->will(
                $this->returnCallback(
                    function ($option) use ($options) {
                        return isset($options[$option]);
                    }
                )
            );
        $context->expects($this->any())
            ->method('getOption')
            ->will(
                $this->returnCallback(
                    function ($option) use ($options) {
                        return $options[$option];
                    }
                )
            );

        $context->expects($this->any())
            ->method('getReadCount')
            ->will(
                $this->returnCallback(
                    function () {
                        return ++$this->readCount;
                    }
                )
            );

        return $context;
    }
}
