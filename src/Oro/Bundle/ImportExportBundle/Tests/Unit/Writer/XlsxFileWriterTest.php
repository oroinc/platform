<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Box\Spout\Common\Type;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Reader\XLSX\Sheet;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter;
use Oro\Bundle\ImportExportBundle\Writer\XlsxFileWriter;
use Oro\Component\Testing\TempDirExtension;

class XlsxFileWriterTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var XlsxFileWriter */
    private $writer;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    protected function setUp(): void
    {
        $this->getTempDir('XlsxFileWriterTest');
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->writer = new XlsxFileWriter($this->contextRegistry);
    }

    protected function tearDown(): void
    {
        $this->writer->close();
    }

    public function testSetStepExecutionNoFileException()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of XLSX writer must contain "filePath".');

        $this->writer->setStepExecution($this->getMockStepExecution([]));
    }

    public function testUnknownFileException()
    {
        $this->expectException(\Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException::class);
        $this->writer->setStepExecution(
            $this->getMockStepExecution(
                [
                    'filePath' => __DIR__ . '/unknown/new_file.xlsx'
                ]
            )
        );
    }

    public function testSetStepExecution()
    {
        $options = [
            'filePath'          => $this->getFilePath(),
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
        $filePath = $this->getFilePath();

        return [
            'first_item_header' => [
                ['filePath' => $filePath],
                [
                    [
                        'field_one'   => 1,
                        'field_two'   => 2,
                        'field_three' => 3,
                    ],
                    [
                        'field_one'   => 'test1',
                        'field_two'   => 'test2',
                        'field_three' => 'test3',
                    ]
                ],
                __DIR__ . '/fixtures/first_item_header.xlsx'
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
                __DIR__ . '/fixtures/defined_header.xlsx'
            ],
            'no_header'         => [
                [
                    'filePath'          => $filePath,
                    'firstLineIsHeader' => false
                ],
                [
                    [1, 2, 3],
                    ['test1', 'test2', 'test3']
                ],
                __DIR__ . '/fixtures/no_header.xlsx'
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

        /** @var DoctrineClearWriter|\PHPUnit\Framework\MockObject\MockObject $clearWriter */
        $clearWriter = $this->createMock(DoctrineClearWriter::class);
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
     * @return \PHPUnit\Framework\MockObject\MockObject|StepExecution
     */
    protected function getMockStepExecution(array $jobInstanceRawConfiguration)
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $context = new Context($jobInstanceRawConfiguration);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        return $stepExecution;
    }

    /**
     * @return string
     */
    private function getFilePath(): string
    {
        return $this->getTempDir('XlsxFileWriterTest', null) . DIRECTORY_SEPARATOR . 'new_file.xlsx';
    }

    /**
     * @param string $expectedPath
     * @param string $actualPath
     */
    private function assertXlsx(string $expectedPath, string $actualPath): void
    {
        $exceptedReader = ReaderFactory::create(Type::XLSX);
        $exceptedReader->open($expectedPath);
        /** @var Sheet[] $exceptedSheets */
        $exceptedSheets = iterator_to_array($exceptedReader->getSheetIterator());

        $actualReader = ReaderFactory::create(Type::XLSX);
        $actualReader->open($actualPath);
        /** @var Sheet[] $actualSheets */
        $actualSheets = iterator_to_array($actualReader->getSheetIterator());

        $this->assertCount(count($exceptedSheets), $actualSheets);

        foreach ($exceptedSheets as $sheetIndex => $sheet) {
            $this->assertSame(
                iterator_to_array($sheet->getRowIterator()),
                iterator_to_array($actualSheets[$sheetIndex]->getRowIterator())
            );
        }
    }
}
