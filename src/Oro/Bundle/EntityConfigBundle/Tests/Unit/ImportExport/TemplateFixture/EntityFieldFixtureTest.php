<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\TemplateFixture;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeConfigurationInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\TemplateFixture\EntityFieldFixture;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;

class EntityFieldFixtureTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = FieldConfigModel::class;

    /** @var FieldTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldTypeProvider;

    /** @var AttributeTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $typeRegistry;

    private EntityFieldFixture $fixture;

    protected function setUp(): void
    {
        $this->fieldTypeProvider = $this->createMock(FieldTypeProvider::class);
        $this->typeRegistry = $this->createMock(AttributeTypeRegistry::class);
        $this->fieldTypeProvider->expects($this->never())
            ->method('getSupportedRelationTypes');

        $this->fixture = new EntityFieldFixture($this->fieldTypeProvider, $this->typeRegistry);
    }

    private function createFieldConfigModel(string $type = null, array $data = []): FieldConfigModel
    {
        $entity = new FieldConfigModel();

        if ($type) {
            $entity
                ->setType($type)
                ->setFieldName('field_' . $type);
        }

        foreach ($data as $scope => $values) {
            $entity->fromArray($scope, $values, []);
        }

        return $entity;
    }

    public function testGetEntityClass(): void
    {
        $this->assertEquals(self::CLASS_NAME, $this->fixture->getEntityClass());
    }

    public function testGetEntity(): void
    {
        $this->assertInstanceOf(self::CLASS_NAME, $this->fixture->getEntity(null));
    }

    public function testGetData(): void
    {
        $this->fieldTypeProvider->expects($this->once())
            ->method('getSupportedFieldTypes')
            ->willReturn([]);

        $data = $this->fixture->getData();

        $this->assertInstanceOf(\ArrayIterator::class, $data);
    }

    /**
     * @dataProvider fillEntityDataDataProvider
     */
    public function testFillEntityData(string $type, object $entity, array $properties, object $expected): void
    {
        $this->fieldTypeProvider->expects($entity instanceof FieldConfigModel ? self::once() : self::never())
            ->method('getFieldProperties')
            ->with($type)
            ->willReturn($properties);

        $this->fixture->fillEntityData($type, $entity);

        $this->assertEquals($expected, $entity);
    }

    public function fillEntityDataDataProvider(): array
    {
        $type = 'test';

        return [
            'invalid entity class' => [
                'type' => $type,
                'entity' => new \stdClass(),
                'properties' => [],
                'expected' => new \stdClass()
            ],
            'no properties found' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' => [],
                'expected' => $this->createFieldConfigModel($type)
            ],
            'excluded from import property' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' => ['testScope' => ['test_code' => ['options' => ['default_value' => 'def_value']]]],
                'expected' => $this->createFieldConfigModel($type)
            ],
            'simple value property' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' =>
                    [
                        'testScope' => ['test_code' => ['import_export' => ['import_template' => ['value' => 1]]]]
                    ],
                'expected' => $this->createFieldConfigModel(
                    $type,
                    [
                        'testScope' => [
                            'test_code' => 1
                        ]
                    ]
                )
            ],
            'string value property with type marker' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' =>
                    [
                        'testScope' => [
                            'test_code' => ['import_export' => ['import_template' => ['value' => '*type* value']]]
                        ]
                    ],
                'expected' => $this->createFieldConfigModel(
                    $type,
                    [
                        'testScope' => [
                            'test_code' => 'test value'
                        ]
                    ]
                )
            ],
            'array value property' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' =>
                    [
                        'testScope' => [
                            'test_code' => [
                                'import_export' => [
                                    'import_template' => [
                                        'value' => [
                                            ['label' => 'item 1', 'value' => 1],
                                            ['label' => 'item 2', 'value' => 2]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                'expected' => $this->createFieldConfigModel(
                    $type,
                    [
                        'testScope' => [
                            'test_code.0.label' => 'item 1',
                            'test_code.0.value' => 1,
                            'test_code.1.label' => 'item 2',
                            'test_code.1.value' => 2
                        ]
                    ]
                )
            ]
        ];
    }


    /**
     * @dataProvider fillEntityDataForSearchableAttributeDataProvider
     */
    public function testFillEntityDataForSearchableAttribute(
        array $properties,
        string $expectedMethod,
        mixed $methodValue,
        array $expectedConfig
    ): void {
        $entity = $this->createFieldConfigModel();
        $attributeType = $this->createMock(AttributeConfigurationInterface::class);

        $this->fieldTypeProvider->expects(self::once())
            ->method('getFieldProperties')
            ->with('test')
            ->willReturn($properties);

        $this->typeRegistry->expects(self::once())
            ->method('getAttributeType')
            ->with($entity)
            ->willReturn($attributeType);

        $attributeType->expects(self::once())
            ->method($expectedMethod)
            ->with($entity)
            ->willReturn($methodValue);

        $this->fixture->fillEntityData('test', $entity);

        $this->assertEquals(
            $this->createFieldConfigModel(
                'test',
                $expectedConfig
            ),
            $entity
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function fillEntityDataForSearchableAttributeDataProvider(): array
    {
        return [
            'searchable attribute' => [
                [
                    'attribute' => [
                        'searchable' => [
                            'import_export' => [
                                'import_template' => [
                                    'value' => 'Yes'
                                ]
                            ]
                        ]
                    ]
                ],
                'isSearchable',
                true,
                [
                    'attribute' => [
                        'searchable' => 'Yes'
                    ]
                ]
            ],
            'not searchable attribute' => [
                [
                    'attribute' => [
                        'searchable' => [
                            'import_export' => [
                                'import_template' => [
                                    'value' => 'Yes'
                                ]
                            ]
                        ]
                    ]
                ],
                'isSearchable',
                false,
                []
            ],
            'filterable attribute' => [
                [
                    'attribute' => [
                        'filterable' => [
                            'import_export' => [
                                'import_template' => [
                                    'value' => 'Yes'
                                ]
                            ]
                        ]
                    ]
                ],
                'isFilterable',
                true,
                [
                    'attribute' => [
                        'filterable' => 'Yes'
                    ]
                ]
            ],
            'not filterable attribute' => [
                [
                    'attribute' => [
                        'filterable' => [
                            'import_export' => [
                                'import_template' => [
                                    'value' => 'Yes'
                                ]
                            ]
                        ]
                    ]
                ],
                'isFilterable',
                false,
                []
            ],
            'filter_by attribute' => [
                [
                    'attribute' => [
                        'filter_by' => [
                            'import_export' => [
                                'import_template' => [
                                    'value' => 'exact_value'
                                ]
                            ]
                        ]
                    ]
                ],
                'isFilterable',
                true,
                [
                    'attribute' => [
                        'filter_by' => 'exact_value'
                    ]
                ]
            ],
            'not filterable filter_by attribute' => [
                [
                    'attribute' => [
                        'filter_by' => [
                            'import_export' => [
                                'import_template' => [
                                    'value' => 'Yes'
                                ]
                            ]
                        ]
                    ]
                ],
                'isFilterable',
                false,
                []
            ],
            'sortable attribute' => [
                [
                    'attribute' => [
                        'sortable' => [
                            'import_export' => [
                                'import_template' => [
                                    'value' => 'Yes'
                                ]
                            ]
                        ]
                    ]
                ],
                'isSortable',
                true,
                [
                    'attribute' => [
                        'sortable' => 'Yes'
                    ]
                ]
            ],
            'not sortable attribute' => [
                [
                    'attribute' => [
                        'sortable' => [
                            'import_export' => [
                                'import_template' => [
                                    'value' => 'Yes'
                                ]
                            ]
                        ]
                    ]
                ],
                'isSortable',
                false,
                []
            ]
        ];
    }
}
