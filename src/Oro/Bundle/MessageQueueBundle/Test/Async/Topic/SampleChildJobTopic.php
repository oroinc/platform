<?php

declare(strict_types=1);

namespace Oro\Bundle\MessageQueueBundle\Test\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Test topic with child jobId.
 */
class SampleChildJobTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.message_queue.sample_child_job';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return '';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('jobId')
            ->setAllowedTypes('jobId', 'int');
    }
}
