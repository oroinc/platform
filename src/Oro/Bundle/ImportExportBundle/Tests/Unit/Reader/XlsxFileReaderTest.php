<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\XlsxFileReader;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Component\Testing\ReflectionUtil;

class XlsxFileReaderTest extends \PHPUnit\Framework\TestCase
{
    private const MOCK_FILE_NAME = 'mock_file_for_initialize.xlsx';

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var ImportStrategyHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $importHelper;

    /** @var XlsxFileReader */
    private $reader;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->importHelper = $this->createMock(ImportStrategyHelper::class);

        $this->reader = new XlsxFileReader($this->contextRegistry);
        $this->reader->setImportHelper($this->importHelper);
    }

    public function testSetStepExecution()
    {
        $options = [
            'filePath' => __DIR__ . '/fixtures/' . self::MOCK_FILE_NAME,
            'firstLineIsHeader' => false,
            'header' => ['one', 'two']
        ];

        self::assertTrue(ReflectionUtil::getPropertyValue($this->reader, 'firstLineIsHeader'));
        self::assertEmpty($this->reader->getHeader());

        $context = new Context($options);
        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);
        $this->reader->setStepExecution($stepExecution);

        self::assertEquals(
            $options['firstLineIsHeader'],
            ReflectionUtil::getPropertyValue($this->reader, 'firstLineIsHeader')
        );
        self::assertEquals(
            $options['header'],
            $this->reader->getHeader()
        );
    }

    /**
     * @dataProvider excelDataProvider
     */
    public function testRead(array $options, ?array $exceptedHeader, array $expected)
    {
        $context = new Context($options);
        $stepExecution = $this->createMock(StepExecution::class);
        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);
        $this->reader->setStepExecution($stepExecution);
        $this->reader->initializeByContext($context);

        $this->assertSame($exceptedHeader, $this->reader->getHeader());

        $data = [];
        while (($dataRow = $this->reader->read($context)) !== null) {
            $this->assertEquals($exceptedHeader, $this->reader->getHeader());
            $data[] = $dataRow;
        }

        //ensured that previous data was cleared
        $this->assertNull($this->reader->getHeader());

        $this->assertSame($expected, $data);
    }

    public function excelDataProvider(): array
    {
        return [
            'without headers' => [
                'option' => [
                    'filePath' => __DIR__ . '/fixtures/' . self::MOCK_FILE_NAME,
                    Context::OPTION_FIRST_LINE_IS_HEADER => false,
                ],
                'exceptedHeader' => null,
                'expected' => [
                    ['field_one', 'field_two', 'field_three', 'field_four'],
                    [1, 2, 3, 4],
                    ['test1', 'test2', 'test3', 'test4'],
                    ['after_new1', 'after_new2', 'after_new3', 'after_new4'],
                ]
            ],
            'with headers' => [
                'option' => [
                    'filePath' => __DIR__ . '/fixtures/' . self::MOCK_FILE_NAME,
                    Context::OPTION_FIRST_LINE_IS_HEADER => false,
                    Context::OPTION_HEADER => ['h1', 'h2', 'h3', 'h4'],
                ],
                'exceptedHeader' => ['h1', 'h2', 'h3', 'h4'],
                'expected' => [
                    ['field_one', 'field_two', 'field_three', 'field_four'],
                    [1, 2, 3, 4],
                    ['test1', 'test2', 'test3', 'test4'],
                    ['after_new1', 'after_new2', 'after_new3', 'after_new4'],
                ]
            ],
            'with headers from file' => [
                'option' => [
                    'filePath' => __DIR__ . '/fixtures/' . self::MOCK_FILE_NAME,
                    Context::OPTION_FIRST_LINE_IS_HEADER => true,
                ],
                'exceptedHeader' => ['field_one', 'field_two', 'field_three', 'field_four'],
                'expected' => [
                    [
                        'field_one' => 1,
                        'field_two' => 2,
                        'field_three' => 3,
                        'field_four' => 4,
                    ],
                    [
                        'field_one' => 'test1',
                        'field_two' => 'test2',
                        'field_three' => 'test3',
                        'field_four' => 'test4',
                    ],
                    [
                        'field_one' => 'after_new1',
                        'field_two' => 'after_new2',
                        'field_three' => 'after_new3',
                        'field_four' => 'after_new4',
                    ],
                ]
            ],
        ];
    }
}
