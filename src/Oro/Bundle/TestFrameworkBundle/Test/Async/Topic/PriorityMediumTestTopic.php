<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Test\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Test topic routed to the medium-priority test queue.
 * Used in functional tests for the `priority` consumption mode.
 */
class PriorityMediumTestTopic extends AbstractTopic
{
    public const string NAME = 'oro.test.priority.medium';

    #[\Override]
    public static function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Test topic for the medium-priority queue in priority consumption mode tests.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined('label')
            ->setAllowedTypes('label', 'string');
    }
}
