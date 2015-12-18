<?php

namespace Oro\Bundle\ImportExportBundle\Context;

use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

class ContextRegistry
{
    /**
     * @var array
     */
    protected $contexts = [];

    /**
     * @param StepExecution $stepExecution
     * @return ContextInterface
     */
    public function getByStepExecution(StepExecution $stepExecution)
    {
        $key = spl_object_hash($stepExecution);

        $jobName = $stepExecution->getJobExecution()->getJobInstance()->getAlias();

        if (empty($this->contexts[$jobName][$key])) {
            $this->contexts[$jobName][$key] = $this->createByStepExecution($stepExecution);
        }

        return $this->contexts[$jobName][$key];
    }

    /**
     * @param StepExecution $stepExecution
     * @return StepExecutionProxyContext
     */
    protected function createByStepExecution(StepExecution $stepExecution)
    {
        return new StepExecutionProxyContext($stepExecution);
    }

    /**
     * @param JobInstance $jobInstance
     */
    public function clear(JobInstance $jobInstance)
    {
        unset($this->contexts[$jobInstance->getAlias()]);
    }
}
