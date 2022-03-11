<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Async\Topic;

use Oro\Bundle\MessageQueueBundle\Test\Model\StdModel;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Test topic with body that is normalized by option resolver.
 * Declared as a service only in test env.
 */
class SampleNormalizableBodyTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.message_queue.test.normalizable_body';
    }

    public static function getDescription(): string
    {
        return 'Test topic with body that is normalized by option resolver.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('entity', [])
            ->setAllowedTypes('entity', 'array')
            ->setNormalizer(
                'entity',
                static fn (Options $allOptions, array $option) => new StdModel($option)
            );
    }
}
