<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Liuggio\ExcelBundle\Factory;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\XlsxFileWriter;

class XlsxFileWriterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XlsxFileWriter
     */
    protected $writer;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var Factory
     */
    protected $excel;

    protected function setUp()
    {
        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->setMethods(['getByStepExecution'])
            ->getMock();

        $this->excel = new Factory();

        $this->filePath = __DIR__.'/fixtures/new_file.xlsx';
        $this->writer = new XlsxFileWriter($this->contextRegistry, $this->excel);
    }

    protected function tearDown()
    {
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration of XLSX writer must contain "filePath".
     */
    public function testSetStepExecutionNoFileException()
    {
        $this->writer->setStepExecution($this->getMockStepExecution([]));
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     */
    public function testUnknownFileException()
    {
        $this->writer->setStepExecution(
            $this->getMockStepExecution(
                [
                    'filePath' => __DIR__.'/unknown/new_file.xlsx'
                ]
            )
        );
    }

    public function testSetStepExecution()
    {
        $options = [
            'filePath'          => $this->filePath,
            'firstLineIsHeader' => false,
            'header'            => ['one', 'two']
        ];

        $this->assertAttributeEquals(true, 'firstLineIsHeader', $this->writer);
        $this->assertAttributeEmpty('header', $this->writer);

        $this->writer->setStepExecution($this->getMockStepExecution($options));

        $this->assertAttributeEquals($options['firstLineIsHeader'], 'firstLineIsHeader', $this->writer);
        $this->assertAttributeEquals($options['header'], 'header', $this->writer);
    }

    /**
     * @dataProvider optionsDataProvider
     *
     * @param array  $options
     * @param array  $data
     * @param string $expected
     */
    public function testWrite($options, $data, $expected)
    {
        $stepExecution = $this->getMockStepExecution($options);
        $this->writer->setStepExecution($stepExecution);
        $this->writer->write($data);
        $this->writer->close();

        $this->assertFileExists($expected);
        $this->assertXlsx($expected, $options['filePath']);
    }

    public function optionsDataProvider()
    {
        $filePath = __DIR__.'/fixtures/new_file.xlsx';

        return [
            'first_item_header' => [
                ['filePath' => $filePath],
                [
                    [
                        'field_one'   => '1',
                        'field_two'   => '2',
                        'field_three' => '3',
                    ],
                    [
                        'field_one'   => 'test1',
                        'field_two'   => 'test2',
                        'field_three' => 'test3',
                    ]
                ],
                __DIR__.'/fixtures/first_item_header.xlsx'
            ],
            'defined_header'    => [
                [
                    'filePath' => $filePath,
                    'header'   => ['h1', 'h2', 'h3']
                ],
                [
                    [
                        'h1' => 'field_one',
                        'h2' => 'field_two',
                        'h3' => 'field_three'
                    ]
                ],
                __DIR__.'/fixtures/defined_header.xlsx'
            ],
            'no_header'         => [
                [
                    'filePath'          => $filePath,
                    'firstLineIsHeader' => false
                ],
                [
                    ['1', '2', '3'],
                    ['test1', 'test2', 'test3']
                ],
                __DIR__.'/fixtures/no_header.xlsx'
            ]
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     *
     * @param array  $options
     * @param array  $data
     * @param string $expected
     */
    public function testWriteWithClearWriter($options, $data, $expected)
    {
        $stepExecution = $this->getMockStepExecution($options);
        $this->writer->setStepExecution($stepExecution);
        $clearWriter = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter')
            ->disableOriginalConstructor()
            ->getMock();
        $clearWriter->expects($this->once())
            ->method('write')
            ->with($data);
        $this->writer->setClearWriter($clearWriter);
        $this->writer->write($data);
        $this->writer->close();

        $this->assertFileExists($expected);
        $this->assertXlsx($expected, $options['filePath']);
    }

    /**
     * @param array $jobInstanceRawConfiguration
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|StepExecution
     */
    protected function getMockStepExecution(array $jobInstanceRawConfiguration)
    {
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext')
            ->disableOriginalConstructor()
            ->setMethods(['getConfiguration'])
            ->getMock();
        $context->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($jobInstanceRawConfiguration));

        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        return $stepExecution;
    }

    public function assertXlsx($expectedPath, $actualPath)
    {
        $expectedReader = $this->excel->createPHPExcelObject($expectedPath);
        $actualReader = $this->excel->createPHPExcelObject($actualPath);

        $expectedSheet =  $expectedReader->getActiveSheet();
        $actualSheet =  $actualReader->getActiveSheet();

        $expectedHighestRow = $expectedSheet->getHighestRow();
        $expectedHighestColumn = $expectedSheet->getHighestColumn();
        $expectedHighestColumnIndex = \PHPExcel_Cell::columnIndexFromString($expectedHighestColumn);
        $actualHighestRow = $actualSheet->getHighestRow();
        $actualHighestColumn = $actualSheet->getHighestColumn();
        $actualHighestColumnIndex = \PHPExcel_Cell::columnIndexFromString($actualHighestColumn);

        $this->assertEquals($expectedHighestRow, $actualHighestRow);
        $this->assertEquals($expectedHighestColumn, $actualHighestColumn);
        $this->assertEquals($expectedHighestColumnIndex, $actualHighestColumnIndex);

        for ($col = 0; $col < $expectedHighestColumnIndex; $col++) {
            for ($row = 1; $row <= $expectedHighestRow; $row++) {
                $expectedValue = $expectedSheet->getCellByColumnAndRow($col, $row)->getValue();
                $actualValue = $actualSheet->getCellByColumnAndRow($col, $row)->getValue();

                $this->assertEquals($expectedValue, $actualValue);
            }
        }
    }
}
