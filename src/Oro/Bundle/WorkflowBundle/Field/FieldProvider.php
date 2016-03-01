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

        $metadata = $this->getMetadataFor($className);

        // add single association fields
        foreach ($metadata->getAssociationNames() as $associationName) {
            if ($this->isIgnoredInWorkflow($metadata, $associationName)) {
                continue;
            }

            if (isset($result[$associationName])) {
                // skip because a field with this name is already added, it could be a virtual field
                continue;
            }

            if (!$this->entityConfigProvider->hasConfig($metadata->getName(), $associationName)) {
                // skip non configurable relation
                continue;
            }

            $label = $this->getFieldLabel($metadata, $associationName);

            $field = [
                'name' => $associationName,
                'type' => $this->getRelationFieldType($className, $associationName),
                'label' => $translate ? $this->translator->trans($label) : $label,
            ];

            $result[$associationName . '-field'] = $field;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        // skip workflow and collection relations
        if ($this->isIgnoredInWorkflow($metadata, $associationName)) {
            return true;
        }

        return parent::isIgnoredRelation($metadata, $associationName);
    }

    /**
     * Checks if the given relation should be ignored in workflows
     *
     * @param ClassMetadata $metadata
     * @param string        $associationName
     *
     * @return bool
     */
    protected function isIgnoredInWorkflow(ClassMetadata $metadata, $associationName)
    {
        if ($this->isWorkflowField($associationName)) {
            return true;
        }

        return !$metadata->isSingleValuedAssociation($associationName);
    }
}
