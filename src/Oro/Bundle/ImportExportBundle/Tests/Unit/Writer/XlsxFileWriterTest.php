<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\XLSX\Sheet;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter;
use Oro\Bundle\ImportExportBundle\Writer\XlsxFileWriter;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;

class XlsxFileWriterTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var DoctrineClearWriter|\PHPUnit\Framework\MockObject\MockObject */
    private $clearWriter;

    /** @var XlsxFileWriter */
    private $writer;

    protected function setUp(): void
    {
        $this->getTempDir('XlsxFileWriterTest');
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->clearWriter = $this->createMock(DoctrineClearWriter::class);

        $this->writer = new XlsxFileWriter($this->contextRegistry, $this->clearWriter);
    }

    protected function tearDown(): void
    {
        $this->writer->close();
    }

    public function testSetStepExecutionNoFileException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of XLSX writer must contain "filePath".');

        $this->writer->setStepExecution($this->getStepExecution([]));
    }

    public function testUnknownFileException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->writer->setStepExecution(
            $this->getStepExecution(['filePath' => __DIR__ . '/unknown/new_file.xlsx'])
        );
    }

    public function testSetStepExecution()
    {
        $options = [
            'filePath'          => $this->getFilePath(),
            'firstLineIsHeader' => false,
            'header'            => ['one', 'two']
        ];

        self::assertTrue(ReflectionUtil::getPropertyValue($this->writer, 'firstLineIsHeader'));
        self::assertEmpty(ReflectionUtil::getPropertyValue($this->writer, 'header'));

        $this->writer->setStepExecution($this->getStepExecution($options));

        self::assertEquals(
            $options['firstLineIsHeader'],
            ReflectionUtil::getPropertyValue($this->writer, 'firstLineIsHeader')
        );
        self::assertEquals(
            $options['header'],
            ReflectionUtil::getPropertyValue($this->writer, 'header')
        );
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testWrite(array $options, array $data, string $expected)
    {
        $stepExecution = $this->getStepExecution($options);
        $this->writer->setStepExecution($stepExecution);
        $this->writer->write($data);
        $this->writer->close();

        self::assertFileExists($expected);
        $this->assertXlsx($expected, $options['filePath']);
    }

    public function optionsDataProvider(): array
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
     */
    public function testWriteWithClearWriter(array $options, array $data, string $expected)
    {
        $stepExecution = $this->getStepExecution($options);
        $this->writer->setStepExecution($stepExecution);
        $this->clearWriter->expects(self::once())
            ->method('write')
            ->with($data);

        $this->writer->write($data);
        $this->writer->close();

        self::assertFileExists($expected);
        $this->assertXlsx($expected, $options['filePath']);
    }

    private function getStepExecution(array $jobInstanceRawConfiguration): StepExecution
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $context = new Context($jobInstanceRawConfiguration);
        $this->contextRegistry->expects(self::any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        return $stepExecution;
    }

    private function getFilePath(): string
    {
        return $this->getTempDir('XlsxFileWriterTest', null) . DIRECTORY_SEPARATOR . 'new_file.xlsx';
    }

    private function assertXlsx(string $expectedPath, string $actualPath): void
    {
        $exceptedReader = ReaderFactory::createFromType(Type::XLSX);
        $exceptedReader->open($expectedPath);
        /** @var Sheet[] $exceptedSheets */
        $exceptedSheets = iterator_to_array($exceptedReader->getSheetIterator());

        $actualReader = ReaderFactory::createFromType(Type::XLSX);
        $actualReader->open($actualPath);
        /** @var Sheet[] $actualSheets */
        $actualSheets = iterator_to_array($actualReader->getSheetIterator());

        self::assertCount(count($exceptedSheets), $actualSheets);

        foreach ($exceptedSheets as $sheetIndex => $sheet) {
            self::assertEquals(
                iterator_to_array($sheet->getRowIterator()),
                iterator_to_array($actualSheets[$sheetIndex]->getRowIterator())
            );
        }
    }
}
