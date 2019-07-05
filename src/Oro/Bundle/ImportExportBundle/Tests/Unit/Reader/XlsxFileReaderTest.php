<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\CacheBundle\Simple\PhpTempCache;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Reader\XlsxFileReader;

class XlsxFileReaderTest extends \PHPUnit\Framework\TestCase
{
    const MOCK_FILE_NAME = 'mock_file_for_initialize.xlsx';

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var PhpTempCache */
    private $cache;

    /** @var XlsxFileReader */
    private $reader;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);

        $this->cache = new PhpTempCache();
        $this->reader = new XlsxFileReader($this->contextRegistry, $this->cache);
    }

    /** {@inheritdoc} */
    public function tearDown()
    {
        $this->cache->clear();
    }

    public function testSetStepExecution()
    {
        $options = [
            'filePath' => __DIR__ . '/fixtures/' . self::MOCK_FILE_NAME,
            'firstLineIsHeader' => false,
            'header' => ['one', 'two']
        ];

        $this->assertAttributeEquals(true, 'firstLineIsHeader', $this->reader);
        $this->assertAttributeEmpty('header', $this->reader);

        $context = new Context($options);
        $this->reader->setStepExecution($this->getMockStepExecution($context));

        $this->assertAttributeEquals($options['firstLineIsHeader'], 'firstLineIsHeader', $this->reader);
        $this->assertAttributeEquals($options['header'], 'header', $this->reader);
    }

    /**
     * @dataProvider excelDataProvider
     *
     * @param array $options
     * @param $exceptedHeader
     * @param array $expected
     */
    public function testRead($options, $exceptedHeader, $expected)
    {
        $context = new Context($options);
        $stepExecution = $this->getMockStepExecution($context);

        $this->reader->setStepExecution($stepExecution);
        $this->reader->initializeByContext($context);

        $stepExecution->expects($this->never())
            ->method('addReaderWarning');

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

    /** @return array */
    public function excelDataProvider()
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
                    [1.0, 2.0, 3.0, 4.0],
                    ['test1', 'test2', 'test3', 'test4'],
                    [],
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
                    [1.0, 2.0, 3.0, 4.0],
                    ['test1', 'test2', 'test3', 'test4'],
                    [],
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
                        'field_one' => 1.0,
                        'field_two' => 2.0,
                        'field_three' => 3.0,
                        'field_four' => 4.0,
                    ],
                    [
                        'field_one' => 'test1',
                        'field_two' => 'test2',
                        'field_three' => 'test3',
                        'field_four' => 'test4',
                    ],
                    [],
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

    /**
     * @param ContextInterface $context
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
            ->willReturn($context);

        return $stepExecution;
    }
}
