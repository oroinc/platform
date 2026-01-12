<?php

namespace Oro\Bundle\TestFrameworkBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;

/**
 * Excludes test framework entities from normal entity processing.
 *
 * This provider marks all entities implementing {@see TestFrameworkEntityInterface} as ignored,
 * preventing them from being processed in normal entity operations and searches.
 */
class EntityExclusionProvider implements ExclusionProviderInterface
{
    #[\Override]
    public function isIgnoredEntity($className)
    {
        return in_array(
            'Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface',
            class_implements($className)
        );
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
