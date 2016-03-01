<?php

namespace Oro\Bundle\ImportExportBundle\Context;

use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

class ContextRegistry
{
    const DEFAULT_ALIAS = 'default';

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

        $alias = self::DEFAULT_ALIAS;
        $jobExecution = $stepExecution->getJobExecution();
        if ($jobExecution) {
            $jobInstance = $jobExecution->getJobInstance();
            if ($jobInstance) {
                $alias = $jobInstance->getAlias();
            }
        }

        if (empty($this->contexts[$alias][$key])) {
            $this->contexts[$alias][$key] = $this->createByStepExecution($stepExecution);
        }

        return $this->contexts[$alias][$key];
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
     * @param JobInstance|null $jobInstance
     */
    public function clear(JobInstance $jobInstance = null)
    {
        $alias = self::DEFAULT_ALIAS;
        if ($jobInstance) {
            $alias = $jobInstance->getAlias();
        }

        unset($this->contexts[$alias]);
    }
}
