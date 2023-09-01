<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\IncludeMapManager;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects processed included entities and makes necessary updated in the included items map.
 */
class CollectProcessedIncludedEntities implements ProcessorInterface
{
    public const OPERATION_NAME = 'collect_processed_included_entities';

    private IncludeMapManager $includeMapManager;
    private ValueNormalizer $valueNormalizer;
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;

    public function __construct(IncludeMapManager $includeMapManager, ValueNormalizer $valueNormalizer)
    {
        $this->includeMapManager = $includeMapManager;
        $this->valueNormalizer = $valueNormalizer;
    }

    public function setEntityIdTransformerRegistry(EntityIdTransformerRegistry $entityIdTransformerRegistry): void
    {
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // processed entities were already collected
            return;
        }

        if (null === $context->getIncludedData()) {
            // included data were not loaded
            return;
        }

        $items = $context->getBatchItems();
        if ($items) {
            $processedItems = [];
            $processedItemStatuses = $context->getProcessedItemStatuses();
            foreach ($items as $item) {
                if (BatchUpdateItemStatus::NO_ERRORS !== $processedItemStatuses[$item->getIndex()]) {
                    continue;
                }
                $itemTargetContext = $item->getContext()->getTargetContext();
                if ($itemTargetContext instanceof FormContext) {
                    $includedEntities = $itemTargetContext->getIncludedEntities();
                    if (null !== $includedEntities) {
                        $requestType = $itemTargetContext->getRequestType();
                        foreach ($includedEntities as $entity) {
                            $processedItem = $this->getProcessedItem($entity, $includedEntities, $requestType);
                            if (null !== $processedItem) {
                                $processedItems[] = $processedItem;
                            }
                        }
                    }
                }
            }
            if ($processedItems) {
                $this->includeMapManager->moveToProcessed(
                    $context->getFileManager(),
                    $context->getOperationId(),
                    $processedItems
                );
            }
        }
        $context->setProcessed(self::OPERATION_NAME);
    }

    /**
     * @param object                   $entity
     * @param IncludedEntityCollection $includedEntities
     * @param RequestType              $requestType
     *
     * @return array|null [entity type, string representation of entity include id, string representation of entity id]
     */
    private function getProcessedItem(
        object $entity,
        IncludedEntityCollection $includedEntities,
        RequestType $requestType
    ): ?array {
        $entityData = $includedEntities->getData($entity);
        if (null === $entityData) {
            return null;
        }
        $metadata = $entityData->getMetadata();
        if (null === $metadata) {
            return null;
        }
        $entityId = $metadata->getIdentifierValue($entity);
        if (null === $entityId) {
            return null;
        }

        $entityIdTransformer = $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);

        return [
            ValueNormalizerUtil::convertToEntityType(
                $this->valueNormalizer,
                $includedEntities->getClass($entity),
                $requestType
            ),
            $entityIdTransformer->transform($includedEntities->getId($entity), $metadata),
            $entityIdTransformer->transform($entityId, $metadata)
        ];
    }
}
