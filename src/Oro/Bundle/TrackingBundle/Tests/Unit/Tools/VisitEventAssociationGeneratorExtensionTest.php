<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Tools;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use Oro\Bundle\TrackingBundle\Migration\Extension\VisitEventAssociationExtension;
use Oro\Bundle\TrackingBundle\Tools\VisitEventAssociationGeneratorExtension;

class VisitEventAssociationGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var VisitEventAssociationGeneratorExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->extension = new VisitEventAssociationGeneratorExtension();
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
                    'class' => 'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent',
                    'relation' => 'test',
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent',
                                ExtendHelper::buildAssociationName(
                                    'Test\TargetEntity',
                                    VisitEventAssociationExtension::ASSOCIATION_KIND
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
                    'class' => 'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent',
                    'relation' => 'test',
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent',
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
                    'class' => 'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent',
                    'relation' => 'test',
                    'relationData' => [
                        [
                            'field_id' => new FieldConfigId(
                                'extend',
                                'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent',
                                ExtendHelper::buildAssociationName(
                                    'Test\TargetEntity',
                                    VisitEventAssociationExtension::ASSOCIATION_KIND
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
                ['class' => 'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent'],
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
                            VisitEventAssociationExtension::ASSOCIATION_KIND
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
                            VisitEventAssociationExtension::ASSOCIATION_KIND
                        ),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity2'
                ],
                [   // should be ignored because field type is not multipleManyToOne
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName(
                            'Test\TargetEntity3',
                            VisitEventAssociationExtension::ASSOCIATION_KIND
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

        $expectedBody = file_get_contents(__DIR__ . '/Fixtures/generationAssociationResult.txt');
        $this->assertEquals(trim($expectedBody), $classBody);
    }
}
