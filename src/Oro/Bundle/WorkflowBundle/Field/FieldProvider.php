<?php

namespace Oro\Bundle\WorkflowBundle\Field;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

class FieldProvider extends EntityFieldProvider
{
    /**
     * @var array
     */
    protected $workflowFields = [
        FieldGenerator::PROPERTY_WORKFLOW_ITEM,
        FieldGenerator::PROPERTY_WORKFLOW_STEP,
    ];

    /**
     * @param string $field
     * @return bool
     */
    protected function isWorkflowField($field)
    {
        return in_array($field, $this->workflowFields);
    }

    /**
     * {@inheritdoc}
     */
    protected function addFields(array &$result, $className, $applyExclusions, $translate)
    {
        // exclusions are not used in workflow
        parent::addFields($result, $className, false, $translate);
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        // skip workflow and collection relations
        if ($this->isWorkflowField($associationName) || !$metadata->isSingleValuedAssociation($associationName)) {
            return true;
        }

        return parent::isIgnoredRelation($metadata, $associationName);
    }
}
