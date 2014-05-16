<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

abstract class EntityFieldProviderExtension
{
    /**
     * Checks if the given field should be ignored
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return bool
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        // will be implemented in children
    }

    /**
     * Checks if the given relation should be ignored
     *
     * @param ClassMetadata $metadata
     * @param string        $associationName
     *
     * @return bool
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        // will be implemented in children
    }
} 