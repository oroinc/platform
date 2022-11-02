<?php

namespace Oro\Bundle\SearchBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reindex search index.
 */
class ReindexTopic extends AbstractTopic
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
}
