<?php

namespace Oro\Bundle\ApiBundle\Batch\Async\Topic;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to create child jobs that are used to process API batch operation chunks.
 */
class UpdateListCreateChunkJobsTopic extends AbstractUpdateListTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.api.update_list.create_chunk_jobs';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Creates child jobs that are used to process API batch operation chunks.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired('rootJobId')
            ->setAllowedTypes('rootJobId', 'int');

        $resolver
            ->setRequired('chunkJobNameTemplate')
            ->setAllowedTypes('chunkJobNameTemplate', 'string');

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
