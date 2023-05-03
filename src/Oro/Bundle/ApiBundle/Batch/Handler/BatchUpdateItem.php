<?php

namespace Oro\Bundle\ApiBundle\Batch\Handler;

use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Bundle\ApiBundle\Batch\Processor\BatchUpdateItemProcessor;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Processor\StepExecutor;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;

/**
 * Represents a single item for API batch update operation.
 */
class BatchUpdateItem
{
    private StepExecutor $stepExecutor;
    private BatchUpdateContext $updateContext;
    private int $index;
    private ?BatchUpdateItemContext $context = null;

    public function __construct(
        int $index,
        BatchUpdateItemProcessor $processor,
        BatchUpdateContext $updateContext
    ) {
        $this->index = $index;
        $this->stepExecutor = new StepExecutor($processor);
        $this->updateContext = $updateContext;
    }

    /**
     * Gets the index of the source record related to the batch item.
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * Gets the context of the batch item.
     */
    public function getContext(): BatchUpdateItemContext
    {
        if (null === $this->context) {
            $this->context = $this->stepExecutor->createContext();
            $this->context->setVersion($this->updateContext->getVersion());
            $this->context->getRequestType()->set($this->updateContext->getRequestType());
            $this->context->setSharedData($this->updateContext->getSharedData());
            $this->context->setSummary($this->updateContext->getSummary());
            $this->context->setSupportedEntityClasses($this->updateContext->getSupportedEntityClasses());
        }

        return $this->context;
    }

    /**
     * Gets included data.
     */
    public function getIncludedData():? IncludedData
    {
        return $this->updateContext->getIncludedData();
    }

    /**
     * Initializes the related target action.
     */
    public function initialize(mixed $data): void
    {
        $context = $this->getContext();
        $context->setRequestData($data);
        $this->stepExecutor->executeStep(ApiActionGroup::INITIALIZE, $context);
    }

    /**
     * Transforms the request data to an entity object.
     */
    public function transform(): void
    {
        $context = $this->getContext();
        try {
            $this->stepExecutor->executeStep(ApiActionGroup::TRANSFORM_DATA, $context);
        } finally {
            if ($context->hasErrors()) {
                $context->removeResult();
            }
        }
    }
}
