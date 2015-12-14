<?php

namespace Oro\Bundle\NoteBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * relations which are a note associations.
 */
class NoteExclusionProvider implements ExclusionProviderInterface
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
        if ($metadata->name !== 'Oro\Bundle\NoteBundle\Entity\Note') {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($associationName);
        if (!$mapping['isOwningSide'] || !($mapping['type'] & ClassMetadata::MANY_TO_ONE)) {
            return false;
        }

        return $associationName === ExtendHelper::buildAssociationName($mapping['targetEntity']);
    }
}
