<?php

namespace Oro\Bundle\DataGridBundle\Async\Export\Executor;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;

/**
 * Interface for datagrid pre export job executors.
 * {@see \Oro\Bundle\DataGridBundle\Async\Export\DatagridPreExportMessageProcessor}
 */
interface DatagridPreExportExecutorInterface
{
    public function run(JobRunner $jobRunner, Job $job, DatagridInterface $datagrid, array $options): bool;

    public function isSupported(DatagridInterface $datagrid, array $options): bool;
}
