<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\AbstractExclusionProvider;

/**
 * The implementation of ExclusionProviderInterface that can be used to ignore virtual vields.
 */
class WorkflowExclusionProvider extends AbstractExclusionProvider
{
    /**
     * {@inheritdoc}
     */
    public function isIgnoredField(ClassMetadata $metadata, $fieldName)
    {
        return in_array(
            $fieldName,
            [
                WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME,
                WorkflowVirtualRelationProvider::STEPS_RELATION_NAME,
            ],
            true
        );
    }
}
