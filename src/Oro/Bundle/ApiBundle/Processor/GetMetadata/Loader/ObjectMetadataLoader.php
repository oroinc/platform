<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * The metadata loader for non manageable entities.
 */
class ObjectMetadataLoader
{
    /** @var ObjectMetadataFactory */
    protected $objectMetadataFactory;

    /** @var ObjectNestedObjectMetadataFactory */
    protected $nestedObjectMetadataFactory;

    /** @var ObjectNestedAssociationMetadataFactory */
    protected $nestedAssociationMetadataFactory;

    /**
     * @param ObjectMetadataFactory                  $objectMetadataFactory
     * @param ObjectNestedObjectMetadataFactory      $nestedObjectMetadataFactory
     * @param ObjectNestedAssociationMetadataFactory $nestedAssociationMetadataFactory
     */
    public function __construct(
        ObjectMetadataFactory $objectMetadataFactory,
        ObjectNestedObjectMetadataFactory $nestedObjectMetadataFactory,
        ObjectNestedAssociationMetadataFactory $nestedAssociationMetadataFactory
    ) {
        $this->objectMetadataFactory = $objectMetadataFactory;
        $this->nestedObjectMetadataFactory = $nestedObjectMetadataFactory;
        $this->nestedAssociationMetadataFactory = $nestedAssociationMetadataFactory;
    }

    /**
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     * @param bool                   $withExcludedProperties
     * @param string                 $targetAction
     *
     * @return EntityMetadata
     */
    public function loadObjectMetadata(
        $entityClass,
        EntityDefinitionConfig $config,
        $withExcludedProperties,
        $targetAction
    ) {
        $entityMetadata = $this->objectMetadataFactory->createObjectMetadata($entityClass, $config);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$withExcludedProperties && $field->isExcluded()) {
                continue;
            }
            $dataType = $field->getDataType();
            if (DataType::isNestedObject($dataType)) {
                $metadata = $this->nestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
                    $entityMetadata,
                    $config,
                    $entityClass,
                    $fieldName,
                    $field,
                    $withExcludedProperties,
                    $targetAction
                );
            } elseif (DataType::isNestedAssociation($dataType)) {
                $metadata = $this->nestedAssociationMetadataFactory->createAndAddNestedAssociationMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $withExcludedProperties,
                    $targetAction
                );
            } elseif ($field->isMetaProperty()) {
                $metadata = $this->objectMetadataFactory->createAndAddMetaPropertyMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $targetAction
                );
                if (ConfigUtil::CLASS_NAME === $fieldName) {
                    $entityMetadata->setInheritedType(true);
                }
            } elseif ($field->getTargetClass()) {
                $metadata = $this->objectMetadataFactory->createAndAddAssociationMetadata(
                    $entityMetadata,
                    $entityClass,
                    $config,
                    $fieldName,
                    $field,
                    $targetAction
                );
            } else {
                $metadata = $this->objectMetadataFactory->createAndAddFieldMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $targetAction
                );
            }
            if ($field->hasDirection()) {
                $metadata->setDirection($field->isInput(), $field->isOutput());
            }
        }

        return $entityMetadata;
    }
}
