<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata\Driver;

use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Component\PhpUtils\Attribute\Reader\AttributeReader;

/**
 * The driver to read entity and field config attributes.
 */
class AttributeDriver
{
    public function __construct(private readonly AttributeReader $reader)
    {
    }

    public function loadMetadataForClass(\ReflectionClass $class): ?EntityMetadata
    {
        $entity = $this->reader->getClassAttribute($class, Config::class);
        if (null === $entity) {
            return null;
        }

        $metadata = new EntityMetadata($class->getName());
        $metadata->mode = $entity->mode;
        $metadata->defaultValues = $entity->defaultValues;
        $metadata->routeName = $entity->routeName;
        $metadata->routeView = $entity->routeView;
        $metadata->routeCreate = $entity->routeCreate;
        $metadata->routes = $entity->routes;

        $properties = $class->getProperties();
        foreach ($properties as $property) {
            $fieldMetadata = new FieldMetadata($class->getName(), $property->getName());
            $field = $this->reader->getPropertyAttribute($property, ConfigField::class);
            if (null !== $field) {
                $fieldMetadata->mode = $field->mode;
                $fieldMetadata->defaultValues = $field->defaultValues;
            }
            $metadata->addFieldMetadata($fieldMetadata);
        }

        return $metadata;
    }
}
