<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;

class EntityMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityMetadata
     */
    protected $classMetadata;

    protected function setUp()
    {
        $this->classMetadata       = new EntityMetadata(DemoEntity::ENTITY_NAME);
        $this->classMetadata->mode = ConfigModel::MODE_DEFAULT;
    }

    public function testSerialize()
    {
        $this->assertEquals($this->classMetadata, unserialize(serialize($this->classMetadata)));
    }

    public function testMerge()
    {
        $newMetadata       = new EntityMetadata(DemoEntity::ENTITY_NAME);
        $newMetadata->mode = ConfigModel::MODE_READONLY;
        $this->classMetadata->merge($newMetadata);

        $this->assertEquals(ConfigModel::MODE_READONLY, $this->classMetadata->mode);
    }

    public function testGetRouteFromAnnotationValues()
    {
        $metadata = new EntityMetadata('Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity');

        $metadata->routeView   = 'test_route_view';
        $metadata->routeName   = 'test_route_name';
        $metadata->routeCreate = 'test_route_create';
        $metadata->routeEdit   = 'test_route_edit';
        $metadata->routeDelete = 'test_route_delete';
        $metadata->routes      = ['custom' => 'test_route_custom'];

        $this->assertEquals('test_route_view', $metadata->getRoute());
        $this->assertEquals('test_route_view', $metadata->getRoute('view'));
        $this->assertEquals('test_route_name', $metadata->getRoute('name'));
        $this->assertEquals('test_route_custom', $metadata->getRoute('custom'));
    }

    public function testGetRouteGeneratedAutomaticallyInNonStrictMode()
    {
        $metadata = new EntityMetadata('Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity');

        $this->assertEquals('oro_demoentity_view', $metadata->getRoute('view', false));
        $this->assertEquals('oro_demoentity_index', $metadata->getRoute('name', false));
        $this->assertEquals('oro_demoentity_create', $metadata->getRoute('create', false));
    }

    /**
     * @dataProvider getRouteThrowExceptionProvider
     *
     * @param string $name
     */
    public function testGetRouteThrowExceptionInStrictMode($name)
    {
        $this->setExpectedException('\LogicException', sprintf('No route "%s" found for entity', $name));

        $metadata = new EntityMetadata('Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity');
        $metadata->getRoute($name, true);
    }

    /**
     * @return array
     */
    public function getRouteThrowExceptionProvider()
    {
        return [
            ['view'],
            ['custom']
        ];
    }

    /**
     * @dataProvider hasRouteDataProvider
     *
     * @param string $routeName
     * @param bool $strict
     * @param bool $expected
     * @param array $properties
     */
    public function testHasRoute($routeName, $strict, $expected, array $properties = [])
    {
        $metadata = new EntityMetadata(DemoEntity::ENTITY_NAME);

        foreach ($properties as $name => $value) {
            $metadata->$name = $value;
        }

        $this->assertEquals($expected, $metadata->hasRoute($routeName, $strict));
    }

    /**
     * @return array
     */
    public function hasRouteDataProvider()
    {
        return [
            [
                'routeName' => 'name',
                'strict' => false,
                'expected' => true
            ],
            [
                'routeName' => 'name',
                'strict' => true,
                'expected' => false
            ],
            [
                'routeName' => 'name',
                'strict' => true,
                'expected' => true,
                'properties' => [
                    'routeName' => 'value'
                ]
            ],
            [
                'routeName' => 'view',
                'strict' => false,
                'expected' => true
            ],
            [
                'routeName' => 'view',
                'strict' => true,
                'expected' => false
            ],
            [
                'routeName' => 'view',
                'strict' => true,
                'expected' => true,
                'properties' => [
                    'routeView' => 'value'
                ]
            ],
            [
                'routeName' => 'create',
                'strict' => false,
                'expected' => true
            ],
            [
                'routeName' => 'create',
                'strict' => true,
                'expected' => false
            ],
            [
                'routeName' => 'create',
                'strict' => true,
                'expected' => true,
                'properties' => [
                    'routeCreate' => 'value'
                ]
            ],
            [
                'routeName' => 'test',
                'strict' => false,
                'expected' => false
            ],
            [
                'routeName' => 'test',
                'strict' => true,
                'expected' => false
            ],
            [
                'routeName' => 'test',
                'strict' => false,
                'expected' => true,
                'properties' => [
                    'routes' => ['test' => 'value']
                ]
            ]
        ];
    }
}
