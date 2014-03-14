<?php

namespace Oro\Bundle\WorkflowBundle\Field;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

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
     * Adds entity fields to $result
     *
     * @param array         $result
     * @param string        $className
     * @param EntityManager $em
     * @param bool          $translate
     */
    protected function addFields(array &$result, $className, EntityManager $em, $translate)
    {
        // only configurable entities are supported
        if ($this->entityConfigProvider->hasConfig($className)) {
            $metadata = $em->getClassMetadata($className);

            // add regular fields
            foreach ($metadata->getFieldNames() as $fieldName) {
                $fieldLabel = $this->getFieldLabel($className, $fieldName);
                $this->addField(
                    $result,
                    $fieldName,
                    $metadata->getTypeOfField($fieldName),
                    $fieldLabel,
                    $metadata->isIdentifier($fieldName),
                    $translate
                );
            }

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
     * {@inheritDoc}
     */
    protected function addRelations(
        array &$result,
        $className,
        EntityManager $em,
        $withEntityDetails,
        $relationDeepLevel,
        $lastDeepLevelRelations,
        $translate
    ) {
        // only configurable entities are supported
        if ($this->entityConfigProvider->hasConfig($className)) {
            $metadata = $em->getClassMetadata($className);
            foreach ($metadata->getAssociationNames() as $associationName) {
                $targetClassName = $metadata->getAssociationTargetClass($associationName);
                // skip workflow and collection relations
                if (!$this->isWorkflowField($associationName)
                    && $metadata->isSingleValuedAssociation($associationName)
                    && $this->entityConfigProvider->hasConfig($targetClassName)
                ) {
                    // skip 'default_' extend field
                    if (strpos($associationName, ExtendConfigDumper::DEFAULT_PREFIX) === 0) {
                        $guessedFieldName = substr($associationName, strlen(ExtendConfigDumper::DEFAULT_PREFIX));
                        if ($this->isExtendField($className, $guessedFieldName)) {
                            continue;
                        }
                    }

                    $targetFieldName = $metadata->getAssociationMappedByTargetField($associationName);
                    $targetMetadata  = $em->getClassMetadata($targetClassName);
                    $fieldLabel      = $this->getFieldLabel($className, $associationName);
                    $relationData    = array(
                        'name'                => $associationName,
                        'type'                => $targetMetadata->getTypeOfField($targetFieldName),
                        'label'               => $fieldLabel,
                        'relation_type'       => $this->getRelationType($className, $associationName),
                        'related_entity_name' => $targetClassName
                    );
                    $this->addRelation(
                        $result,
                        $relationData,
                        $withEntityDetails,
                        $relationDeepLevel,
                        $lastDeepLevelRelations,
                        $translate
                    );
                }
            }
        }
    }
}
