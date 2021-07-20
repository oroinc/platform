<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\GeneratorExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class ManyToManyAbstractAssociationEntityGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const ASSOCIATION_KIND = 'test';

    /** @var AbstractAssociationEntityGeneratorExtension|MockObject */
    protected $extension;

    protected function setUp(): void
    {
        $this->extension = $this->getMockForAbstractClass(
            AbstractAssociationEntityGeneratorExtension::class,
            [],
            '',
            true,
            true,
            true,
            ['getAssociationKind', 'getAssociationType']
        );
        $this->extension->method('getAssociationKind')->willReturn(self::ASSOCIATION_KIND);
        $this->extension->method('getAssociationType')->willReturn(RelationType::MANY_TO_MANY);
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(array $schemas, bool $expected)
    {
        static::assertEquals($expected, $this->extension->supports($schemas));
    }

    public function supportsProvider(): array
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

        $class = new ClassGenerator('Test\Entity');

        $this->extension->generate($schema, $class);
        $expectedCode = \file_get_contents(__DIR__ . '/../Fixtures/many_to_many_association.txt');

        static::assertEquals(\trim($expectedCode), \trim($class->print()));
    }
}
