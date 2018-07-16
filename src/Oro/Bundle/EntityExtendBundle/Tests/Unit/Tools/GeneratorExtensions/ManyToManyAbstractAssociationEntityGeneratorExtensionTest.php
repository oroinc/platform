<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\GeneratorExtensions;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;

class ManyToManyAbstractAssociationEntityGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    const ASSOCIATION_KIND = 'test';

    /** @var AbstractAssociationEntityGeneratorExtension|\PHPUnit\Framework\MockObject\MockObject */
    protected $extension;

    public function setUp()
    {
        $this->extension = $this->getMockForAbstractClass(
            'Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension',
            [],
            '',
            true,
            true,
            true,
            ['getAssociationKind', 'getAssociationType']
        );
        $this->extension->expects($this->any())
            ->method('getAssociationKind')
            ->will($this->returnValue(self::ASSOCIATION_KIND));
        $this->extension->expects($this->any())
            ->method('getAssociationType')
            ->will($this->returnValue('manyToMany'));
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports($schemas, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->supports($schemas)
        );
    }

    public function supportsProvider()
    {
        return [
            'supported' => [
                [
                    'relation' => ['test' => 'test'],
                    'relationData' => [
                        'test' => [
                            'target_entity' => 'Entity\Target',
                            'state' => 'Active',
                            'field_id' =>
                                new FieldConfigId(
                                    'extend',
                                    'Entity\Test',
                                    ExtendHelper::buildAssociationName('Entity\Target', self::ASSOCIATION_KIND),
                                    'manyToMany'
                                ),
                        ],
                    ]
                ],
                true,
            ],
            'another association kind' => [
                [
                    'relation' => ['test' => 'test'],
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Test\Entity',
                                ExtendHelper::buildAssociationName('Test\TargetEntity', 'another'),
                                'manyToMany'
                            ),
                            'target_entity' => 'Test\TargetEntity',
                            'state' => 'Active',
                        ]
                    ]
                ],
                false,
            ],
            'unsupported field name' => [
                [
                    'relation' => ['test' => 'test'],
                    'relationData' => [
                        'test' => [
                            'target_entity' => 'Entity\Target',
                            'state' => 'Active',
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Entity\Test',
                                'target',
                                'manyToMany'
                            ),
                        ],
                    ]
                ],
                false,
            ],
            'unsupported field type' => [
                [
                    'relation' => ['test' => 'test'],
                    'relationData' => [
                        'test' => [
                            'target_entity' => 'Entity\Target',
                            'state' => 'Active',
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Entity\Test',
                                ExtendHelper::buildAssociationName('Entity\Target', self::ASSOCIATION_KIND),
                                'manyToOne'
                            ),
                        ],
                    ]
                ],
                false,
            ],
            'empty data' => [
                [
                    'relation' => [],
                    'relationData' => []
                ],
                false,
            ],
            'no relationData' => [
                ['relation' => ['test' => 'test']],
                false,
            ],
            'empty' => [
                [],
                false,
            ],
        ];
    }

    public function testGenerate()
    {
        $schema = [
            'relationData' => [
                [
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity1', self::ASSOCIATION_KIND),
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity1',
                    'state' => 'Active',
                ],
                [
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity2', self::ASSOCIATION_KIND),
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity2',
                    'state' => 'Active',
                ],
                [ // should be ignored because field type is not manyToMany
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity3', self::ASSOCIATION_KIND),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity3',
                    'state' => 'Active'
                ],
                [ // should be ignored because field name is not match association naming conventions
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        'testField',
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity4',
                    'state' => 'Active',
                ],
            ],
        ];

        $class = PhpClass::create('Test\Entity');

        $this->extension->generate($schema, $class);
        $strategy     = new DefaultGeneratorStrategy();
        $classBody    = $strategy->generate($class);
        $expectedBody = file_get_contents(__DIR__ . '/../Fixtures/many_to_many_association.txt');

        $this->assertEquals(trim($expectedBody), $classBody);
    }
}
