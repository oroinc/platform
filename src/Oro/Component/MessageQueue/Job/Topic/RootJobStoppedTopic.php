<?php

namespace Oro\Component\MessageQueue\Job\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Root job is stopped.
 */
class RootJobStoppedTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.message_queue.job.root_job_stopped';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Root job is stopped';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('jobId')
            ->addAllowedTypes('jobId', 'int');
    }
}
