<?php

namespace Oro\Bundle\WorkflowBundle\Field;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

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
    protected function addFields(
        array &$result,
        $className,
        EntityManager $em,
        $withVirtualFields,
        $applyExclusions,
        $translate
    ) {
        // in workflow exclusions not used
        $applyExclusions = false;
        parent::addFields($result, $className, $em, $withVirtualFields, $applyExclusions, $translate);

        $metadata = $this->getMetadataFor($className);

        // add single association fields
        foreach ($metadata->getAssociationNames() as $associationName) {
            if (!$this->isWorkflowField($associationName)
                && $metadata->isSingleValuedAssociation($associationName)
            ) {
                if (isset($result[$associationName])) {
                    // skip because a field with this name is already added, it could be a virtual field
                    continue;
                }
                if (!$this->entityConfigProvider->hasConfig($metadata->getName(), $associationName)) {
                    // skip non configurable relation
                    continue;
                }
                if ($this->isIgnoredField($metadata, $associationName)) {
                    continue;
                }
                if ($applyExclusions && $this->exclusionProvider->isIgnoredField($metadata, $associationName)) {
                    continue;
                }

                $this->addField(
                    $result,
                    $associationName,
                    $this->getRelationFieldType($className, $associationName),
                    $this->getFieldLabel($className, $associationName),
                    false,
                    $translate
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function isIgnoredRelation(ClassMetadata $metadata, $associationName)
    {
        // skip workflow and collection relations
        if ($this->isWorkflowField($associationName)
            || !$metadata->isSingleValuedAssociation($associationName)
        ) {
            return true;
        }

        return parent::isIgnoredRelation($metadata, $associationName);
    }
}
