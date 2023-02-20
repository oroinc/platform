<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Processor\BatchUpdateItemProcessor;
use Oro\Bundle\ApiBundle\Batch\Processor\BatchUpdateProcessor;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Processor\StepExecutor;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;

/**
 * The handler for API batch update operation.
 */
class BatchUpdateHandler
{
    private StepExecutor $stepExecutor;
    private BatchUpdateItemProcessor $itemProcessor;

    public function __construct(BatchUpdateProcessor $processor, BatchUpdateItemProcessor $itemProcessor)
    {
        $this->stepExecutor = new StepExecutor($processor);
        $this->itemProcessor = $itemProcessor;
    }

    public function handle(BatchUpdateRequest $request): BatchUpdateResponse
    {
        /** @var BatchUpdateContext $context */
        $context = $this->stepExecutor->createContext();
        $context->setVersion($request->getVersion());
        $context->getRequestType()->set($request->getRequestType());
        $context->setOperationId($request->getOperationId());
        $context->setFileManager($request->getFileManager());
        $context->setFile($request->getFile());
        $context->setSupportedEntityClasses($request->getSupportedEntityClasses());
        $context->setSoftErrorsHandling(true);

        $this->process($context);
        $this->stepExecutor->executeStep(ApiActionGroup::SAVE_ERRORS, $context, false);

        return new BatchUpdateResponse(
            $context->getResult() ?? [],
            $context->getProcessedItemStatuses() ?? [],
            $context->getSummary(),
            $context->hasUnexpectedErrors(),
            $context->getRetryReason()
        );
    }

    private function process(BatchUpdateContext $context): void
    {
        $this->stepExecutor->executeStep(ApiActionGroup::INITIALIZE, $context);
        if ($context->hasErrors() || $context->isRetryAgain()) {
            return;
        }

        $records = $context->getResult();
        if ($records) {
            $context->getSummary()->incrementReadCount(\count($records));
            $context->setBatchItems($this->processRecords($records, $context));
            $this->stepExecutor->executeStep(ApiActionGroup::SAVE_DATA, $context);
            if ($context->hasErrors() || $context->isRetryAgain()) {
                return;
            }
        }

        $this->stepExecutor->executeStep(ApiActionGroup::FINALIZE, $context);
    }

    /**
     * @param array              $records
     * @param BatchUpdateContext $context
     *
     * @return BatchUpdateItem[]
     */
    private function processRecords(array $records, BatchUpdateContext $context): array
    {
        $items = [];
        foreach ($records as $index => $record) {
            $item = new BatchUpdateItem($index, $this->itemProcessor, $context);
            $items[] = $item;

            $item->initialize($record);
            if (!$item->getContext()->hasErrors()) {
                $item->transform();
            }
        }

        return $items;
    }
}
