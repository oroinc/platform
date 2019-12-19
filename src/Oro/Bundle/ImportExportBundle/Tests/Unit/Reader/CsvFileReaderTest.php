<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Reader;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CsvFileReaderTest extends TestCase
{
    /**
     * @var CsvFileReader
     */
    protected $reader;

    /**
     * @var ContextRegistry|MockObject
     */
    protected $contextRegistry;

    /** @var ImportStrategyHelper|MockObject */
    protected $importHelper;

    protected function setUp()
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->importHelper = $this->createMock(ImportStrategyHelper::class);

        $this->reader = new CsvFileReader($this->contextRegistry);
        $this->reader->setImportHelper($this->importHelper);
    }

    /**
     * @dataProvider readSeveralEntitiesProvider
     *
     * @param array $options
     * @param array $expected
     */
    public function testEnsureThatHeaderIsCleared(array $options, array $expected)
    {
        $context = $this->getContextWithOptionsMock($options);
        $stepExecution = $this->getMockStepExecution($context);
        $this->reader->setStepExecution($stepExecution);
        $this->reader->initializeByContext($context);

        $stepExecution->expects($this->never())
            ->method('addReaderWarning');
        $data = [];
        //ensure that header is cleared before read
        $this->assertNull($this->reader->getHeader());

        while (($dataRow = $this->reader->read($stepExecution)) !== null) {
            $data[] = $dataRow;
        }
        
        $this->assertNull($this->reader->getHeader()); //ensured that previous data was cleared
        $this->assertEquals($expected, $data);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration of reader must contain "filePath".
     */
    public function testSetStepExecutionNoFileException()
    {
        $context = $this->getContextWithOptionsMock([]);
        $this->reader->setStepExecution($this->getMockStepExecution($context));
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage File "unknown_file.csv" does not exists.
     */
    public function testUnknownFileException()
    {
        $context = $this->getContextWithOptionsMock(['filePath' => 'unknown_file.csv']);
        $this->reader->setStepExecution($this->getMockStepExecution($context));
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

        $this->assertAttributeEquals(',', 'delimiter', $this->reader);
        $this->assertAttributeEquals('"', 'enclosure', $this->reader);
        $this->assertAttributeEquals(chr(0), 'escape', $this->reader);
        $this->assertAttributeEquals(true, 'firstLineIsHeader', $this->reader);
        $this->assertAttributeEmpty('header', $this->reader);

        $context = $this->getContextWithOptionsMock($options);
        $this->reader->setStepExecution($this->getMockStepExecution($context));

        $this->assertAttributeEquals($options['delimiter'], 'delimiter', $this->reader);
        $this->assertAttributeEquals($options['enclosure'], 'enclosure', $this->reader);
        $this->assertAttributeEquals($options['escape'], 'escape', $this->reader);
        $this->assertAttributeEquals($options['firstLineIsHeader'], 'firstLineIsHeader', $this->reader);
        $this->assertAttributeEquals($options['header'], 'header', $this->reader);
    }

    /** @return array */
    public function readSeveralEntitiesProvider()
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
                ],
            ]
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testRead($options, $expected)
    {
        $context = $this->getContextWithOptionsMock($options);
        $stepExecution = $this->getMockStepExecution($context);
        $this->reader->setStepExecution($stepExecution);
        $context->expects($this->atLeastOnce())
            ->method('incrementReadOffset');
        $context->expects($this->atLeastOnce())
            ->method('incrementReadCount');
        $stepExecution->expects($this->never())
            ->method('addReaderWarning');
        $data = [];
        while (($dataRow = $this->reader->read($stepExecution)) !== null) {
            $data[] = $dataRow;
        }
        $this->assertEquals($expected, $data);
    }

    public function optionsDataProvider()
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
                ]
            ]
        ];
    }

    public function testReadWithBackslashes()
    {
        $context = $this->getContextWithOptionsMock([
            'filePath' => __DIR__ . '/fixtures/import_with_backslashes.csv',
            'firstLineIsHeader' => false
        ]);
        $stepExecution = $this->getMockStepExecution($context);
        $this->reader->setStepExecution($stepExecution);
        $data = [];
        $row = null;
        while (($dataRow = $this->reader->read($stepExecution)) !== null) {
            $data[] = $row = $dataRow;
        }
        $this->assertEquals([['\\', 'other field', '\\\\', '\\notquote', 'back \\slash inside', '\"quoted\"']], $data);
    }

    /**
     * @expectedException \Akeneo\Bundle\BatchBundle\Item\InvalidItemException
     * @expectedExceptionMessage Expecting to get 3 columns, actually got 2.
     * Message also contains additional rows info but it is not possible to add it in annotation
     */
    public function testReadError()
    {
        $context = $this->getContextWithOptionsMock(['filePath' => __DIR__ . '/fixtures/import_incorrect.csv']);
        $stepExecution = $this->getMockStepExecution($context);
        $this->reader->setStepExecution($stepExecution);
        $this->reader->initializeByContext($context);

        $context
            ->expects($this->once())
            ->method('incrementErrorEntriesCount');

        $this->importHelper
            ->expects($this->once())
            ->method('addValidationErrors')
            ->willReturnCallback(function (array $messages, $context) {
                $message = reset($messages);
                $this->assertContains('Expecting to get 3 columns, actually got 2.', $message);
            });

        $this->reader->read($context);
    }

    /**
     * @expectedException \Akeneo\Bundle\BatchBundle\Item\InvalidItemException
     * @expectedExceptionMessage Expecting to get 3 columns, actually got 2.
     * Message also contains additional rows info but it is not possible to add it in annotation
     */
    public function testReadErrorWithinSplitProcess()
    {
        $context = $this->getContextWithOptionsMock(['filePath' => __DIR__ . '/fixtures/import_incorrect.csv']);
        $this->reader->initializeByContext($context);

        $context
            ->expects($this->never())
            ->method('incrementErrorEntriesCount');

        $this->importHelper
            ->expects($this->never())
            ->method('addValidationErrors');

        $this->reader->read($context);
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
        $context = $this->createMock(StepExecutionProxyContext::class);

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
        return $context;
    }
}
