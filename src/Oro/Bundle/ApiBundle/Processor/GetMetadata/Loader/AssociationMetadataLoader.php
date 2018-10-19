<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata\Loader;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

/**
 * Adds metadata to all associations.
 */
class AssociationMetadataLoader
{
    /** @var MetadataProvider */
    protected $metadataProvider;

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
            }
        }
    }
}
