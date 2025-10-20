<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\ImportExportBundle\Event\DenormalizeEntityEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Normalizer\Stub\DenormalizationStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

class ConfigurableEntityNormalizerTest extends TestCase
{
    private FieldHelper&MockObject $fieldHelper;
    private EventDispatcher&MockObject $eventDispatcher;
    private ConfigurableEntityNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $fieldProvider = $this->createMock(EntityFieldProvider::class);
        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn([]);
        $fieldTypeHelper = new FieldTypeHelper($entityExtendConfigurationProvider);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->setConstructorArgs([
                $fieldProvider,
                $configProvider,
                $fieldTypeHelper,
                $propertyAccessor,
                $this->createMock(EnumOptionsProvider::class)
            ])
            ->onlyMethods(['hasConfig', 'getConfigValue', 'getEntityFields', 'getObjectValue'])
            ->getMock();

        $this->eventDispatcher = $this->createMock(EventDispatcher::class);

        $this->normalizer = new ConfigurableEntityNormalizer($this->fieldHelper);
        $this->normalizer->setScalarFieldDenormalizer(new ScalarFieldDenormalizer());

        $this->normalizer->setDispatcher($this->eventDispatcher);
    }

    /**
     * @dataProvider supportDenormalizationDataProvider
     */
    public function testSupportsDenormalization(mixed $data, string $type, bool $hasConfig, bool $isSupported): void
    {
        if (is_array($data) && class_exists($type)) {
            $this->fieldHelper->expects(self::once())
                ->method('hasConfig')
                ->willReturn($hasConfig);
        } else {
            $this->fieldHelper->expects(self::never())
                ->method('hasConfig');
        }
        self::assertEquals($isSupported, $this->normalizer->supportsDenormalization($data, $type));
    }

    public function supportDenormalizationDataProvider(): array
    {
        return [
            [null, '', false, false],
            ['test', '', false, false],
            ['test', 'stdClass', false, false],
            [[], '', false, false],
            [[], 'stdClass', false, false],
            [[], 'stdClass', true, true],
        ];
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     */
    public function testSupportsNormalization(mixed $data, bool $hasConfig, bool $isSupported): void
    {
        if (is_object($data)) {
            $this->fieldHelper->expects(self::once())
                ->method('hasConfig')
                ->willReturn($hasConfig);
        } else {
            $this->fieldHelper->expects(self::never())
                ->method('hasConfig');
        }

        self::assertEquals($isSupported, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationDataProvider(): array
    {
        return [
            [null, false, false],
            [null, true, false],
            ['test', false, false],
            ['test', true, false],
            [new \stdClass(), false, false],
            [new \stdClass(), true, true],
        ];
    }

    public function testSetSerializerException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Serializer must implement "%s" and "%s"',
            NormalizerInterface::class,
            DenormalizerInterface::class
        ));

        $serializer = $this->createMock(SerializerInterface::class);
        $this->normalizer->setSerializer($serializer);
    }

    public function testSetSerializer(): void
    {
        $serializer = $this->createMock(Serializer::class);
        $this->normalizer->setSerializer($serializer);
        self::assertSame($serializer, ReflectionUtil::getPropertyValue($this->normalizer, 'serializer'));
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(
        object $object,
        array $context,
        array $fields,
        array $fieldsImportConfig,
        array $result
    ): void {
        $format = null;
        $entityName = get_class($object);

        $fieldsValueMap = [
            $entityName => $fields,
            'DateTime' => [],
        ];

        $this->fieldHelper->expects(self::atLeastOnce())
            ->method('getEntityFields')
            ->willReturnCallback(function ($className) use ($fieldsValueMap) {
                if (empty($fieldsValueMap[$className])) {
                    return [];
                }

                return $fieldsValueMap[$className];
            });
        $this->fieldHelper->expects(self::any())
            ->method('getObjectValue')
            ->willReturnCallback(function ($object, $field) {
                return PropertyAccess::createPropertyAccessor()->getValue($object, $field);
            });

        $configValueMap = [];
        $normalizedMap = [];
        $hasConfigMap = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'];

            if (isset($field['normalizedValue'])) {
                $fieldValue = $object->{$fieldName};
                $fieldContext = $field['fieldContext'] ?? $context;
                $normalizedMap[] = [$fieldValue, null, $fieldContext, $field['normalizedValue']];
            }

            if (isset($field['related_entity_type'])) {
                $hasConfigMap[] = [$field['related_entity_type'], true];
            }

            foreach ($fieldsImportConfig[$fieldName] as $configKey => $configValue) {
                $configValueMap[] = [$entityName, $fieldName, $configKey, null, $configValue];
            }
        }
        $this->fieldHelper->expects(self::any())
            ->method('getConfigValue')
            ->willReturnMap($configValueMap);
        if ($hasConfigMap) {
            $this->fieldHelper->expects(self::any())
                ->method('hasConfig')
                ->willReturn($hasConfigMap);
        }

        $serializer = $this->createMock(Serializer::class);
        if ($normalizedMap) {
            $serializer->expects(self::atLeastOnce())
                ->method('normalize')
                ->willReturnMap($normalizedMap);
        }
        $this->normalizer->setSerializer($serializer);

        $this->eventDispatcher->expects(self::any())
            ->method('hasListeners')
            ->willReturn(false);

        self::assertEquals($result, $this->normalizer->normalize($object, $format, $context));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function normalizeDataProvider(): array
    {
        $object = (object)[
            'fieldString' => 'string',
            'excluded' => 'excluded',
            'id' => 'id',
            'nonId' => 'nonId',
            'objectNoIds' => new \DateTime(),
        ];
        $object->relatedObjectWithId = clone $object;

        return [
            'simple' => [
                $object,
                [],
                [
                    [
                        'name' => 'fieldString',
                    ],
                ],
                [
                    'fieldString' => [
                        'excluded' => false,
                    ],
                ],
                [
                    'fieldString' => 'string',
                ],
            ],
            'simple_with_excluded' => [
                $object,
                [],
                [
                    [
                        'name' => 'fieldString',
                    ],
                    [
                        'name' => 'id',
                    ],
                ],
                [
                    'fieldString' => [
                        'excluded' => true,
                    ],
                    'id' => [
                        'excluded' => false,
                    ],
                ],
                [
                    'id' => 'id',
                ],
            ],
            'with_identity' => [
                $object,
                [
                    'mode' => ConfigurableEntityNormalizer::SHORT_MODE,
                ],
                [
                    [
                        'name' => 'fieldString',
                    ],
                    [
                        'name' => 'nonId',
                    ],
                    [
                        'name' => 'id',
                    ],
                ],
                [
                    'fieldString' => [
                        'excluded' => false,
                    ],
                    'nonId' => [
                        'identity' => false,
                    ],
                    'id' => [
                        'identity' => true,
                    ],
                ],
                [
                    'id' => 'id',
                ],
            ],
            'with_object_full_non_identity' => [
                $object,
                [],
                [
                    [
                        'name' => 'relatedObjectWithId',
                        'normalizedValue' => 'obj1',
                        'related_entity_type' => 'stdClass',
                        'related_entity_name' => 'stdClass',
                        'relation_type' => 'ref-one',
                        'fieldContext' => [
                            'fieldName' => 'relatedObjectWithId',
                            'mode' => ConfigurableEntityNormalizer::FULL_MODE,
                        ],
                    ],
                    [
                        'name' => 'objectNoIds',
                        'normalizedValue' => 'obj2',
                        'related_entity_type' => 'DateTime',
                        'related_entity_name' => 'DateTime',
                        'relation_type' => 'ref-one',
                        'fieldContext' => [
                            'fieldName' => 'objectNoIds',
                            'mode' => ConfigurableEntityNormalizer::FULL_MODE,
                        ],
                    ],
                    [
                        'name' => 'id',
                    ],
                ],
                [
                    'relatedObjectWithId' => [
                        'full' => true,
                    ],
                    'objectNoIds' => [
                        'full' => true,
                    ],
                    'id' => [
                        'identity' => true,
                    ],
                ],
                [
                    'id' => 'id',
                    'relatedObjectWithId' => 'obj1',
                    'objectNoIds' => 'obj2',
                ],
            ],
            'object_relation_short_with_non_identity' => [
                $object,
                [],
                [
                    [
                        'name' => 'relatedObjectWithId',
                        'normalizedValue' => 'obj1',
                        'fieldContext' => [
                            'fieldName' => 'relatedObjectWithId',
                            'mode' => ConfigurableEntityNormalizer::SHORT_MODE,
                        ],
                        'related_entity_type' => 'stdClass',
                        'related_entity_name' => 'stdClass',
                        'relation_type' => 'ref-one',
                    ],
                    [
                        'name' => 'objectNoIds',
                        'normalizedValue' => 'obj2',
                        'fieldContext' => [
                            'fieldName' => 'objectNoIds',
                            'mode' => ConfigurableEntityNormalizer::SHORT_MODE,
                        ],
                        'related_entity_type' => 'DateTime',
                        'related_entity_name' => 'DateTime',
                        'relation_type' => 'ref-one',
                    ],
                    [
                        'name' => 'id',
                    ],
                ],
                [
                    'relatedObjectWithId' => [
                    ],
                    'objectNoIds' => [
                    ],
                    'id' => [
                        'identity' => true,
                    ],
                ],
                [
                    'id' => 'id',
                    'relatedObjectWithId' => 'obj1',
                ],
            ],
        ];
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(array $data, string $class, array $fields, object $expected): void
    {
        $serializer = $this->createMock(Serializer::class);
        $this->normalizer->setSerializer($serializer);

        $this->fieldHelper->expects(self::once())
            ->method('getEntityFields')
            ->with($class, EntityFieldProvider::OPTION_WITH_RELATIONS)
            ->willReturn($fields);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(DenormalizeEntityEvent::class), Events::BEFORE_DENORMALIZE_ENTITY],
                [self::isInstanceOf(DenormalizeEntityEvent::class), Events::AFTER_DENORMALIZE_ENTITY]
            )
            ->willReturnOnConsecutiveCalls(
                new DenormalizeEntityEvent(new $class(), $data),
                new DenormalizeEntityEvent($expected, $data)
            );

        $denormalizedMap = [];

        foreach ($fields as $field) {
            if (isset($field['denormalizedValue'])) {
                $fieldContext = $this->normalizer->prepareFieldContextForDenormalization(
                    [],
                    $field['name'],
                    $class,
                    $field
                );

                $denormalizedMap[] = [
                    $data[$field['name']],
                    $field['expectedEntityClass'],
                    null,
                    $fieldContext,
                    $field['denormalizedValue']
                ];
            }
        }

        if ($denormalizedMap) {
            $serializer->expects(self::atLeastOnce())
                ->method('denormalize')
                ->willReturnMap($denormalizedMap);
        }

        foreach ($fields as $field) {
            $fieldName = $field['name'];
            if (isset($data[$fieldName]) && $field['type'] == 'boolean') {
                if ($data[$fieldName] === 'false') {
                    $this->fieldHelper->setObjectValue($expected, $fieldName, false);
                } elseif ($data[$fieldName] === 'true') {
                    $this->fieldHelper->setObjectValue($expected, $fieldName, true);
                } else {
                    $this->fieldHelper->setObjectValue($expected, $fieldName, (bool)$data[$fieldName]);
                }
            }
        }

        $result = $this->normalizer->denormalize($data, $class, null, []);

        self::assertEquals($expected, $result);
    }

    public function denormalizeDataProvider(): array
    {
        $expected = new DenormalizationStub();
        $expected->id = 100;
        $expected->name = 'test';
        $expected->created = 'dDateTime';
        $expected->birthday = 'dDate';
        $expected->time = 'dTime';
        $expected->obj = 'dObj';
        $expected->collection = 'dCollection';

        return [
            [
                [
                    'id' => '1e2',
                    'name' => 'test',
                    'created' => new \DateTime('2011-11-11'),
                    'birthday' => new \DateTime('2011-11-11'),
                    'time' => new \DateTime('2011-11-11 12:12:12'),
                    'obj' => (object)['key' => 'val'],
                    'obj2' => [],
                    'collection' => [1, 2],
                    'unknown' => 'not_included',
                ],
                DenormalizationStub::class,
                [
                    [
                        'name' => 'id',
                        'type' => 'integer',
                    ],
                    [
                        'name' => 'name',
                        'type' => 'string',
                    ],
                    [
                        'name' => 'created',
                        'related_entity_name' => 'DateTime',
                        'relation_type' => null,
                        'type' => 'datetime',
                        'denormalizedValue' => 'dDateTime',
                        'expectedEntityClass' => 'DateTime',
                    ],
                    [
                        'name' => 'birthday',
                        'related_entity_name' => 'DateTime',
                        'relation_type' => null,
                        'type' => 'date',
                        'denormalizedValue' => 'dDate',
                        'expectedEntityClass' => 'DateTime',
                    ],
                    [
                        'name' => 'time',
                        'related_entity_name' => 'DateTime',
                        'relation_type' => null,
                        'type' => 'time',
                        'denormalizedValue' => 'dTime',
                        'expectedEntityClass' => 'DateTime',
                    ],
                    [
                        'name' => 'obj',
                        'related_entity_name' => 'stdClass',
                        'relation_type' => 'ref-one',
                        'denormalizedValue' => 'dObj',
                        'expectedEntityClass' => 'stdClass',
                        'type' => 'object',
                    ],
                    [
                        'name' => 'obj2',
                        'related_entity_name' => 'stdClass',
                        'relation_type' => 'ref-one',
                        'denormalizedValue' => 'dObj',
                        'expectedEntityClass' => 'stdClass',
                        'type' => 'object',
                    ],
                    [
                        'name' => 'collection',
                        'related_entity_name' => 'stdClass',
                        'relation_type' => 'ref-many',
                        'denormalizedValue' => 'dCollection',
                        'expectedEntityClass' => 'ArrayCollection<stdClass>',
                        'type' => 'object',
                    ],
                ],
                $expected,
            ],
        ];
    }
}
