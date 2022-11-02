<?php

namespace Oro\Bundle\DataGridBundle\Async\Export\Executor;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;

/**
 * Datagrid pre export job executor that operates on a chain of inner executors.
 * Finds a supported executor and passes execution to it.
 */
class DatagridPreExportExecutor implements DatagridPreExportExecutorInterface
{
    /** @var iterable<DatagridPreExportExecutorInterface> */
    private iterable $jobExecutors;

    public function __construct(iterable $jobExecutors)
    {
        $this->jobExecutors = $jobExecutors;
    }

    public function run(JobRunner $jobRunner, Job $job, DatagridInterface $datagrid, array $options): bool
    {
        foreach ($this->jobExecutors as $jobExecutor) {
            if ($jobExecutor->isSupported($datagrid, $options)) {
                return $jobExecutor->run($jobRunner, $job, $datagrid, $options);
            }
        }

        throw new \LogicException(
            sprintf(
                'Job executor is not found for the job #%s, datagrid %s, options: %s',
                $job->getName(),
                $datagrid->getName(),
                json_encode($options, JSON_THROW_ON_ERROR)
            )
        );
    }

    public function isSupported(DatagridInterface $datagrid, array $options): bool
    {
        foreach ($this->jobExecutors as $jobExecutor) {
            if ($jobExecutor->isSupported($datagrid, $options)) {
                return true;
            }
        }

        return false;
    }
}
