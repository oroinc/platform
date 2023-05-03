<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * Adds metadata to all associations.
 */
class AssociationMetadataLoader
{
    private MetadataProvider $metadataProvider;

    public function __construct(MetadataProvider $metadataProvider)
    {
        $this->metadataProvider = $metadataProvider;
    }

    public function completeAssociationMetadata(
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $config,
        MetadataContext $context
    ): void {
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            if (null !== $association->getTargetMetadata()) {
                // metadata for an associated entity is already loaded
                continue;
            }
            $field = $config->getField($associationName);
            if (null === $field) {
                continue;
            }
            $targetConfig = $field->getTargetEntity();
            if (null === $targetConfig) {
                continue;
            }
            $targetClass = $field->getTargetClass();
            if (!$targetClass) {
                continue;
            }

            $this->updateAssociationTargetClass($association, $targetClass);
            $targetMetadata = $this->metadataProvider->getMetadata(
                $targetClass,
                $context->getVersion(),
                $context->getRequestType(),
                $targetConfig,
                $context->getExtras(),
                $context->getWithExcludedProperties()
            );
            if (null !== $targetMetadata) {
                $association->setTargetMetadata($targetMetadata);
                if (!$association->getDataType()) {
                    $association->setDataType($this->getAssociationDataType($targetMetadata));
                }
            }
        }
    }

    private function updateAssociationTargetClass(AssociationMetadata $association, string $targetClass): void
    {
        if ($association->getTargetClassName() !== $targetClass) {
            $acceptableTargetClassNames = $association->getAcceptableTargetClassNames();
            $i = array_search($association->getTargetClassName(), $acceptableTargetClassNames, true);
            if (false !== $i) {
                $acceptableTargetClassNames[$i] = $targetClass;
                $association->setAcceptableTargetClassNames($acceptableTargetClassNames);
            }
            $association->setTargetClassName($targetClass);
        }
    }

    private function getAssociationDataType(EntityMetadata $targetMetadata): string
    {
        $dataType = null;
        $idFieldNames = $targetMetadata->getIdentifierFieldNames();
        if (\count($idFieldNames) === 1) {
            $idField = $targetMetadata->getProperty(reset($idFieldNames));
            if (null !== $idField) {
                $dataType = $idField->getDataType();
            }
        }
        if (null === $dataType) {
            $dataType = DataType::STRING;
        }

        return $dataType;
    }
}
