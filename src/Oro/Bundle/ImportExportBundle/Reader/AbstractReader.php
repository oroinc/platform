<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

/**
 * Abstract class for context aware readers
 */
abstract class AbstractReader implements ReaderInterface
{
    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var StepExecution|null
     */
    protected $stepExecution;

    public function __construct(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;

        $this->initializeFromContext($this->getContext());
    }

    /**
     * @return StepExecution
     * @throws LogicException
     */
    protected function getStepExecution()
    {
        if (!$this->stepExecution) {
            throw new LogicException('Step execution must be set');
        }

        return $this->stepExecution;
    }

    /**
     * @return ContextInterface
     */
    protected function getContext()
    {
        return $this->contextRegistry->getByStepExecution($this->getStepExecution());
    }

    /**
     * Should be overridden in descendant classes
     */
    protected function initializeFromContext(ContextInterface $context)
    {
    }

    public function initializeByContext(ContextInterface $context)
    {
        $this->initializeFromContext($context);
    }
}
