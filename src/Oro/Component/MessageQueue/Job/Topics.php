<?php
namespace Oro\Component\MessageQueue\Job;

class Topics
{
    const CALCULATE_ROOT_JOB_STATUS = 'oro.message_queue.job.calculate_root_job_status';
    const CALCULATE_ROOT_JOB_PROGRESS = 'oro.message_queue.job.calculate_root_job_progress';
    const ROOT_JOB_STOPPED = 'oro.message_queue.job.root_job_stopped';
}
