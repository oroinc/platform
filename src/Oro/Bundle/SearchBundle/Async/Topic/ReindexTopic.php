<?php

namespace Oro\Bundle\SearchBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reindex search index.
 */
class ReindexTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.search.reindex';
    }

    public static function getDescription(): string
    {
        return 'Reindex search index';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
    }

    public function createJobName($messageBody): string
    {
        return self::getName();
    }
}
