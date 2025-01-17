<?php

namespace Oro\Component\MessageQueue\Test\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A test topic to run unique job
 */
class UniqueJobTestTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.message_queue.unique_test_topic';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Test topic to run unique job.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
