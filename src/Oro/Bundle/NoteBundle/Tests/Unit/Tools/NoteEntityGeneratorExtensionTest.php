<?php


namespace Oro\Bundle\NoteBundle\Tests\Unit\Tools;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Tools\NoteEntityGeneratorExtension;

class NoteEntityGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var NoteEntityGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new NoteEntityGeneratorExtension();
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
                    'class' => 'Oro\Bundle\NoteBundle\Entity\Note',
                    'relation' => 'test',
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Oro\Bundle\NoteBundle\Entity\Note',
                                ExtendHelper::buildAssociationName('Test\TargetEntity'),
                                'manyToOne'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
                    ]
                ],
                true,
            ],
            [
                [
                    'class' => 'Oro\Bundle\NoteBundle\Entity\Note',
                    'relation' => 'test',
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Oro\Bundle\NoteBundle\Entity\Note',
                                'testField',
                                'manyToOne'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
                    ]
                ],
                false,
            ],
            [
                [
                    'class' => 'Oro\Bundle\NoteBundle\Entity\Note',
                    'relation' => 'test',
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Oro\Bundle\NoteBundle\Entity\Note',
                                ExtendHelper::buildAssociationName('Test\TargetEntity'),
                                'manyToMany'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
                    ]
                ],
                false,
            ],
            [
                ['class' => 'Oro\Bundle\NoteBundle\Entity\Note'],
                false,
            ],
            [
                ['class' => 'Test\Entity', 'relation' => 'test'],
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
                        ExtendHelper::buildAssociationName('Test\TargetEntity1'),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity1',
                ],
                [
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity2'),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity2',
                ],
                [ // should be ignored because field type is not manyToOne
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Oro\Bundle\NoteBundle\Entity\Note',
                        ExtendHelper::buildAssociationName('Test\TargetEntity3'),
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity3'
                ],
                [ // should be ignored because field name is not match association naming conventions
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Oro\Bundle\NoteBundle\Entity\Note',
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
        $expectedBody = file_get_contents(__DIR__ . '/Fixtures/generationResult.txt');

        $this->assertEquals(trim($expectedBody), $classBody);
    }
}
