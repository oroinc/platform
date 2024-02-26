<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata\Factory;

use Oro\Bundle\EntityConfigBundle\Metadata\Driver\AttributeDriver;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;

/**
 * The factory to create EntityMetadata objects that contain all the metadata information
 * configured via {@see \Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config}
 * and {@see \Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField} annotations.
 */
class MetadataFactory
{
    private AttributeDriver $driver;
    /** @var EntityMetadata[] */
    private array $loadedMetadata = [];
    /** @var EntityMetadata[] */
    private array $loadedClassMetadata = [];

    public function __construct(AttributeDriver $driver)
    {
        $this->driver = $driver;
    }

    public function getMetadataForClass(string $className): ?EntityMetadata
    {
        if (\array_key_exists($className, $this->loadedMetadata)) {
            return $this->loadedMetadata[$className];
        }

        $metadata = null;
        $classHierarchy = $this->getClassHierarchy($className);
        foreach ($classHierarchy as $class) {
            $name = $class->getName();
            $classMetadata = null;
            if (\array_key_exists($name, $this->loadedClassMetadata)) {
                $classMetadata = $this->loadedClassMetadata[$name];
            } else {
                $classMetadata = $this->driver->loadMetadataForClass($class);
                $this->loadedClassMetadata[$name] = $classMetadata;
            }
            if (null !== $classMetadata) {
                if (null === $metadata) {
                    $metadata = clone $classMetadata;
                } else {
                    $metadata->merge($classMetadata);
                }
            }
        }

        $this->loadedMetadata[$className] = $metadata;

        return $metadata;
    }

    /**
     * @return \ReflectionClass[]
     */
    private function getClassHierarchy(string $class): array
    {
        $classes = [];
        $refl = new \ReflectionClass($class);
        do {
            $classes[] = $refl;
            $refl = $refl->getParentClass();
        } while (false !== $refl);

        return array_reverse($classes);
    }
}
