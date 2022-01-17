<?php

namespace Oro\Component\MessageQueue\Job\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Calculate root job status and progress.
 */
class CalculateRootJobStatusTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.message_queue.job.calculate_root_job_status';
    }

    public static function getDescription(): string
    {
        return 'Calculate root job status and progress';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('jobId')
            ->addAllowedTypes('jobId', 'int');
    }
}
