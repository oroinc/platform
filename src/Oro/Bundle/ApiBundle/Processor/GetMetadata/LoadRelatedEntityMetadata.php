<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

/**
 * Adds metadata for fields of related entities.
 */
class LoadRelatedEntityMetadata implements ProcessorInterface
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
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var MetadataContext $context */

        if (!$context->hasResult()) {
            // metadata is not loaded
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // a configuration does not exist
            return;
        }

        $this->loadMetadataForRelatedEntities($context->getResult(), $config, $context);
    }

    /**
     * @param EntityMetadata         $entityMetadata
     * @param EntityDefinitionConfig $definition
     * @param MetadataContext        $context
     */
    protected function loadMetadataForRelatedEntities(
        EntityMetadata $entityMetadata,
        EntityDefinitionConfig $definition,
        MetadataContext $context
    ) {
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            if (null !== $association->getTargetMetadata()) {
                // metadata for an associated entity is already loaded
                continue;
            }
            $field = $definition->getField($associationName);
            if (null === $field || !$field->hasTargetEntity()) {
                // a configuration of an association fields does not exist
                continue;
            }

            $relatedEntityMetadata = $this->metadataProvider->getMetadata(
                $association->getTargetClassName(),
                $context->getVersion(),
                $context->getRequestType(),
                $context->getExtras(),
                $field->getTargetEntity()
            );
            if (null !== $relatedEntityMetadata) {
                $association->setTargetMetadata($relatedEntityMetadata);
            }
        }
    }
}
