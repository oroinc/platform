<?php


namespace Oro\Bundle\ActivityBundle\Tests\Unit\Tools;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;

use Oro\Bundle\ActivityBundle\Tools\ActivityExtendEntityGeneratorExtension;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGenerator;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityExtendEntityGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new ActivityExtendEntityGeneratorExtension();
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports($actionType, $schemas, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->supports($actionType, $schemas)
        );
    }

    public function supportsProvider()
    {
        return [
            [
                ExtendEntityGenerator::ACTION_GENERATE,
                [
                    'relation'     => ['test' => 'test'],
                    'relationData' => [
                        'test' => [
                            'target_entity' => 'Entity\Target',
                            'field_id'      =>
                                new FieldConfigId(
                                    'extend',
                                    'Entity\Test',
                                    ExtendHelper::buildAssociationName('Entity\Target'),
                                    'manyToMany'
                                ),
                        ],
                    ]
                ],
                true,
            ],
            [
                ExtendEntityGenerator::ACTION_PRE_PROCESS,
                [
                    'relation'     => ['test' => 'test'],
                    'relationData' => [
                        'test' => [
                            'target_entity' => 'Entity\Target',
                            'field_id'      =>
                                new FieldConfigId(
                                    'extend',
                                    'Entity\Test',
                                    ExtendHelper::buildAssociationName('Entity\Target'),
                                    'manyToMany'
                                ),
                        ],
                    ]
                ],
                false,
            ],
            [
                ExtendEntityGenerator::ACTION_GENERATE,
                [
                    'relation'     => ['test' => 'test'],
                    'relationData' => [
                        'test' => [
                            'target_entity' => 'Entity\Target',
                            'field_id'      =>
                                new FieldConfigId(
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
            [
                ExtendEntityGenerator::ACTION_GENERATE,
                [
                    'relation'     => ['test' => 'test'],
                    'relationData' => [
                        'test' => [
                            'target_entity' => 'Entity\Target',
                            'field_id'      =>
                                new FieldConfigId(
                                    'extend',
                                    'Entity\Test',
                                    ExtendHelper::buildAssociationName('Entity\Target'),
                                    'manyToOne'
                                ),
                        ],
                    ]
                ],
                false,
            ],
            [
                ExtendEntityGenerator::ACTION_GENERATE,
                [
                    'relation'     => [],
                    'relationData' => []
                ],
                false,
            ],
            [
                ExtendEntityGenerator::ACTION_GENERATE,
                [],
                false,
            ],
        ];
    }

    public function atestSupports1()
    {
        $schemas = [];
        $result  = $this->extension->supports(ExtendEntityGenerator::ACTION_PRE_PROCESS, $schemas);
        $this->assertFalse($result, 'Pre processing not supported');

        $result = $this->extension->supports(
            ExtendEntityGenerator::ACTION_GENERATE,
            ['class' => 'Test\Entity', 'relation' => 'test']
        );
        $this->assertFalse($result, 'Generate action, no relation data');

        $result = $this->extension->supports(
            ExtendEntityGenerator::ACTION_GENERATE,
            [
                'relation'     => ['test' => 'test'],
                'relationData' => [
                    'test'    => [
                        'field_id' => new FieldConfigId('extend', 'Entity\Test', 'name', 'manyToOne'),
                    ],
                    'another' => [
                        'target_entity' => 'Entity\Test',
                        'field_id'      => new FieldConfigId('extend', 'Entity\Test', 'org_2342', 'manyToMany'),
                    ],
                ]
            ]
        );
        $this->assertFalse($result, 'Generate action');
    }

    public function testGenerate()
    {
        $schema = [
            'relationData' => [
                [
                    'field_id'      => new FieldConfigId('extend', 'Test\Entity', 'targetField1'),
                    'target_entity' => 'Test\TargetEntity1',
                ],
                [
                    'field_id'      => new FieldConfigId('extend', 'Test\Entity', 'targetField2'),
                    'target_entity' => 'Test\TargetEntity2',
                ],
            ],
        ];

        $class = PhpClass::create('Test\Entity');

        $this->extension->generate($schema, $class);
        $strategy     = new DefaultGeneratorStrategy();
        $classBody    = $strategy->generate($class);
        $expectedBody = file_get_contents(__DIR__ . '/Fixtures/generationResult.txt');

        $this->assertEquals(trim($expectedBody), $classBody);
    }
}
