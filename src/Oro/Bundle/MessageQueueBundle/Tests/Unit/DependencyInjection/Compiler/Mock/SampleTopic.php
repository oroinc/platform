<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Sample topic to test BuildTopicMetaRegistryPass
 */
class SampleTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'sample_topic';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Sample topic to test BuildTopicMetaRegistryPass.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }
}
