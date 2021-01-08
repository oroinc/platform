<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\GeneratorExtensions;

use Doctrine\Inflector\InflectorFactory;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\ParentClass;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\ParentClassWithConstructor;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\ParentClassWithConstructorWithArgs;
use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\ExtendEntityGeneratorExtension;
use Oro\Component\PhpUtils\ClassGenerator;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendEntityGeneratorExtensionTest extends \PHPUnit\Framework\TestCase
{
    protected ExtendEntityGeneratorExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new ExtendEntityGeneratorExtension(InflectorFactory::create()->build());
    }

    public function testSupports()
    {
        static::assertTrue($this->extension->supports([]));
    }

    public function testEmptyCustom()
    {
        $schema = [
            'type'      => 'Custom',
            'property'  => [],
            'relation'  => [],
            'default'   => [],
            'addremove' => []
        ];
        $this->assertGeneration('empty_custom.txt', $schema);
    }

    public function testEmptyCustomWithInherit()
    {
        $schema = [
            'type'      => 'Custom',
            'property'  => [],
            'relation'  => [],
            'default'   => [],
            'addremove' => [],
            'inherit'   => ParentClassWithConstructor::class
        ];
        $this->assertGeneration('empty_custom_with_inherit.txt', $schema);
    }

    public function testEmptyExtend()
    {
        $schema = [
            'type'      => 'Extend',
            'property'  => [],
            'relation'  => [],
            'default'   => [],
            'addremove' => []
        ];
        $this->assertGeneration('empty_extend.txt', $schema);
    }

    public function testEmptyExtendWithParent()
    {
        $schema = [
            'type'      => 'Extend',
            'inherit'   => ParentClass::class,
            'property'  => [],
            'relation'  => [],
            'default'   => [],
            'addremove' => []
        ];
        $this->assertGeneration('empty_extend_with_parent.txt', $schema);
    }

    public function testEmptyExtendWithParentConstructor()
    {
        $schema = [
            'type'      => 'Extend',
            'inherit'   => ParentClassWithConstructor::class,
            'property'  => [],
            'relation'  => [],
            'default'   => [],
            'addremove' => []
        ];
        $this->assertGeneration('empty_extend_with_parent_constructor.txt', $schema);
    }

    public function testExtendWithParentConstructorWithArgs()
    {
        $schema = [
            'type'      => 'Extend',
            'inherit'   => ParentClassWithConstructorWithArgs::class,
            'property'  => [],
            'relation'  => [],
            'default'   => [],
            'addremove' => []
        ];
        $this->assertGeneration('extend_with_parent_constructor_with_args.txt', $schema);
    }

    public function testProperties()
    {
        $schema = [
            'type'      => 'Extend',
            'property'  => ['field1' => 'field1', 'field_2' => 'field_2'],
            'relation'  => [],
            'default'   => [],
            'addremove' => [],
            'entity' => 'CustomEntity',
            'doctrine' => [
                'CustomEntity' => [
                    'fields' => [
                        'field1' => [],
                        'field_2' => ['default' => true, 'type' => 'boolean']
                    ]
                ]
            ]
        ];
        $this->assertGeneration('properties.txt', $schema);
    }

    public function testDefaults()
    {
        $schema = [
            'type'      => 'Extend',
            'property'  => [],
            'relation'  => [],
            'default'   => ['default_rel1' => 'default_rel1', 'default_rel_2' => 'default_rel_2'],
            'addremove' => []
        ];
        $this->assertGeneration('defaults.txt', $schema);
    }

    public function testCollections()
    {
        $schema = [
            'type'      => 'Extend',
            'property'  => [],
            'relation'  => [],
            'default'   => [],
            'addremove' => [
                'rel1'  => [
                    'self'                => 'rel1',
                    'is_target_addremove' => false,
                    'target'              => 'target1',
                ],
                'rel_2' => [
                    'self'                => 'rel_2',
                    'is_target_addremove' => true,
                    'target'              => 'target_2',
                ],
            ]
        ];
        $this->assertGeneration('collections.txt', $schema);
    }

    public function testCollectionsWithPluralNames()
    {
        $schema = [
            'type'      => 'Extend',
            'property'  => [],
            'relation'  => [],
            'default'   => [],
            'addremove' => [
                'owners'  => [
                    'self'                => 'owners',
                    'is_target_addremove' => true,
                    'target'              => 'targets',
                ],
            ]
        ];
        $this->assertGeneration('collections_with_plural_names.txt', $schema);
    }

    public function testRelations()
    {
        $schema = [
            'type'      => 'Extend',
            'property'  => [],
            'relation'  => ['rel1' => 'rel1', 'rel_2' => 'rel_2'],
            'default'   => [],
            'addremove' => [
                'rel1'  => [
                    'self'                => 'rel1',
                    'is_target_addremove' => false,
                    'target'              => 'target1',
                ],
            ]
        ];
        $this->assertGeneration('relations.txt', $schema);
    }

    public function testCollectionsWithoutTarget()
    {
        $schema = [
            'type'      => 'Extend',
            'property'  => [],
            'relation'  => [],
            'default'   => [],
            'addremove' => [
                'rel1'  => [
                    'self' => 'rel1',
                ],
                'rel_2' => [
                    'self' => 'rel_2',
                ],
            ]
        ];
        $this->assertGeneration('collections_without_target.txt', $schema);
    }

    protected function assertGeneration(string $expectedFile, array $schema): void
    {
        $class = new ClassGenerator('Test\Entity');

        $this->extension->generate($schema, $class);
        $expectedCode = \file_get_contents(__DIR__ . '/../Fixtures/' . $expectedFile);

        // Support different line endings.
        $expectedCode = \trim(\str_replace(PHP_EOL, "\n", $expectedCode));
        $generatedCode = \trim(\str_replace(PHP_EOL, "\n", $class->print()));

        static::assertEquals($expectedCode, $generatedCode);
    }
}
