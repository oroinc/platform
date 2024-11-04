<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Model\BatchAffectedEntities;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates the following summary counters:
 * * WriteCount
 * * CreateCount
 * * UpdateCount
 */
class UpdateSummaryCounters implements ProcessorInterface
{
    private EntityIdHelper $entityIdHelper;

    public function __construct(EntityIdHelper $entityIdHelper)
    {
        $this->entityIdHelper = $entityIdHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $items = $context->getBatchItems();
        if (!$items) {
            return;
        }

        $summary = $context->getSummary();
        $affectedEntities = $context->getAffectedEntities();
        $processedItemStatuses = $context->getProcessedItemStatuses();
        foreach ($items as $item) {
            if (BatchUpdateItemStatus::NO_ERRORS === $processedItemStatuses[$item->getIndex()]) {
                $context = $item->getContext();
                switch ($context->getTargetAction()) {
                    case ApiAction::CREATE:
                        $summary->incrementWriteCount();
                        $summary->incrementCreateCount();
                        break;
                    case ApiAction::UPDATE:
                        $summary->incrementWriteCount();
                        $summary->incrementUpdateCount();
                        break;
                }
                $this->addAffectedEntities($affectedEntities, $context);
            }
        }
    }

    private function addAffectedEntities(BatchAffectedEntities $affectedEntities, BatchUpdateItemContext $context): void
    {
        $targetContext = $context->getTargetContext();
        if (!$targetContext instanceof FormContext) {
            return;
        }
        $affectedEntities->addPrimaryEntity(
            $this->entityIdHelper->getEntityIdentifier($targetContext->getResult(), $targetContext->getMetadata()),
            $targetContext->getRequestId(),
            $targetContext->isExisting()
        );

        $includedEntities = $targetContext->getIncludedEntities();
        if (null !== $includedEntities) {
            foreach ($includedEntities as $includedEntity) {
                /** @var IncludedEntityData $includedEntityData */
                $includedEntityData = $includedEntities->getData($includedEntity);
                /** @var EntityMetadata $includedEntityMetadata */
                $includedEntityMetadata = $includedEntityData->getMetadata();
                $affectedEntities->addIncludedEntity(
                    $includedEntityMetadata->getClassName(),
                    $this->entityIdHelper->getEntityIdentifier($includedEntity, $includedEntityMetadata),
                    $includedEntities->getId($includedEntity),
                    $includedEntityData->isExisting()
                );
            }
        }
    }
}
