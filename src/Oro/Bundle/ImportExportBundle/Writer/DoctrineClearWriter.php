<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

/**
 * Batch job writer that clears entity manager.
 */
class DoctrineClearWriter implements ItemWriterInterface, StepExecutionAwareInterface
{
    public const SKIP_CLEAR = 'skip_clear';

    private ManagerRegistry $registry;
    private ContextRegistry $context;
    private ?StepExecution $stepExecution = null;

    public function __construct(ManagerRegistry $registry, ContextRegistry $contextRegistry)
    {
        $this->registry = $registry;
        $this->context = $contextRegistry;
    }

    public function write(array $items): void
    {
        if (!$this->getContext()?->getValue(self::SKIP_CLEAR)) {
            $this->registry->getManager()->clear();
        }
    }

    public function setStepExecution(StepExecution $stepExecution): void
    {
        $this->stepExecution = $stepExecution;
    }

    private function getContext(): ?ContextInterface
    {
        return $this->stepExecution ? $this->context->getByStepExecution($this->stepExecution) : null;
    }
}
