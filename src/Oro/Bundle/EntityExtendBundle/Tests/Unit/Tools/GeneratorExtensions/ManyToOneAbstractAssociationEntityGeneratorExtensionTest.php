<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\GeneratorExtensions;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ManyToOneAbstractAssociationEntityGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ASSOCIATION_KIND = 'test';

    /** @var AbstractAssociationEntityGeneratorExtension|\PHPUnit_Framework_MockObject_MockObject */
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
            ['getAssociationKind']
        );
        $this->extension->expects($this->any())
            ->method('getAssociationKind')
            ->will($this->returnValue(self::ASSOCIATION_KIND));
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
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Test\Entity',
                                ExtendHelper::buildAssociationName('Test\TargetEntity', self::ASSOCIATION_KIND),
                                'manyToOne'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
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
                                'manyToOne'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
                    ]
                ],
                false,
            ],
            'unsupported field name' => [
                [
                    'relation' => ['test' => 'test'],
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Test\Entity',
                                'testField',
                                'manyToOne'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
                    ]
                ],
                false,
            ],
            'unsupported field type' => [
                [
                    'relation' => ['test' => 'test'],
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Test\Entity',
                                ExtendHelper::buildAssociationName('Test\TargetEntity', self::ASSOCIATION_KIND),
                                'manyToMany'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
                    ]
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
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity1',
                ],
                [
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity2', self::ASSOCIATION_KIND),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity2',
                ],
                [ // should be ignored because field type is not manyToOne
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity3', self::ASSOCIATION_KIND),
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity3'
                ],
                [ // should be ignored because field name is not match association naming conventions
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        'testField',
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity4'
                ],
            ],
        ];

        $class = PhpClass::create('Test\Entity');

        $this->extension->generate($schema, $class);
        $strategy     = new DefaultGeneratorStrategy();
        $classBody    = $strategy->generate($class);
        $expectedBody = file_get_contents(__DIR__ . '/../Fixtures/many_to_one_association.txt');

        $this->assertEquals(trim($expectedBody), $classBody);
    }
}
