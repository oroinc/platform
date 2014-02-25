<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Oro\Bundle\EntityConfigBundle\Metadata\Driver\AnnotationDriver;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;

class AnnotationDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadMetadataForClass()
    {
        $reader = new AnnotationReader();
        $driver = new AnnotationDriver($reader);

        /** @var EntityMetadata $metadata */
        $metadata = $driver->loadMetadataForClass(
            new \ReflectionClass('Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\EntityForAnnotationTests')
        );

        $this->assertTrue($metadata->configurable);
        $this->assertEquals('test_route_name', $metadata->routeName);
        $this->assertEquals('test_route_view', $metadata->routeView);
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

        $this->assertCount(2, $metadata->propertyMetadata);
        /** @var FieldMetadata $nameFieldMetadata */
        $idFieldMetadata = $metadata->propertyMetadata['id'];
        $this->assertEquals('id', $idFieldMetadata->name);
        $this->assertNull($idFieldMetadata->mode);
        $this->assertNull($idFieldMetadata->defaultValues);

        /** @var FieldMetadata $nameFieldMetadata */
        $nameFieldMetadata = $metadata->propertyMetadata['name'];

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
        $reader = new AnnotationReader();
        $driver = new AnnotationDriver($reader);

        /** @var EntityMetadata $metadata */
        $metadata = $driver->loadMetadataForClass(
            new \ReflectionClass('\stdClass')
        );

        $this->assertNull($metadata);
    }
}
