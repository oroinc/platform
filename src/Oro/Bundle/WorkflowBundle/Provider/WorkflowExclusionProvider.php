<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore
 * "workflowItem" and "workflowStep" relations.
 */
class WorkflowExclusionProvider extends AbstractExclusionProvider
{
    /**
     * {@inheritdoc}
     */
    public function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        $mapping = $metadata->getAssociationMapping($associationName);
        if (!$mapping['isOwningSide'] || !($mapping['type'] & ClassMetadata::TO_ONE)) {
            return false;
        }

        return $associationName === 'workflowItem' || $associationName === 'workflowStep';
    }
}
