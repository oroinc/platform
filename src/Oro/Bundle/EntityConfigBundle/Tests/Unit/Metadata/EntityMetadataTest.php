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

        $this->assertEquals('test_route_view', $metadata->getRoute());
        $this->assertEquals('test_route_view', $metadata->getRoute('view'));
        $this->assertEquals('test_route_name', $metadata->getRoute('name'));
        $this->assertEquals('test_route_create', $metadata->getRoute('create'));
    }

    public function testGetRouteGeneratedAutomaticallyInNonStrictMode()
    {
        $metadata = new EntityMetadata('Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity');

        $this->assertEquals('oro_demoentity_view', $metadata->getRoute('view', false));
        $this->assertEquals('oro_demoentity_index', $metadata->getRoute('name', false));
        $this->assertEquals('oro_demoentity_create', $metadata->getRoute('create', false));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No route "view" found for entity
     */
    public function testGetRouteThrowExceptionInStrictMode()
    {
        $metadata = new EntityMetadata('Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity');

        $this->assertEquals('oro_demoentity_view', $metadata->getRoute('view', true));
    }
}
