<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\GeneratorExtensions;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\PhpClass;

use Oro\Bundle\EntityExtendBundle\Tools\GeneratorExtensions\ExtendEntityGeneratorExtension;

class ExtendEntityGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExtendEntityGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new ExtendEntityGeneratorExtension();
    }

    public function testSupports()
    {
        $this->assertTrue(
            $this->extension->supports([])
        );
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
            'inherit'   => 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\ParentClassWithConstructor'
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
            'inherit'   => 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\ParentClass',
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
            'inherit'   => 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\ParentClassWithConstructor',
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
            'inherit'   => 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Tools\Fixtures\ParentClassWithConstructorWithArgs',
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
            'addremove' => []
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

    /**
     * @param string $expectedFile
     * @param array  $schema
     * @param bool   $dump
     */
    protected function assertGeneration($expectedFile, $schema, $dump = false)
    {
        $class = PhpClass::create('Test\Entity');

        $this->extension->generate($schema, $class);
        $strategy  = new DefaultGeneratorStrategy();
        $classBody = $strategy->generate($class);
        if ($dump) {
            print_r("\n" . $classBody . "\n");
        }
        $expectedBody = file_get_contents(__DIR__ . '/../Fixtures/' . $expectedFile);

        /**
         * Support different line endings.
         */
        $expectedBody = str_replace(PHP_EOL, "\n", $expectedBody);
        $classBody = str_replace(PHP_EOL, "\n", $classBody);
        $expectedBody = trim($expectedBody);
        $classBody = trim($classBody);

        $this->assertEquals($expectedBody, $classBody);
    }
}
