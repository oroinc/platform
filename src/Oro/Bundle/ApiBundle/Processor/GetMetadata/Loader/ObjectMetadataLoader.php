<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ObjectMetadataLoader
{
    /** @var ObjectMetadataBuilder */
    protected $objectMetadataBuilder;

    /** @var ObjectNestedObjectMetadataBuilder */
    protected $nestedObjectMetadataBuilder;

    /**
     * @param ObjectMetadataBuilder             $objectMetadataBuilder
     * @param ObjectNestedObjectMetadataBuilder $nestedObjectMetadataBuilder
     */
    public function __construct(
        ObjectMetadataBuilder $objectMetadataBuilder,
        ObjectNestedObjectMetadataBuilder $nestedObjectMetadataBuilder
    ) {
        $this->objectMetadataBuilder = $objectMetadataBuilder;
        $this->nestedObjectMetadataBuilder = $nestedObjectMetadataBuilder;
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
        $entityMetadata = $this->objectMetadataBuilder->createObjectMetadata($entityClass, $config);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if (!$withExcludedProperties && $field->isExcluded()) {
                continue;
            }
            $targetClass = $field->getTargetClass();
            if ($targetClass) {
                $this->objectMetadataBuilder->addAssociationMetadata(
                    $entityMetadata,
                    $entityClass,
                    $fieldName,
                    $field,
                    $targetAction
                );
            } elseif ($field->isMetaProperty()) {
                $this->objectMetadataBuilder->addMetaPropertyMetadata(
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
                $this->nestedObjectMetadataBuilder->addNestedObjectMetadata(
                    $entityMetadata,
                    $config,
                    $entityClass,
                    $fieldName,
                    $field,
                    $withExcludedProperties,
                    $targetAction
                );
            } else {
                $this->objectMetadataBuilder->addFieldMetadata(
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
