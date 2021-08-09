<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Oro\Bundle\EntityConfigBundle\Metadata\Driver\AnnotationDriver;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\EntityForAnnotationTests;

class AnnotationDriverTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadMetadataForClass()
    {
        $driver = new AnnotationDriver(new AnnotationReader());

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass(EntityForAnnotationTests::class));

        $this->assertEquals('test_route_name', $metadata->routeName);
        $this->assertEquals('test_route_view', $metadata->routeView);
        $this->assertEquals('test_route_create', $metadata->routeCreate);
        $this->assertEquals(['custom' => 'test_route_custom'], $metadata->routes);
        $this->assertEquals('default', $metadata->mode);
        $this->assertEquals(
            [
                'ownership' => [
                    'owner_type'        => 'USER',
                    'owner_field_name'  => 'owner',
                    'owner_column_name' => 'user_owner_id',
                ]
            ],
            $metadata->defaultValues
        );

        $this->assertCount(2, $metadata->fieldMetadata);
        $idFieldMetadata = $metadata->fieldMetadata['id'];
        $this->assertEquals('id', $idFieldMetadata->name);
        $this->assertNull($idFieldMetadata->mode);
        $this->assertNull($idFieldMetadata->defaultValues);

        $nameFieldMetadata = $metadata->fieldMetadata['name'];

        $this->assertEquals('name', $nameFieldMetadata->name);
        $this->assertEquals('default', $nameFieldMetadata->mode);
        $this->assertEquals(
            [
                'email' => [
                    'available_in_template' => true,
                ]
            ],
            $nameFieldMetadata->defaultValues
        );
    }

    public function testLoadMetadataForClassForNonConfigurableEntity()
    {
        $driver = new AnnotationDriver(new AnnotationReader());

        $metadata = $driver->loadMetadataForClass(new \ReflectionClass(\stdClass::class));

        $this->assertNull($metadata);
    }
}
