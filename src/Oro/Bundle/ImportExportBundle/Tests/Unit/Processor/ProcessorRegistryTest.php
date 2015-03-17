<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor;

use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareProcessor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

class ProcessorRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProcessorRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new ProcessorRegistry();
    }

    public function testRegisterProcessor()
    {
        $entityName = 'entity_name';
        $alias = 'processor_alias';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $importProcessor */
        $importProcessor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityNameAwareProcessor $exportProcessor */
        $exportProcessor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareProcessor');
        $exportProcessor->expects($this->once())->method('setEntityName')->with($entityName);

        $this->registry->registerProcessor($importProcessor, ProcessorRegistry::TYPE_IMPORT, $entityName, $alias);
        $this->registry->registerProcessor($exportProcessor, ProcessorRegistry::TYPE_EXPORT, $entityName, $alias);
        $this->assertAttributeEquals(
            [
                ProcessorRegistry::TYPE_IMPORT => [$alias => $importProcessor],
                ProcessorRegistry::TYPE_EXPORT => [$alias => $exportProcessor],
            ],
            'processors',
            $this->registry
        );
        $this->assertAttributeEquals(
            [
                $entityName => [
                    ProcessorRegistry::TYPE_IMPORT => [$alias => $importProcessor],
                    ProcessorRegistry::TYPE_EXPORT => [$alias => $exportProcessor]
                ]
            ],
            'processorsByEntity',
            $this->registry
        );

        return $this->registry;
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage Processor with type "import" and alias "processor_alias" already exists
     */
    public function testRegisterProcessorFails()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $alias = 'processor_alias';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $processor */
        $processor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        $this->registry->registerProcessor($processor, $type, $entityName, $alias);
        $this->registry->registerProcessor($processor, $type, $entityName, $alias);
    }

    public function testUnregisterProcessor()
    {
        $fooType = ProcessorRegistry::TYPE_IMPORT;
        $fooEntityName = 'foo_entity_name';
        $fooAlias = 'foo_processor_alias';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $fooProcessor */
        $fooProcessor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        $barType = ProcessorRegistry::TYPE_EXPORT;
        $barEntityName = 'bar_entity_name';
        $barAlias = 'bar_processor_alias';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $barProcessor */
        $barProcessor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        $this->registry->registerProcessor($fooProcessor, $fooType, $fooEntityName, $fooAlias);
        $this->registry->registerProcessor($barProcessor, $barType, $barEntityName, $barAlias);
        $this->registry->unregisterProcessor($fooType, $fooEntityName, $fooAlias);
        $this->assertAttributeEquals(
            [$fooType => [], $barType => [$barAlias => $barProcessor]],
            'processors',
            $this->registry
        );
        $this->assertAttributeEquals(
            [
                $fooEntityName => [$fooType => []],
                $barEntityName => [$barType => [$barAlias => $barProcessor]],
            ],
            'processorsByEntity',
            $this->registry
        );
    }

    public function testHasProcessor()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $alias = 'processor_alias';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $processor */
        $processor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        $this->assertFalse($this->registry->hasProcessor($type, $alias));
        $this->registry->registerProcessor($processor, $type, $entityName, $alias);
        $this->assertTrue($this->registry->hasProcessor($type, $alias));
    }

    public function testGetProcessor()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $alias = 'processor_alias';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $processor */
        $processor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        $this->registry->registerProcessor($processor, $type, $entityName, $alias);
        $this->assertSame($processor, $this->registry->getProcessor($type, $alias));
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\UnexpectedValueException
     * @expectedExceptionMessage Processor with type "import" and alias "processor_alias" is not exist
     */
    public function testGetProcessorFails()
    {
        $this->registry->getProcessor('import', 'processor_alias');
    }

    public function testGetProcessorsByEntity()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $fooAlias = 'foo_alias';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $fooProcessor */
        $fooProcessor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');
        $barAlias = 'bar_alias';

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $barProcessor */
        $barProcessor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        $this->registry->registerProcessor($fooProcessor, $type, $entityName, $fooAlias);
        $this->registry->registerProcessor($barProcessor, $type, $entityName, $barAlias);

        $this->assertEquals(
            [$fooAlias => $fooProcessor, $barAlias => $barProcessor],
            $this->registry->getProcessorsByEntity($type, $entityName)
        );
    }

    public function testGetProcessorsByEntityUnknown()
    {
        $this->assertEquals(
            [],
            $this->registry->getProcessorsByEntity('unknown', 'unknown')
        );
    }

    public function testGetProcessorAliasesByEntityUnknown()
    {
        $this->assertEquals(
            [],
            $this->registry->getProcessorAliasesByEntity('unknown', 'unknown')
        );
    }

    public function testGetProcessorAliasesByEntity()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $fooAlias = 'foo_alias';
        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $fooProcessor */
        $fooProcessor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');
        $barAlias = 'bar_alias';
        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $barProcessor */
        $barProcessor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        $this->registry->registerProcessor($fooProcessor, $type, $entityName, $fooAlias);
        $this->registry->registerProcessor($barProcessor, $type, $entityName, $barAlias);

        $this->assertEquals(
            [$fooAlias, $barAlias],
            $this->registry->getProcessorAliasesByEntity($type, $entityName)
        );
    }

    public function testGetProcessorEntityName()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $alias = 'foo_alias';
        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessorInterface $processor */
        $processor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        $this->registry->registerProcessor($processor, $type, $entityName, $alias);

        $this->assertEquals($entityName, $this->registry->getProcessorEntityName($type, $alias));
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\UnexpectedValueException
     * @expectedExceptionMessage Processor with type "import" and alias "foo_alias" is not exist
     */
    public function testGetProcessorEntityNameFails()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $alias = 'foo_alias';
        $this->registry->getProcessorEntityName($type, $alias);
    }

    /**
     * @param array $processors
     * @param string $type
     * @param array $expected
     *
     * @dataProvider processorsByTypeDataProvider
     */
    public function testGetProcessorsByType(array $processors, $type, array $expected)
    {

        foreach ($processors as $processorType => $processorsByType) {
            foreach ($processorsByType as $processorName => $processor) {
                $this->registry->registerProcessor($processor, $processorType, '\stdClass', $processorName);
            }
        }

        $this->assertEquals(
            $expected,
            $this->registry->getProcessorsByType($type)
        );
    }

    /**
     * @return array
     */
    public function processorsByTypeDataProvider()
    {
        $processor = $this->getMock('Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface');

        return [
            [
                [
                    ProcessorRegistry::TYPE_IMPORT => [
                        'processor1' => $processor,
                        'processor2' => $processor,
                    ],
                    ProcessorRegistry::TYPE_IMPORT_VALIDATION => [
                        'processor3' => $processor,
                        'processor4' => $processor,
                    ],
                    ProcessorRegistry::TYPE_EXPORT => [
                        'processor5' => $processor,
                        'processor6' => $processor,
                    ]
                ],
                ProcessorRegistry::TYPE_IMPORT,
                [
                    'processor1' => $processor,
                    'processor2' => $processor,
                ]
            ],
            [
                [
                    ProcessorRegistry::TYPE_IMPORT => [
                        'processor1' => $processor,
                        'processor2' => $processor,
                    ],
                    ProcessorRegistry::TYPE_IMPORT_VALIDATION => [
                        'processor3' => $processor,
                        'processor4' => $processor,
                    ],
                    ProcessorRegistry::TYPE_EXPORT => [
                        'processor5' => $processor,
                        'processor6' => $processor,
                    ]
                ],
                ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                [
                    'processor3' => $processor,
                    'processor4' => $processor,
                ]
            ],
            [
                [],
                ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                []
            ],
            [
                [
                    ProcessorRegistry::TYPE_EXPORT => [
                        'processor5' => $processor,
                        'processor6' => $processor,
                    ]
                ],
                'non_existing_type',
                []
            ]
        ];
    }
}
