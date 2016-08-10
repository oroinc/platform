<?php
namespace Oro\Bundle\MessageQueueBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\MessageQueueBundle\Entity\Job;

class RootJobActionConfiguration
{
    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getConfiguration(ResultRecordInterface $record)
    {
        /** @var Job $job */
        $job = $record->getRootEntity();

        $showInterruptRootJob = true;
        if ($job->isInterrupted() ||
            in_array($job->getStatus(), [Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED])) {
            $showInterruptRootJob = false;
        }

        return [
            'view' => true,
            'interrupt_root_job' => $showInterruptRootJob,
        ];
    }
}
