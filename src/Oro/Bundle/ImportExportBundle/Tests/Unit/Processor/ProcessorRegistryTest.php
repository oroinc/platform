<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor;

use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Exception\UnexpectedValueException;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareProcessor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProcessorRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = new ProcessorRegistry();
    }

    public function testRegisterProcessor()
    {
        $entityName = 'entity_name';
        $alias = 'processor_alias';

        $importProcessor = $this->createMock(ProcessorInterface::class);

        $exportProcessor = $this->createMock(EntityNameAwareProcessor::class);
        $exportProcessor->expects(self::once())
            ->method('setEntityName')
            ->with($entityName);

        $this->registry->registerProcessor($importProcessor, ProcessorRegistry::TYPE_IMPORT, $entityName, $alias);
        $this->registry->registerProcessor($exportProcessor, ProcessorRegistry::TYPE_EXPORT, $entityName, $alias);
        self::assertEquals(
            [
                ProcessorRegistry::TYPE_IMPORT => [$alias => $importProcessor],
                ProcessorRegistry::TYPE_EXPORT => [$alias => $exportProcessor],
            ],
            ReflectionUtil::getPropertyValue($this->registry, 'processors')
        );
        self::assertEquals(
            [
                $entityName => [
                    ProcessorRegistry::TYPE_IMPORT => [$alias => $importProcessor],
                    ProcessorRegistry::TYPE_EXPORT => [$alias => $exportProcessor]
                ]
            ],
            ReflectionUtil::getPropertyValue($this->registry, 'processorsByEntity')
        );
    }

    public function testRegisterProcessorFails()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Processor with type "import" and alias "processor_alias" already exists');

        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $alias = 'processor_alias';

        $processor = $this->createMock(ProcessorInterface::class);

        $this->registry->registerProcessor($processor, $type, $entityName, $alias);
        $this->registry->registerProcessor($processor, $type, $entityName, $alias);
    }

    public function testUnregisterProcessor()
    {
        $fooType = ProcessorRegistry::TYPE_IMPORT;
        $fooEntityName = 'foo_entity_name';
        $fooAlias = 'foo_processor_alias';

        $fooProcessor = $this->createMock(ProcessorInterface::class);

        $barType = ProcessorRegistry::TYPE_EXPORT;
        $barEntityName = 'bar_entity_name';
        $barAlias = 'bar_processor_alias';

        $barProcessor = $this->createMock(ProcessorInterface::class);

        $this->registry->registerProcessor($fooProcessor, $fooType, $fooEntityName, $fooAlias);
        $this->registry->registerProcessor($barProcessor, $barType, $barEntityName, $barAlias);
        $this->registry->unregisterProcessor($fooType, $fooEntityName, $fooAlias);
        self::assertEquals(
            [$fooType => [], $barType => [$barAlias => $barProcessor]],
            ReflectionUtil::getPropertyValue($this->registry, 'processors')
        );
        self::assertEquals(
            [
                $fooEntityName => [$fooType => []],
                $barEntityName => [$barType => [$barAlias => $barProcessor]],
            ],
            ReflectionUtil::getPropertyValue($this->registry, 'processorsByEntity')
        );
    }

    public function testHasProcessor()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $alias = 'processor_alias';

        $processor = $this->createMock(ProcessorInterface::class);

        self::assertFalse($this->registry->hasProcessor($type, $alias));
        $this->registry->registerProcessor($processor, $type, $entityName, $alias);
        self::assertTrue($this->registry->hasProcessor($type, $alias));
    }

    public function testGetProcessor()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $alias = 'processor_alias';

        $processor = $this->createMock(ProcessorInterface::class);

        $this->registry->registerProcessor($processor, $type, $entityName, $alias);
        self::assertSame($processor, $this->registry->getProcessor($type, $alias));
    }

    public function testGetProcessorFails()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Processor with type "import" and alias "processor_alias" is not exist');

        $this->registry->getProcessor('import', 'processor_alias');
    }

    public function testGetProcessorsByEntity()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $fooAlias = 'foo_alias';

        $fooProcessor = $this->createMock(ProcessorInterface::class);
        $barAlias = 'bar_alias';

        $barProcessor = $this->createMock(ProcessorInterface::class);

        $this->registry->registerProcessor($fooProcessor, $type, $entityName, $fooAlias);
        $this->registry->registerProcessor($barProcessor, $type, $entityName, $barAlias);

        self::assertEquals(
            [$fooAlias => $fooProcessor, $barAlias => $barProcessor],
            $this->registry->getProcessorsByEntity($type, $entityName)
        );
    }

    public function testGetProcessorsByEntityUnknown()
    {
        self::assertEquals(
            [],
            $this->registry->getProcessorsByEntity('unknown', 'unknown')
        );
    }

    public function testGetProcessorAliasesByEntityUnknown()
    {
        self::assertEquals(
            [],
            $this->registry->getProcessorAliasesByEntity('unknown', 'unknown')
        );
    }

    public function testGetProcessorAliasesByEntity()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $fooAlias = 'foo_alias';
        $fooProcessor = $this->createMock(ProcessorInterface::class);
        $barAlias = 'bar_alias';
        $barProcessor = $this->createMock(ProcessorInterface::class);

        $this->registry->registerProcessor($fooProcessor, $type, $entityName, $fooAlias);
        $this->registry->registerProcessor($barProcessor, $type, $entityName, $barAlias);

        self::assertEquals(
            [$fooAlias, $barAlias],
            $this->registry->getProcessorAliasesByEntity($type, $entityName)
        );
    }

    public function testGetProcessorEntityName()
    {
        $type = ProcessorRegistry::TYPE_IMPORT;
        $entityName = 'entity_name';
        $alias = 'foo_alias';
        $processor = $this->createMock(ProcessorInterface::class);

        $this->registry->registerProcessor($processor, $type, $entityName, $alias);

        self::assertEquals($entityName, $this->registry->getProcessorEntityName($type, $alias));
    }

    public function testGetProcessorEntityNameFails()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Processor with type "import" and alias "foo_alias" is not exist');

        $type = ProcessorRegistry::TYPE_IMPORT;
        $alias = 'foo_alias';
        $this->registry->getProcessorEntityName($type, $alias);
    }

    /**
     * @dataProvider processorsByTypeDataProvider
     */
    public function testGetProcessorsByType(array $processors, string $type, array $expected)
    {
        foreach ($processors as $processorType => $processorsByType) {
            foreach ($processorsByType as $processorName => $processor) {
                $this->registry->registerProcessor($processor, $processorType, \stdClass::class, $processorName);
            }
        }

        self::assertEquals(
            $expected,
            $this->registry->getProcessorsByType($type)
        );
    }

    public function processorsByTypeDataProvider(): array
    {
        $processor = $this->createMock(ProcessorInterface::class);

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
