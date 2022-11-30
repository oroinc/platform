<?php

namespace Oro\Bundle\ApiBundle\Batch\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to start child jobs that are used to process API batch operation chunks
 */
class UpdateListStartChunkJobsTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.api.update_list.start_chunk_jobs';
    }

    public static function getDescription(): string
    {
        return 'Starts child jobs that are used to process API batch operation chunks.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('operationId')
            ->setAllowedTypes('operationId', 'int');

        $resolver
            ->setRequired('entityClass')
            ->setAllowedTypes('entityClass', 'string');

        $resolver
            ->setRequired('requestType')
            ->setAllowedTypes('requestType', 'string[]');

        $resolver
            ->setRequired('version')
            ->setAllowedTypes('version', 'string');

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
