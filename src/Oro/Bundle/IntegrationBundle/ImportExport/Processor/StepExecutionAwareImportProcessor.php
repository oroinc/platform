<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ImportProcessor;

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

    /**
     * @param ContextRegistry $contextRegistry
     */
    public function setContextRegistry(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * @return ExecutionContext
     */
    protected function getJobContext()
    {
        if (!$this->stepExecution) {
            throw new \InvalidArgumentException('Missing StepExecution');
        }

        return $this->stepExecution->getJobExecution()->getExecutionContext();
    }
}
