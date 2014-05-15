<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner\ProviderExtension;

use Doctrine\ORM\Mapping\ClassMetadata;

abstract class AbstractProviderExtension
{
    /**
     * Checks if the given field should be ignored
     *
     * @param ClassMetadata $metadata
     * @param string        $fieldName
     *
     * @return bool
     */
    abstract public function isIgnoredField(ClassMetadata $metadata, $fieldName);

    /**
     * Checks if the given relation should be ignored
     *
     * @param ClassMetadata $metadata
     * @param string        $associationName
     *
     * @return bool
     */
    abstract public function isIgnoredRelation(ClassMetadata $metadata, $associationName);
} 