<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Tools;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use Oro\Bundle\TrackingBundle\Migration\Extension\IdentifierEventExtension;
use Oro\Bundle\TrackingBundle\Tools\IdentifierVisitGeneratorExtension;

class IdentifierVisitGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var IdentifierVisitGeneratorExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->extension = new IdentifierVisitGeneratorExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        unset($this->extension);
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports($schema, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->extension->supports($schema)
        );
    }

    public function supportsProvider()
    {
        return [
            [
                [
                    'class' => 'Oro\Bundle\TrackingBundle\Entity\TrackingVisit',
                    'relation' => 'test',
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Oro\Bundle\TrackingBundle\Entity\TrackingVisit',
                                ExtendHelper::buildAssociationName(
                                    'Test\TargetEntity',
                                    IdentifierEventExtension::ASSOCIATION_KIND
                                ),
                                'manyToOne'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
                    ]
                ],
                true
            ],
            [
                [
                    'class' => 'Oro\Bundle\TrackingBundle\Entity\TrackingVisit',
                    'relation' => 'test',
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Oro\Bundle\TrackingBundle\Entity\TrackingVisit',
                                'testField',
                                'manyToOne'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
                    ]
                ],
                false
            ],
            [
                [
                    'class' => 'Oro\Bundle\TrackingBundle\Entity\TrackingVisit',
                    'relation' => 'test',
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Oro\Bundle\TrackingBundle\Entity\TrackingVisit',
                                ExtendHelper::buildAssociationName(
                                    'Test\TargetEntity',
                                    IdentifierEventExtension::ASSOCIATION_KIND
                                ),
                                'manyToMany'
                            ),
                            'target_entity' => 'Test\TargetEntity'
                        ]
                    ]
                ],
                false
            ],
            [
                ['class' => 'Oro\Bundle\TrackingBundle\Entity\TrackingVisit'],
                false
            ],
            [
                ['class' => 'Test\Entity', 'relation' => 'test'],
                false
            ]
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
                        ExtendHelper::buildAssociationName(
                            'Test\TargetEntity1',
                            IdentifierEventExtension::ASSOCIATION_KIND
                        ),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity1'
                ],
                [
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName(
                            'Test\TargetEntity2',
                            IdentifierEventExtension::ASSOCIATION_KIND
                        ),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity2'
                ],
                [   // should be ignored because field type is not manyToOne
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName(
                            'Test\TargetEntity3',
                            IdentifierEventExtension::ASSOCIATION_KIND
                        ),
                        'manyToMany'
                    ),
                    'target_entity' => 'Test\TargetEntity3'
                ],
                [   // should be ignored because field name is not match association naming conventions
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        'testField',
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity4'
                ]
            ]
        ];

        $class = PhpClass::create('Test\Entity');

        $this->extension->generate($schema, $class);
        $strategy     = new DefaultGeneratorStrategy();
        $classBody    = $strategy->generate($class);
        $expectedBody = file_get_contents(__DIR__ . '/Fixtures/generationIdentifierResult.txt');
        $this->assertEquals(trim($expectedBody), $classBody);
    }
}
