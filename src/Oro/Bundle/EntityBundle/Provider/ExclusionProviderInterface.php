<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Provides an interface for classes responsible to filter entities and fields based on exclude rules
 */
interface ExclusionProviderInterface
{
    /**
     * Checks if the given entity should be ignored
     *
     * @param string $className
     *
     * @return bool
     */
    public function isIgnoredEntity($className);

    /**
     * Checks if the given field should be ignored
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return bool
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName);

    /**
     * Checks if the given relation should be ignored
     *
     * @param ClassMetadata $metadata
     * @param string        $associationName
     *
     * @return bool
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName);
}
