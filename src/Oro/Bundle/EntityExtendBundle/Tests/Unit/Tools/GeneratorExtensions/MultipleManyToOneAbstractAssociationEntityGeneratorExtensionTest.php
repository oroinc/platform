<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\GeneratorExtensions;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;

class MultipleManyToOneAbstractAssociationEntityGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
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
            ->will($this->returnValue('multipleManyToOne'));
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
                            'target_entity' => 'Test\TargetEntity',
                            'state' => 'Active'
                        ]
                    ]
                ],
                true,
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
                    'state' => 'Active'
                ],
                [
                    'field_id' => new FieldConfigId(
                        'extend',
                        'Test\Entity',
                        ExtendHelper::buildAssociationName('Test\TargetEntity2', self::ASSOCIATION_KIND),
                        'manyToOne'
                    ),
                    'target_entity' => 'Test\TargetEntity2',
                    'state' => 'Active'
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
        $expectedBody = file_get_contents(__DIR__ . '/../Fixtures/multiple_many_to_one_association.txt');

        $this->assertEquals(trim($expectedBody), $classBody);
    }
}
