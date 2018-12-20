<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Writer\CsvFileWriter;
use Oro\Component\Testing\TempDirExtension;

class CsvFileWriterTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var CsvFileWriter */
    protected $writer;

    /** @var string */
    protected $filePath;

    /** @var string */
    private $tmpDir;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextRegistry;

    protected function setUp()
    {
        $this->contextRegistry = $this->getMockBuilder(ContextRegistry::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByStepExecution'])
            ->getMock();

        $this->tmpDir = $this->getTempDir('CsvFileWriterTest');

        $this->filePath = $this->tmpDir . DIRECTORY_SEPARATOR . 'new_file.csv';

        $this->writer = new CsvFileWriter($this->contextRegistry);
    }

    protected function tearDown()
    {
        $this->writer->close();
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration of CSV writer must contain "filePath".
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

        self::assertAttributeEquals(',', 'delimiter', $this->writer);
        self::assertAttributeEquals('"', 'enclosure', $this->writer);
        self::assertAttributeEquals(true, 'firstLineIsHeader', $this->writer);
        self::assertAttributeEmpty('header', $this->writer);

        $this->writer->setStepExecution($this->getMockStepExecution($options));

        self::assertAttributeEquals($options['delimiter'], 'delimiter', $this->writer);
        self::assertAttributeEquals($options['enclosure'], 'enclosure', $this->writer);
        self::assertAttributeEquals($options['firstLineIsHeader'], 'firstLineIsHeader', $this->writer);
        self::assertAttributeEquals($options['header'], 'header', $this->writer);
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
        $clearWriter = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter')
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
     * @return \PHPUnit\Framework\MockObject\MockObject|StepExecution
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
}
