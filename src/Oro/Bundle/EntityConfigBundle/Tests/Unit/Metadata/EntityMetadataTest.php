<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;

class EntityMetadataTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityMetadata */
    private $classMetadata;

    protected function setUp(): void
    {
        $this->classMetadata = new EntityMetadata(DemoEntity::class);
        $this->classMetadata->mode = ConfigModel::MODE_DEFAULT;
    }

    public function testSerialize()
    {
        $this->classMetadata->addFieldMetadata(new FieldMetadata(DemoEntity::class, 'id'));
        $this->assertEquals($this->classMetadata, unserialize(serialize($this->classMetadata)));
    }

    public function testMerge()
    {
        $newMetadata = new EntityMetadata(DemoEntity::class);
        $newMetadata->mode = ConfigModel::MODE_READONLY;
        $this->classMetadata->merge($newMetadata);

        $this->assertEquals(ConfigModel::MODE_READONLY, $this->classMetadata->mode);
    }

    public function testGetRoutes()
    {
        $metadata = new EntityMetadata(DemoEntity::class);

        $metadata->routeView = 'test_route_view';
        $metadata->routeName = 'test_route_name';
        $metadata->routeCreate = 'test_route_create';
        $metadata->routeEdit = 'test_route_edit';
        $metadata->routeDelete = 'test_route_delete';
        $metadata->routeTest = null;
        $metadata->routes = ['custom' => 'test_route_custom', 'emtpy' => null];

        $this->assertEquals(
            [
                'custom' => 'test_route_custom',
                'view'   => 'test_route_view',
                'name'   => 'test_route_name',
                'create' => 'test_route_create',
            ],
            $metadata->getRoutes()
        );
    }

    public function testGetRouteFromAnnotationValues()
    {
        $metadata = new EntityMetadata(DemoEntity::class);

        $metadata->routeView = 'test_route_view';
        $metadata->routeName = 'test_route_name';
        $metadata->routeCreate = 'test_route_create';
        $metadata->routeEdit = 'test_route_edit';
        $metadata->routeDelete = 'test_route_delete';
        $metadata->routes = ['custom' => 'test_route_custom'];

        $this->assertEquals('test_route_view', $metadata->getRoute());
        $this->assertEquals('test_route_view', $metadata->getRoute('view'));
        $this->assertEquals('test_route_name', $metadata->getRoute('name'));
        $this->assertEquals('test_route_custom', $metadata->getRoute('custom'));
    }

    public function testGetRouteGeneratedAutomaticallyInNonStrictMode()
    {
        $metadata = new EntityMetadata(DemoEntity::class);

        $this->assertEquals('oro_demoentity_view', $metadata->getRoute());
        $this->assertEquals('oro_demoentity_view', $metadata->getRoute('view', false));
        $this->assertEquals('oro_demoentity_index', $metadata->getRoute('name', false));
        $this->assertEquals('oro_demoentity_create', $metadata->getRoute('create', false));
    }

    /**
     * @dataProvider getRouteThrowExceptionProvider
     */
    public function testGetRouteThrowExceptionInStrictMode(string $name)
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf('No route "%s" found for entity', $name));

        $metadata = new EntityMetadata(DemoEntity::class);
        $metadata->getRoute($name, true);
    }

    public function getRouteThrowExceptionProvider(): array
    {
        return [
            ['view'],
            ['custom']
        ];
    }

    /**
     * @dataProvider hasRouteDataProvider
     */
    public function testHasRoute(string $routeName, bool $strict, bool $expected, array $properties = [])
    {
        $metadata = new EntityMetadata(DemoEntity::class);

        foreach ($properties as $name => $value) {
            $metadata->{$name} = $value;
        }

        $this->assertSame($expected, $metadata->hasRoute($routeName, $strict));
    }

    public function hasRouteDataProvider(): array
    {
        return [
            [
                'routeName' => 'name',
                'strict'    => false,
                'expected'  => true
            ],
            [
                'routeName' => 'name',
                'strict'    => true,
                'expected'  => false
            ],
            [
                'routeName'  => 'name',
                'strict'     => true,
                'expected'   => true,
                'properties' => [
                    'routeName' => 'value'
                ]
            ],
            [
                'routeName' => 'view',
                'strict'    => false,
                'expected'  => true
            ],
            [
                'routeName' => 'view',
                'strict'    => true,
                'expected'  => false
            ],
            [
                'routeName'  => 'view',
                'strict'     => true,
                'expected'   => true,
                'properties' => [
                    'routeView' => 'value'
                ]
            ],
            [
                'routeName' => 'create',
                'strict'    => false,
                'expected'  => true
            ],
            [
                'routeName' => 'create',
                'strict'    => true,
                'expected'  => false
            ],
            [
                'routeName'  => 'create',
                'strict'     => true,
                'expected'   => true,
                'properties' => [
                    'routeCreate' => 'value'
                ]
            ],
            [
                'routeName' => 'test',
                'strict'    => false,
                'expected'  => false
            ],
            [
                'routeName' => 'test',
                'strict'    => true,
                'expected'  => false
            ],
            [
                'routeName'  => 'test',
                'strict'     => false,
                'expected'   => true,
                'properties' => [
                    'routes' => ['test' => 'value']
                ]
            ]
        ];
    }
}
