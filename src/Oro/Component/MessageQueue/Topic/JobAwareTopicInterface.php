<?php

namespace Oro\Component\MessageQueue\Topic;

/**
 * Interface for job aware message queue topic
 */
interface JobAwareTopicInterface
{
    public const UNIQUE_JOB_NAME = 'oro.message_queue.job_name';

    public function createJobName($messageBody): string;
}
