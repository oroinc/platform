<?php

namespace Oro\Bundle\ApiBundle\Batch\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to start child jobs that are used to process API batch operation chunks.
 */
class UpdateListStartChunkJobsTopic extends AbstractUpdateListTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.api.update_list.start_chunk_jobs';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Starts child jobs that are used to process API batch operation chunks.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired('rootJobId')
            ->setAllowedTypes('rootJobId', 'int');

        $resolver
            ->setDefined('firstChunkFileIndex')
            ->setDefault('firstChunkFileIndex', 0)
            ->setAllowedTypes('firstChunkFileIndex', 'int');

        $resolver
            ->setDefined('aggregateTime')
            ->setDefault('aggregateTime', 0)
            ->setAllowedTypes('aggregateTime', 'int');
    }
}
