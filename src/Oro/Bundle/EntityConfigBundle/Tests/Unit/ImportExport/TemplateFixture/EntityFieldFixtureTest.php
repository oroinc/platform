<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\TemplateFixture;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\TemplateFixture\EntityFieldFixture;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;

class EntityFieldFixtureTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = FieldConfigModel::class;

    /** @var FieldTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldTypeProvider;

    /** @var EntityFieldFixture */
    private $fixture;

    protected function setUp(): void
    {
        $this->fieldTypeProvider = $this->createMock(FieldTypeProvider::class);
        $this->fieldTypeProvider->expects($this->never())
            ->method('getSupportedRelationTypes');

        $this->fixture = new EntityFieldFixture($this->fieldTypeProvider);
    }

    public function testGetEntityClass()
    {
        $this->assertEquals(self::CLASS_NAME, $this->fixture->getEntityClass());
    }

    public function testGetEntity()
    {
        $this->assertInstanceOf(self::CLASS_NAME, $this->fixture->getEntity(null));
    }

    public function testGetData()
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
    public function testFillEntityData(string $type, object $entity, array $properties, object $expected)
    {
        $this->fieldTypeProvider->expects($entity instanceof FieldConfigModel ? $this->once() : $this->never())
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
            'no value found' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' => ['testScope' => ['test_code' => []]],
                'expected' => $this->createFieldConfigModel($type, ['testScope' => ['test_code' => 'test_code_value']])
            ],
            'enums' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' => [
                    'enum' => [
                        'enum_options' => []
                    ]
                ],
                'expected' => $this->createFieldConfigModel(
                    $type,
                    [
                        'enum' => [
                            'enum_options.0.label' => 'enum_label_0',
                            'enum_options.0.is_default' => 'yes',
                            'enum_options.1.label' => 'enum_label_1',
                            'enum_options.1.is_default' => 'no',
                            'enum_options.2.label' => 'enum_label_2',
                            'enum_options.2.is_default' => 'no',
                        ]
                    ]
                )
            ],
            'default_value' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' => ['testScope' => ['test_code' => ['options' => ['default_value' => 'def_value']]]],
                'expected' => $this->createFieldConfigModel($type, ['testScope' => ['test_code' => 'def_value']])
            ],
            'bool value' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' => ['testScope' => ['test_code' => ['options' => ['default_value' => true]]]],
                'expected' => $this->createFieldConfigModel($type, ['testScope' => ['test_code' => 'yes']])
            ],
            'first form choice' => [
                'type' => $type,
                'entity' => $this->createFieldConfigModel(),
                'properties' => [
                    'testScope' => [
                        'test_code' => [
                            'form' => [
                                'options' => [
                                    'choices' => [
                                        'choice1',
                                        'choice2'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'expected' => $this->createFieldConfigModel($type, ['testScope' => ['test_code' => 'choice1']])
            ]
        ];
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
}
