<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Provides default implementations for entity exclusion provider methods.
 *
 * This base class implements the {@see ExclusionProviderInterface} with methods that return false by default,
 * meaning nothing is excluded. Subclasses should override specific methods to define which entities,
 * fields, or relations should be excluded from various operations (e.g., API exposure, UI display).
 */
abstract class AbstractExclusionProvider implements ExclusionProviderInterface
{
    #[\Override]
    public function isIgnoredEntity($className)
    {
        return false;
    }

    #[\Override]
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return false;
    }

    #[\Override]
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return false;
    }
}
