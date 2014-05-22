<?php

namespace Oro\Bundle\WorkflowBundle\Field;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class FieldProvider extends EntityFieldProvider
{
    /**
     * @var array
     */
    protected $workflowFields = array(
        FieldGenerator::PROPERTY_WORKFLOW_ITEM,
        FieldGenerator::PROPERTY_WORKFLOW_STEP,
    );

    /** @var ConfigProvider */
    protected $groupingConfigProvider;

    /**
     * Constructor
     *
     * @param ConfigProvider                $entityConfigProvider
     * @param ConfigProvider                $extendConfigProvider
     * @param EntityClassResolver           $entityClassResolver
     * @param ManagerRegistry               $doctrine
     * @param Translator                    $translator
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     * @param array                         $hiddenFields
     * @param ConfigProvider                $groupingConfigProvider
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        EntityClassResolver $entityClassResolver,
        ManagerRegistry $doctrine,
        Translator $translator,
        VirtualFieldProviderInterface $virtualFieldProvider,
        $hiddenFields,
        ConfigProvider $groupingConfigProvider
    ) {
        parent::__construct(
            $entityConfigProvider,
            $extendConfigProvider,
            $entityClassResolver,
            $doctrine,
            $translator,
            $virtualFieldProvider,
            $hiddenFields
        );
        $this->groupingConfigProvider = $groupingConfigProvider;
    }

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
            || $this->isDictionary($metadata->getAssociationTargetClass($associationName))
        ) {
            return true;
        }

        return parent::isIgnoredRelation($metadata, $associationName);
    }

    /**
     * Indicates whether the entity is the dictionary one or not
     *
     * @param string $className
     * @return bool
     */
    protected function isDictionary($className)
    {
        $result = false;
        if ($this->groupingConfigProvider->hasConfig($className)) {
            $groups = $this->groupingConfigProvider->getConfig($className)->get('groups');
            if (!empty($groups) && in_array('dictionary', $groups)) {
                $result = true;
            }
        }

        return $result;
    }
}
