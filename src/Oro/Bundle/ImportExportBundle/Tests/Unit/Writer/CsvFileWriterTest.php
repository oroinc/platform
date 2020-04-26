<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Writer\CsvFileWriter;
use Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;

class CsvFileWriterTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var CsvFileWriter */
    protected $writer;

    /** @var string */
    protected $filePath;

    /** @var string */
    private $tmpDir;

    /** @var ContextRegistry|MockObject */
    protected $contextRegistry;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->getMockBuilder(ContextRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByStepExecution'])
            ->getMock();

        $this->tmpDir = $this->getTempDir('CsvFileWriterTest');

        $this->filePath = $this->tmpDir . DIRECTORY_SEPARATOR . 'new_file.csv';

        $this->writer = new class($this->contextRegistry) extends CsvFileWriter {
            public function xgetDelimiter(): string
            {
                return $this->delimiter;
            }

            public function xgetEnclosure(): string
            {
                return $this->enclosure;
            }

            public function xisFirstLineIsHeader(): bool
            {
                return $this->firstLineIsHeader;
            }

            public function xgetHeader(): ?array
            {
                return $this->header;
            }
        };
    }

    protected function tearDown(): void
    {
        $this->writer->close();
    }

    public function testSetStepExecutionNoFileException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of CSV writer must contain "filePath".');

        $this->writer->setStepExecution($this->getMockStepExecution([]));
    }

    public function testUnknownFileException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->writer->setStepExecution(
            $this->getMockStepExecution(
                [
                    'filePath' => $this->tmpDir . '/unknown/new_file.csv'
                ]
            )
        );
    }

    public function testSetStepExecution()
    {
        $options = [
            'filePath'          => $this->filePath,
            'delimiter'         => ',',
            'enclosure'         => "'''",
            'firstLineIsHeader' => false,
            'header'            => ['one', 'two']
        ];

        static::assertEquals(',', $this->writer->xgetDelimiter());
        static::assertEquals('"', $this->writer->xgetEnclosure());
        static::assertTrue($this->writer->xisFirstLineIsHeader());
        static::assertEmpty($this->writer->xgetHeader());

        $this->writer->setStepExecution($this->getMockStepExecution($options));

        static::assertEquals($options['delimiter'], $this->writer->xgetDelimiter());
        static::assertEquals($options['enclosure'], $this->writer->xgetEnclosure());
        static::assertEquals($options['firstLineIsHeader'], $this->writer->xisFirstLineIsHeader());
        static::assertEquals($options['header'], $this->writer->xgetHeader());
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
        self::assertFileExists($expected);
        $expectedContent = file_get_contents($expected);
        $actualContent = file_get_contents($options['filePath']);

        $expectedContent = preg_replace('/\r\n?/', "\n", $expectedContent);
        $actualContent = preg_replace('/\r\n?/', "\n", $actualContent);

        self::assertEquals($expectedContent, $actualContent);
    }

    public function optionsDataProvider()
    {
        $filePath = $this->getTempDir('CsvFileWriterTest', null) . DIRECTORY_SEPARATOR . 'new_file.csv';

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
                __DIR__ . '/fixtures/first_item_header.csv'
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
                __DIR__ . '/fixtures/defined_header.csv'
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
                __DIR__ . '/fixtures/no_header.csv'
            ]
        ];
    }

    public function testWriteBackslashWhenBackslashes()
    {
        $options = [
            'filePath'          => $this->tmpDir . '/new_file.csv',
            'firstLineIsHeader' => false
        ];
        $stepExecution = $this->getMockStepExecution($options);

        $data = [['\\', 'other field', '\\\\', '\\notquote', 'back \\slash inside', '\"quoted\"']];
        $expected = __DIR__ . '/fixtures/backslashes.csv';
        $this->writer->setStepExecution($stepExecution);
        $this->writer->write($data);
        self::assertFileExists($expected);
        $expectedContent = file_get_contents($expected);
        $actualContent = file_get_contents($options['filePath']);

        $expectedContent = preg_replace('/\r\n?/', "\n", $expectedContent);
        $actualContent = preg_replace('/\r\n?/', "\n", $actualContent);

        self::assertEquals($expectedContent, $actualContent);
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
        /** @var DoctrineClearWriter|MockObject $clearWriter */
        $clearWriter = $this->getMockBuilder(DoctrineClearWriter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $clearWriter->expects($this->once())
            ->method('write')
            ->with($data);
        $this->writer->setClearWriter($clearWriter);
        $this->writer->write($data);
        self::assertFileExists($expected);

        $expectedContent = file_get_contents($expected);
        $actualContent = file_get_contents($options['filePath']);

        $expectedContent = preg_replace('/\r\n?/', "\n", $expectedContent);
        $actualContent = preg_replace('/\r\n?/', "\n", $actualContent);

        self::assertEquals($expectedContent, $actualContent);
    }

    /**
     * @param array $jobInstanceRawConfiguration
     *
     * @return MockObject|StepExecution
     */
    protected function getMockStepExecution(array $jobInstanceRawConfiguration)
    {
        $stepExecution = $this->getMockBuilder(StepExecution::class)->disableOriginalConstructor()->getMock();

        $context = $this->getMockBuilder(StepExecutionProxyContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfiguration'])
            ->getMock();
        $context->expects(static::any())->method('getConfiguration')->willReturn($jobInstanceRawConfiguration);

        $this->contextRegistry->expects(static::any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        return $stepExecution;
    }
}
