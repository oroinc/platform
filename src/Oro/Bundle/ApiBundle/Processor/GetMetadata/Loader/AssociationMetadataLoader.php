<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\DataType;

/**
 * Adds metadata to all associations.
 */
class AssociationMetadataLoader
{
    /** @var MetadataProvider */
    private $metadataProvider;

    /**
     * @param MetadataProvider $metadataProvider
     */
    public function __construct(MetadataProvider $metadataProvider)
    {
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $config
     * @param MetadataContext        $context
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function completeAssociationMetadata(
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $config,
        MetadataContext $context
    ) {
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            if (null !== $association->getTargetMetadata()) {
                // metadata for an associated entity is already loaded
                continue;
            }
            $field = $config->getField($associationName);
            if (null === $field || !$field->hasTargetEntity()) {
                // a configuration of an association fields does not exist
                continue;
            }

            $targetClass = $field->getTargetClass();
            if ($targetClass && $association->getTargetClassName() !== $targetClass) {
                $acceptableTargetClassNames = $association->getAcceptableTargetClassNames();
                $i = \array_search($association->getTargetClassName(), $acceptableTargetClassNames, true);
                if (false !== $i) {
                    $acceptableTargetClassNames[$i] = $targetClass;
                    $association->setAcceptableTargetClassNames($acceptableTargetClassNames);
                }
                $association->setTargetClassName($targetClass);
            }
            $targetMetadata = $this->metadataProvider->getMetadata(
                $targetClass,
                $context->getVersion(),
                $context->getRequestType(),
                $field->getTargetEntity(),
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

    /**
     * @param EntityMetadata $targetMetadata
     *
     * @return string
     */
    private function getAssociationDataType(EntityMetadata $targetMetadata)
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
