<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;

/**
 * Import processor aware of the batch job step execution.
 */
class StepExecutionAwareImportProcessor extends ImportProcessor implements StepExecutionAwareInterface
{
    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    public function setContextRegistry(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;

        if (!$this->contextRegistry) {
            throw new \InvalidArgumentException('Missing ContextRegistry');
        }

        if ($this->strategy instanceof StepExecutionAwareInterface) {
            $this->strategy->setStepExecution($stepExecution);
        }

        $this->setImportExportContext($this->contextRegistry->getByStepExecution($this->stepExecution));
    }
}
