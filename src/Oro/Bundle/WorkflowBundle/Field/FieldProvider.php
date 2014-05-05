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
    protected $workflowFields = array(
        FieldGenerator::PROPERTY_WORKFLOW_ITEM,
        FieldGenerator::PROPERTY_WORKFLOW_STEP,
    );

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
    protected function addFields(array &$result, $className, EntityManager $em, $withVirtualFields, $translate)
    {
        parent::addFields($result, $className, $em, $withVirtualFields, $translate);

        // only configurable entities are supported
        if ($this->entityConfigProvider->hasConfig($className)) {
            $metadata = $em->getClassMetadata($className);

            // add single association fields
            foreach ($metadata->getAssociationNames() as $associationName) {
                if (!$this->isWorkflowField($associationName)
                    && $metadata->isSingleValuedAssociation($associationName)
                ) {
                    $fieldLabel = $this->getFieldLabel($className, $associationName);
                    $this->addField(
                        $result,
                        $associationName,
                        null,
                        $fieldLabel,
                        false,
                        $translate
                    );
                }
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
