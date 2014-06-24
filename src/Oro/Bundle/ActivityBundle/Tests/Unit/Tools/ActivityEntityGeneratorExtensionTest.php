<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Tools;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;

use Oro\Bundle\ActivityBundle\Tools\ActivityEntityGeneratorExtension;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityEntityGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActivityEntityGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new ActivityEntityGeneratorExtension();
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
            [
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
                [
                    'relation'     => [],
                    'relationData' => []
                ],
                false,
            ],
            [
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
