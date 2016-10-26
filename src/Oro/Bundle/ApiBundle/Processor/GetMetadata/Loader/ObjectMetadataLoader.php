<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ObjectMetadataLoader
{
    /** @var ObjectMetadataFactory */
    protected $objectMetadataFactory;

    /** @var ObjectNestedObjectMetadataFactory */
    protected $nestedObjectMetadataFactory;

    /**
     * @param ObjectMetadataFactory             $objectMetadataFactory
     * @param ObjectNestedObjectMetadataFactory $nestedObjectMetadataFactory
     */
    public function __construct(
        ObjectMetadataFactory $objectMetadataFactory,
        ObjectNestedObjectMetadataFactory $nestedObjectMetadataFactory
    ) {
        $this->objectMetadataFactory = $objectMetadataFactory;
        $this->nestedObjectMetadataFactory = $nestedObjectMetadataFactory;
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
            $targetClass = $field->getTargetClass();
            if ($targetClass) {
                $this->objectMetadataFactory->createAndAddAssociationMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $targetAction
                );
            } elseif ($field->isMetaProperty()) {
                $this->objectMetadataFactory->createAndAddMetaPropertyMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $targetAction
                );
                if (ConfigUtil::CLASS_NAME === $fieldName) {
                    $entityMetadata->setInheritedType(true);
                }
            } elseif (DataType::isNestedObject($field->getDataType())) {
                $this->nestedObjectMetadataFactory->createAndAddNestedObjectMetadata(
                    $entityMetadata,
                    $config,
                    $entityClass,
                    $fieldName,
                    $field,
                    $withExcludedProperties,
                    $targetAction
                );
            } else {
                $this->objectMetadataFactory->createAndAddFieldMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $targetAction
                );
            }
        }

        return $entityMetadata;
    }
}
