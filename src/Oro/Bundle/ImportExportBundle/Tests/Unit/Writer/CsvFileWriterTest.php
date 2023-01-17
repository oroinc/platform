<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Writer\CsvFileWriter;
use Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;

class CsvFileWriterTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $filePath;

    /** @var string */
    private $tmpDir;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var DoctrineClearWriter|\PHPUnit\Framework\MockObject\MockObject */
    private $clearWriter;

    /** @var CsvFileWriter */
    private $writer;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->getMockBuilder(ContextRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getByStepExecution'])
            ->getMock();
        $this->tmpDir = $this->getTempDir('CsvFileWriterTest');
        $this->filePath = $this->tmpDir . DIRECTORY_SEPARATOR . 'new_file.csv';
        $this->clearWriter = $this->createMock(DoctrineClearWriter::class);

        $this->writer = new CsvFileWriter($this->contextRegistry, $this->clearWriter);
    }

    public function testSetStepExecutionNoFileException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of CSV writer must contain "filePath".');

        $this->writer->setStepExecution($this->getStepExecution([]));
    }

    public function testUnknownFileException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->writer->setStepExecution(
            $this->getStepExecution(['filePath' => $this->tmpDir . '/unknown/new_file.csv'])
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

        self::assertEquals(',', ReflectionUtil::getPropertyValue($this->writer, 'delimiter'));
        self::assertEquals('"', ReflectionUtil::getPropertyValue($this->writer, 'enclosure'));
        self::assertTrue(ReflectionUtil::getPropertyValue($this->writer, 'firstLineIsHeader'));
        self::assertEmpty(ReflectionUtil::getPropertyValue($this->writer, 'header'));

        $this->writer->setStepExecution($this->getStepExecution($options));

        self::assertEquals(
            $options['delimiter'],
            ReflectionUtil::getPropertyValue($this->writer, 'delimiter')
        );
        self::assertEquals(
            $options['enclosure'],
            ReflectionUtil::getPropertyValue($this->writer, 'enclosure')
        );
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
        self::assertFileExists($expected);
        $expectedContent = file_get_contents($expected);
        $actualContent = file_get_contents($options['filePath']);

        $expectedContent = preg_replace('/\r\n?/', "\n", $expectedContent);
        $actualContent = preg_replace('/\r\n?/', "\n", $actualContent);

        self::assertEquals($expectedContent, $actualContent);
    }

    public function optionsDataProvider(): array
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
        $stepExecution = $this->getStepExecution($options);

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
     */
    public function testWriteWithClearWriter(array $options, array $data, string $expected)
    {
        $stepExecution = $this->getStepExecution($options);
        $this->writer->setStepExecution($stepExecution);
        $this->clearWriter
            ->expects($this->once())
            ->method('write')
            ->with($data);
        $this->writer->write($data);
        self::assertFileExists($expected);

        $expectedContent = file_get_contents($expected);
        $actualContent = file_get_contents($options['filePath']);

        $expectedContent = preg_replace('/\r\n?/', "\n", $expectedContent);
        $actualContent = preg_replace('/\r\n?/', "\n", $actualContent);

        self::assertEquals($expectedContent, $actualContent);
    }

    private function getStepExecution(array $jobInstanceRawConfiguration): StepExecution
    {
        $stepExecution = $this->createMock(StepExecution::class);

        $context = $this->getMockBuilder(StepExecutionProxyContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfiguration'])
            ->getMock();
        $context->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($jobInstanceRawConfiguration);

        $this->contextRegistry->expects(self::any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        return $stepExecution;
    }
}
