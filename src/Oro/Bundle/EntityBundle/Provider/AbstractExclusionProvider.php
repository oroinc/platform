<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

abstract class AbstractExclusionProvider implements ExclusionProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function isIgnoredEntity($className)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        return false;
    }
}
