<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\GeneratorExtensions;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\AbstractAssociationEntityGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;
use PHPUnit\Framework\MockObject\MockObject;

class MultipleManyToOneAbstractAssociationEntityGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
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
        $this->extension->method('getAssociationType')->willReturn(RelationType::MULTIPLE_MANY_TO_ONE);
    }

    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(array $schemas, bool $expected)
    {
        static::assertEquals($expected, $this->extension->supports($schemas));
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

        $class = new ClassGenerator('Test\Entity');

        $this->extension->generate($schema, $class);
        $expectedCode = \file_get_contents(__DIR__ . '/../Fixtures/multiple_many_to_one_association.txt');

        static::assertEquals(\trim($expectedCode), \trim($class->print()));
    }
}
