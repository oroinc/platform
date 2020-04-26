<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Normalizer;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Configuration\EntityExtendConfigurationProvider;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ScalarFieldDenormalizer;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Serializer\Normalizer\Stub\DenormalizationStub;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class ConfigurableEntityNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FieldHelper|MockObject
     */
    protected $fieldHelper;

    /**
     * @var ConfigurableEntityNormalizer
     */
    protected $normalizer;

    protected function setUp(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $fieldProvider = $this->createMock(EntityFieldProvider::class);
        $entityExtendConfigurationProvider = $this->createMock(EntityExtendConfigurationProvider::class);
        $entityExtendConfigurationProvider->expects(self::any())
            ->method('getUnderlyingTypes')
            ->willReturn([]);
        $fieldTypeHelper = new FieldTypeHelper($entityExtendConfigurationProvider);

        $this->fieldHelper = $this->getMockBuilder(FieldHelper::class)
            ->setConstructorArgs([$fieldProvider, $configProvider, $fieldTypeHelper])
            ->onlyMethods(['hasConfig', 'getConfigValue', 'getFields', 'getObjectValue'])
            ->getMock();

        $this->normalizer = new class($this->fieldHelper) extends ConfigurableEntityNormalizer {
            public function xgetSerializer()
            {
                return $this->serializer;
            }
        };
        $this->normalizer->setScalarFieldDenormalizer(new ScalarFieldDenormalizer());
    }

    /**
     * @dataProvider supportDenormalizationDataProvider
     * @param mixed $data
     * @param string $type
     * @param bool $hasConfig
     * @param bool $isSupported
     */
    public function testSupportsDenormalization($data, $type, $hasConfig, $isSupported)
    {
        if (is_array($data) && class_exists($type)) {
            $this->fieldHelper->expects($this->once())
                ->method('hasConfig')
                ->will($this->returnValue($hasConfig));
        } else {
            $this->fieldHelper->expects($this->never())
                ->method('hasConfig');
        }
        $this->assertEquals($isSupported, $this->normalizer->supportsDenormalization($data, $type));
    }

    /**
     * @return array
     */
    public function supportDenormalizationDataProvider()
    {
        return [
            [null, null, false, false],
            ['test', null, false, false],
            ['test', 'stdClass', false, false],
            [[], null, false, false],
            [[], 'stdClass', false, false],
            [[], 'stdClass', true, true]
        ];
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     * @param mixed $data
     * @param bool $hasConfig
     * @param bool $isSupported
     */
    public function testSupportsNormalization($data, $hasConfig, $isSupported)
    {
        if (is_object($data)) {
            $this->fieldHelper->expects($this->once())
                ->method('hasConfig')
                ->will($this->returnValue($hasConfig));
        } else {
            $this->fieldHelper->expects($this->never())
                ->method('hasConfig');
        }

        $this->assertEquals($isSupported, $this->normalizer->supportsNormalization($data));
    }

    /**
     * @return array
     */
    public function supportsNormalizationDataProvider()
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

    public function testSetSerializerException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Serializer must implement "%s" and "%s"',
            NormalizerInterface::class,
            DenormalizerInterface::class
        ));

        $serializer = $this->createMock(\Symfony\Component\Serializer\Serializer::class);
        $this->normalizer->setSerializer($serializer);
    }

    public function testSetSerializer()
    {
        $serializer = $this->createMock(Serializer::class);
        $this->normalizer->setSerializer($serializer);
        static::assertSame($serializer, $this->normalizer->xgetSerializer());
    }

    /**
     * @dataProvider normalizeDataProvider
     * @param object $object
     * @param array $context
     * @param array $fields
     * @param array $fieldsImportConfig
     * @param array $result
     */
    public function testNormalize($object, $context, $fields, $fieldsImportConfig, $result)
    {
        $format = null;
        $entityName = get_class($object);

        $fieldsValueMap = [
            $entityName => $fields,
            'DateTime' => []
        ];

        $this->fieldHelper->expects($this->atLeastOnce())
            ->method('getFields')
            ->will(
                $this->returnCallback(
                    function ($className) use ($fieldsValueMap) {
                        if (empty($fieldsValueMap[$className])) {
                            return [];
                        }

                        return $fieldsValueMap[$className];
                    }
                )
            );
        $this->fieldHelper->expects($this->any())
            ->method('getObjectValue')
            ->will(
                $this->returnCallback(
                    function ($object, $field) {
                        $propertyAccessor = PropertyAccess::createPropertyAccessor();
                        return $propertyAccessor->getValue($object, $field);
                    }
                )
            );

        $configValueMap = [];
        $normalizedMap = [];
        $hasConfigMap = [];
        foreach ($fields as $field) {
            $fieldName = $field['name'];

            if (isset($field['normalizedValue'])) {
                $fieldValue = $object->$fieldName;
                $fieldContext = $context;
                if (isset($field['fieldContext'])) {
                    $fieldContext = $field['fieldContext'];
                }
                $normalizedMap[] = [$fieldValue, null, $fieldContext, $field['normalizedValue']];
            }

            if (isset($field['related_entity_type'])) {
                $hasConfigMap[] = [$field['related_entity_type'], true];
            }

            foreach ($fieldsImportConfig[$fieldName] as $configKey => $configValue) {
                $configValueMap[] = [$entityName, $fieldName, $configKey, null, $configValue];
            }
        }
        $this->fieldHelper->expects($this->any())
            ->method('getConfigValue')
            ->will($this->returnValueMap($configValueMap));
        if ($hasConfigMap) {
            $this->fieldHelper->expects($this->any())
                ->method('hasConfig')
                ->will($this->returnValue($hasConfigMap));
        }

        $serializer = $this->createMock(Serializer::class);
        if ($normalizedMap) {
            $serializer->expects($this->atLeastOnce())
                ->method('normalize')
                ->will($this->returnValueMap($normalizedMap));
        }
        $this->normalizer->setSerializer($serializer);

        $this->assertEquals($result, $this->normalizer->normalize($object, $format, $context));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function normalizeDataProvider()
    {
        $object = (object) [
            'fieldString' => 'string',
            'excluded' => 'excluded',
            'id' => 'id',
            'nonId' => 'nonId',
            'objectNoIds' => new \DateTime()
        ];
        $object->relatedObjectWithId = clone $object;

        return [
            'simple' => [
                $object,
                [],
                [
                    [
                        'name' => 'fieldString'
                    ]
                ],
                [
                    'fieldString' => [
                        'excluded' => false
                    ]
                ],
                [
                    'fieldString' => 'string'
                ]
            ],
            'simple_with_excluded' => [
                $object,
                [],
                [
                    [
                        'name' => 'fieldString'
                    ],
                    [
                        'name' => 'id'
                    ]
                ],
                [
                    'fieldString' => [
                        'excluded' => true
                    ],
                    'id' => [
                        'excluded' => false
                    ]
                ],
                [
                    'id' => 'id'
                ]
            ],
            'with_identity' => [
                $object,
                [
                    'mode' => ConfigurableEntityNormalizer::SHORT_MODE
                ],
                [
                    [
                        'name' => 'fieldString'
                    ],
                    [
                        'name' => 'nonId'
                    ],
                    [
                        'name' => 'id'
                    ]
                ],
                [
                    'fieldString' => [
                        'excluded' => false
                    ],
                    'nonId' => [
                        'identity' => false,
                    ],
                    'id' => [
                        'identity' => true,
                    ]
                ],
                [
                    'id' => 'id'
                ]
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
                            'mode' => ConfigurableEntityNormalizer::FULL_MODE
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
                            'mode' => ConfigurableEntityNormalizer::FULL_MODE
                        ],
                    ],
                    [
                        'name' => 'id'
                    ]
                ],
                [
                    'relatedObjectWithId' => [
                        'full' => true
                    ],
                    'objectNoIds' => [
                        'full' => true
                    ],
                    'id' => [
                        'identity' => true,
                    ]
                ],
                [
                    'id' => 'id',
                    'relatedObjectWithId' => 'obj1',
                    'objectNoIds' => 'obj2'
                ]
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
                            'mode' => ConfigurableEntityNormalizer::SHORT_MODE
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
                            'mode' => ConfigurableEntityNormalizer::SHORT_MODE
                        ],
                        'related_entity_type' => 'DateTime',
                        'related_entity_name' => 'DateTime',
                        'relation_type' => 'ref-one',
                    ],
                    [
                        'name' => 'id'
                    ]
                ],
                [
                    'relatedObjectWithId' => [
                    ],
                    'objectNoIds' => [
                    ],
                    'id' => [
                        'identity' => true,
                    ]
                ],
                [
                    'id' => 'id',
                    'relatedObjectWithId' => 'obj1'
                ]
            ],
        ];
    }

    /**
     * @dataProvider denormalizeDataProvider
     * @param array $data
     * @param string $class
     * @param array $fields
     * @param object $expected
     */
    public function testDenormalize($data, $class, $fields, $expected)
    {
        $context = [];

        $denormalizedMap = [];

        foreach ($fields as $field) {
            $fieldName = $field['name'];

            if (isset($field['denormalizedValue'])) {
                $fieldValue = $data[$fieldName];
                $entityClass = $field['expectedEntityClass'];
                $context = array_merge($context, ['fieldName' => $fieldName]);
                if (array_key_exists('type', $field) && in_array($field['type'], ['date', 'datetime', 'time'], true)) {
                    $context = array_merge($context, ['type' => $field['type']]);
                }
                $denormalizedMap[] = [$fieldValue, $entityClass, null, $context, $field['denormalizedValue']];
            }
        }

        $serializer = $this->createMock(Serializer::class);
        if ($denormalizedMap) {
            $serializer->expects($this->atLeastOnce())
                ->method('denormalize')
                ->will($this->returnValueMap($denormalizedMap));
        }
        $this->normalizer->setSerializer($serializer);

        $this->fieldHelper->expects($this->atLeastOnce())
            ->method('getFields')
            ->will($this->returnValue($fields));

        $this->assertEquals($expected, $this->normalizer->denormalize($data, $class, null, $context));
    }

    /**
     * @return array
     */
    public function denormalizeDataProvider()
    {
        $expected = new Stub\DenormalizationStub();
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
                    'obj' => (object) ['key' => 'val'],
                    'obj2' => [],
                    'collection' => [1, 2],
                    'unknown' => 'not_included'
                ],
                DenormalizationStub::class,
                [
                    [
                        'name' => 'id',
                        'type' => 'integer'
                    ],
                    [
                        'name' => 'name',
                        'type' => 'string'
                    ],
                    [
                        'name' => 'created',
                        'related_entity_name' => 'DateTime',
                        'relation_type' => null,
                        'type' => 'datetime',
                        'denormalizedValue' => 'dDateTime',
                        'expectedEntityClass' => 'DateTime'
                    ],
                    [
                        'name' => 'birthday',
                        'related_entity_name' => 'DateTime',
                        'relation_type' => null,
                        'type' => 'date',
                        'denormalizedValue' => 'dDate',
                        'expectedEntityClass' => 'DateTime'
                    ],
                    [
                        'name' => 'time',
                        'related_entity_name' => 'DateTime',
                        'relation_type' => null,
                        'type' => 'time',
                        'denormalizedValue' => 'dTime',
                        'expectedEntityClass' => 'DateTime'
                    ],
                    [
                        'name' => 'obj',
                        'related_entity_name' => 'stdClass',
                        'relation_type' => 'ref-one',
                        'denormalizedValue' => 'dObj',
                        'expectedEntityClass' => 'stdClass',
                        'type' => 'object'
                    ],
                    [
                        'name' => 'obj2',
                        'related_entity_name' => 'stdClass',
                        'relation_type' => 'ref-one',
                        'denormalizedValue' => 'dObj',
                        'expectedEntityClass' => 'stdClass',
                        'type' => 'object'
                    ],
                    [
                        'name' => 'collection',
                        'related_entity_name' => 'stdClass',
                        'relation_type' => 'ref-many',
                        'denormalizedValue' => 'dCollection',
                        'expectedEntityClass' => 'ArrayCollection<stdClass>',
                        'type' => 'object'
                    ],
                ],
                $expected
            ]
        ];
    }
}
