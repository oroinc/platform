<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Exception\InvalidItemException;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Component\Testing\ReflectionUtil;

class CsvFileReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var ImportStrategyHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $importHelper;

    /** @var CsvFileReader */
    private $reader;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->importHelper = $this->createMock(ImportStrategyHelper::class);

        $this->reader = new CsvFileReader($this->contextRegistry);
        $this->reader->setImportHelper($this->importHelper);
    }

    /**
     * @dataProvider readSeveralEntitiesProvider
     */
    public function testEnsureThatHeaderIsCleared(array $options, array $expected)
    {
        $context = $this->getContextWithOptions($options);
        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);
        $this->reader->setStepExecution($stepExecution);
        $this->reader->initializeByContext($context);

        $data = [];
        //ensure that header is cleared before read
        self::assertNull($this->reader->getHeader());

        while (($dataRow = $this->reader->read($stepExecution)) !== null) {
            $data[] = $dataRow;
        }

        self::assertEquals($expected, $data);
    }

    public function testSetStepExecutionNoFileException()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration of reader must contain "filePath".');

        $context = $this->getContextWithOptions([]);
        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);
        $this->reader->setStepExecution($stepExecution);
    }

    public function testUnknownFileException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File "unknown_file.csv" does not exists.');

        $context = $this->getContextWithOptions(['filePath' => 'unknown_file.csv']);
        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);
        $this->reader->setStepExecution($stepExecution);
    }

    public function testSetStepExecution()
    {
        $options = [
            'filePath' => __DIR__ . '/fixtures/import_correct.csv',
            'delimiter' => ',',
            'enclosure' => "'''",
            'escape' => ';',
            'firstLineIsHeader' => false,
            'header' => ['one', 'two']
        ];

        self::assertEquals(',', ReflectionUtil::getPropertyValue($this->reader, 'delimiter'));
        self::assertEquals('"', ReflectionUtil::getPropertyValue($this->reader, 'enclosure'));
        self::assertEquals(chr(0), ReflectionUtil::getPropertyValue($this->reader, 'escape'));
        self::assertTrue(ReflectionUtil::getPropertyValue($this->reader, 'firstLineIsHeader'));
        self::assertEmpty($this->reader->getHeader());

        $context = $this->getContextWithOptions($options);
        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);
        $this->reader->setStepExecution($stepExecution);

        self::assertEquals(
            $options['delimiter'],
            ReflectionUtil::getPropertyValue($this->reader, 'delimiter')
        );
        self::assertEquals(
            $options['enclosure'],
            ReflectionUtil::getPropertyValue($this->reader, 'enclosure')
        );
        self::assertEquals(
            $options['escape'],
            ReflectionUtil::getPropertyValue($this->reader, 'escape')
        );
        self::assertEquals(
            $options['firstLineIsHeader'],
            ReflectionUtil::getPropertyValue($this->reader, 'firstLineIsHeader')
        );
        self::assertEquals(
            $options['header'],
            $this->reader->getHeader()
        );
    }

    public function readSeveralEntitiesProvider(): array
    {
        return [
            [

                'option' => ['filePath' => __DIR__ . '/fixtures/import_correct.csv'],
                'expected' => [
                    [
                        'field_one' => '1',
                        'field_two' => '2',
                        'field_three' => '3',
                    ],
                    [
                        'field_one' => 'test1',
                        'field_two' => 'test2',
                        'field_three' => 'test3',
                    ],
                    [],
                    [
                        'field_one' => 'after_new1',
                        'field_two' => 'after_new2',
                        'field_three' => 'after_new3',
                    ],
                    [
                        'field_one' => 'sample1',
                        'field_two' => 'sample2',
                        'field_three' => "sample3\nwith\nnew\nlines",
                    ],
                ],
            ]
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testRead(array $options, array $expected)
    {
        $context = $this->getContextWithOptions($options);
        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);
        $this->reader->setStepExecution($stepExecution);
        $context->expects($this->atLeastOnce())
            ->method('incrementReadOffset');
        $context->expects($this->atLeastOnce())
            ->method('incrementReadCount');
        $data = [];
        while (($dataRow = $this->reader->read($stepExecution)) !== null) {
            $data[] = $dataRow;
        }
        self::assertEquals($expected, $data);
    }

    public function optionsDataProvider(): array
    {
        return [
            [
                ['filePath' => __DIR__ . '/fixtures/import_correct.csv'],
                [
                    [
                        'field_one' => '1',
                        'field_two' => '2',
                        'field_three' => '3',
                    ],
                    [
                        'field_one' => 'test1',
                        'field_two' => 'test2',
                        'field_three' => 'test3',
                    ],
                    [],
                    [
                        'field_one' => 'after_new1',
                        'field_two' => 'after_new2',
                        'field_three' => 'after_new3',
                    ],
                    [
                        'field_one' => 'sample1',
                        'field_two' => 'sample2',
                        'field_three' => "sample3\nwith\nnew\nlines",
                    ],
                ]
            ],
            [
                [
                    'filePath' => __DIR__ . '/fixtures/import_correct.csv',
                    'header' => ['h1', 'h2', 'h3']
                ],
                [
                    [
                        'h1' => 'field_one',
                        'h2' => 'field_two',
                        'h3' => 'field_three'
                    ],
                    [
                        'h1' => '1',
                        'h2' => '2',
                        'h3' => '3',
                    ],
                    [
                        'h1' => 'test1',
                        'h2' => 'test2',
                        'h3' => 'test3',
                    ],
                    [],
                    [
                        'h1' => 'after_new1',
                        'h2' => 'after_new2',
                        'h3' => 'after_new3',
                    ],
                    [
                        'h1' => 'sample1',
                        'h2' => 'sample2',
                        'h3' => "sample3\nwith\nnew\nlines",
                    ],
                ]
            ],
            [
                [
                    'filePath' => __DIR__ . '/fixtures/import_correct.csv',
                    'firstLineIsHeader' => false
                ],
                [
                    ['field_one', 'field_two', 'field_three'],
                    ['1', '2', '3'],
                    ['test1', 'test2', 'test3'],
                    [],
                    ['after_new1', 'after_new2', 'after_new3'],
                    ['sample1', 'sample2', "sample3\nwith\nnew\nlines"],
                ]
            ],
            [
                ['filePath' => __DIR__ . '/fixtures/import_iso_8859_1.csv'],
                [
                    [
                        'field_one' => '1',
                        'field_two' => "Associ? ? Nom d'utilisateur",
                        "Associ? ? Nom d'utilisateur" => '3',
                    ]
                ]
            ],
        ];
    }

    public function testReadWithBackslashes()
    {
        $context = $this->getContextWithOptions([
            'filePath' => __DIR__ . '/fixtures/import_with_backslashes.csv',
            'firstLineIsHeader' => false
        ]);
        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);
        $this->reader->setStepExecution($stepExecution);
        $data = [];
        while (($dataRow = $this->reader->read($stepExecution)) !== null) {
            $data[] = $dataRow;
        }
        self::assertEquals([['\\', 'other field', '\\\\', '\\notquote', 'back \\slash inside', '\"quoted\"']], $data);
    }

    /**
     * Message also contains additional rows info but it is not possible to add it in annotation
     */
    public function testReadError()
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('Expecting to get 3 columns, actually got 2.');

        $context = $this->getContextWithOptions(['filePath' => __DIR__ . '/fixtures/import_incorrect.csv']);
        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);
        $this->reader->setStepExecution($stepExecution);
        $this->reader->initializeByContext($context);

        $context->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->importHelper->expects($this->once())
            ->method('addValidationErrors')
            ->willReturnCallback(function (array $messages) {
                self::assertStringContainsString('Expecting to get 3 columns, actually got 2.', reset($messages));
            });

        $this->reader->read($stepExecution);
    }

    /**
     * Message also contains additional rows info but it is not possible to add it in annotation
     */
    public function testReadErrorWithinSplitProcess()
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('Expecting to get 3 columns, actually got 2.');

        $context = $this->getContextWithOptions(['filePath' => __DIR__ . '/fixtures/import_incorrect.csv']);
        $this->reader->initializeByContext($context);

        $context->expects($this->never())
            ->method('incrementErrorEntriesCount');

        $this->importHelper->expects($this->never())
            ->method('addValidationErrors');

        $this->reader->read($context);
    }

    /**
     * @return StepExecutionProxyContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getContextWithOptions(array $options)
    {
        $context = $this->createMock(StepExecutionProxyContext::class);
        $context->expects($this->any())
            ->method('hasOption')
            ->willReturnCallback(function ($option) use ($options) {
                return isset($options[$option]);
            });
        $context->expects($this->any())
            ->method('getOption')
            ->willReturnCallback(function ($option) use ($options) {
                return $options[$option];
            });

        return $context;
    }
}
