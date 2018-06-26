<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\ImportExport\TemplateFixture;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\TemplateFixture\EntityFieldFixture;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;

class EntityFieldFixtureTest extends \PHPUnit\Framework\TestCase
{
    const CLASS_NAME = 'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel';

    /** @var \PHPUnit\Framework\MockObject\MockObject|FieldTypeProvider */
    protected $fieldTypeProvider;

    /** @var EntityFieldFixture */
    protected $fixture;

    protected function setUp()
    {
        $this->fieldTypeProvider = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldTypeProvider->expects($this->never())
            ->method('getSupportedRelationTypes');

        $this->fixture = new EntityFieldFixture($this->fieldTypeProvider);
    }

    protected function tearDown()
    {
        unset($this->fixture, $this->fieldTypeProvider);
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

        $this->assertInstanceOf('\ArrayIterator', $data);
    }

    /**
     * @dataProvider fillEntityDataDataProvider
     *
     * @param string $type
     * @param object $entity
     * @param array $properties
     * @param object $expected
     */
    public function testFillEntityData($type, $entity, array $properties, $expected)
    {
        $this->fieldTypeProvider->expects($entity instanceof FieldConfigModel ? $this->once() : $this->never())
            ->method('getFieldProperties')
            ->with($type)
            ->willReturn($properties);

        $this->fixture->fillEntityData($type, $entity);

        $this->assertEquals($expected, $entity);
    }

    /**
     * @return array
     */
    public function fillEntityDataDataProvider()
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

    /**
     * @param string|null $type
     * @param array $data
     * @return FieldConfigModel
     */
    protected function createFieldConfigModel($type = null, array $data = [])
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
