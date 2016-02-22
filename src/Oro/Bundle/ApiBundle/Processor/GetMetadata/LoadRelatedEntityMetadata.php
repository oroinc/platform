<?php

namespace Oro\Bundle\ApiBundle\Processor\GetMetadata;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

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
        if (empty($config)) {
            // a configuration does not exist
            return;
        }

        $this->loadMetadataForRelatedEntities($context->getResult(), $config, $context);
    }

    /**
     * @param EntityMetadata  $entityMetadata
     * @param array           $config
     * @param MetadataContext $context
     */
    protected function loadMetadataForRelatedEntities(
        EntityMetadata $entityMetadata,
        array $config,
        MetadataContext $context
    ) {
        $associations = $entityMetadata->getAssociations();
        foreach ($associations as $associationName => $association) {
            if (null !== $association->getTargetMetadata()) {
                // metadata for an associated entity is already loaded
                continue;
            }
            if (!isset($config[ConfigUtil::FIELDS][$associationName][ConfigUtil::FIELDS])) {
                // a configuration of an association fields does not exist
                continue;
            }

            $relatedEntityMetadata = $this->metadataProvider->getMetadata(
                $association->getTargetClassName(),
                $context->getVersion(),
                $context->getRequestType(),
                $context->getExtras(),
                $config[ConfigUtil::FIELDS][$associationName]
            );
            if (null !== $relatedEntityMetadata) {
                $association->setTargetMetadata($relatedEntityMetadata);
            }
        }
    }
}
