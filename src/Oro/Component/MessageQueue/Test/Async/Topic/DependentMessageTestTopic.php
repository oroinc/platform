<?php

namespace Oro\Component\MessageQueue\Test\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A test topic for dependent message processor
 */
class DependentMessageTestTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.message_queue.test_topic';
    }

    public static function getDescription(): string
    {
        return 'Test topic for dependent message processor.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
