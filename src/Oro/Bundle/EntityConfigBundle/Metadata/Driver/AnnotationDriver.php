<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata\Driver;

use Doctrine\Common\Annotations\Reader;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;

/**
 * The driver to read entity and field config annotations.
 */
class AnnotationDriver
{
    private Reader $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMetadataForClass(\ReflectionClass $class): ?EntityMetadata
    {
        $entity = $this->reader->getClassAnnotation($class, Config::class);
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
            $field = $this->reader->getPropertyAnnotation($property, ConfigField::class);
            if (null !== $field) {
                $fieldMetadata->mode = $field->mode;
                $fieldMetadata->defaultValues = $field->defaultValues;
            }
            $metadata->addFieldMetadata($fieldMetadata);
        }

        return $metadata;
    }
}
