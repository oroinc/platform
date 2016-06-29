<?php

namespace Oro\Bundle\CommentBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * relations which are a comment associations.
 */
class CommentExclusionProvider extends AbstractExclusionProvider
{
    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        if ($metadata->name !== 'Oro\Bundle\CommentBundle\Entity\Comment') {
            return false;
        }

        $mapping = $metadata->getAssociationMapping($associationName);
        if (!$mapping['isOwningSide'] || !($mapping['type'] & ClassMetadata::MANY_TO_ONE)) {
            return false;
        }

        return $associationName === ExtendHelper::buildAssociationName($mapping['targetEntity']);
    }
}
