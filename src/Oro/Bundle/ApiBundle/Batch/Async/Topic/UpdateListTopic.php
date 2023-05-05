<?php

namespace Oro\Bundle\ApiBundle\Batch\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to split data of API batch update request to chunks
 */
class UpdateListTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.api.update_list';
    }

    public static function getDescription(): string
    {
        return 'Splits data of API batch update request to chunks.';
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
            ->setRequired('fileName')
            ->setAllowedTypes('fileName', 'string');

        $resolver
            ->setRequired('chunkSize')
            ->setAllowedTypes('chunkSize', 'int');

        $resolver
            ->setRequired('includedDataChunkSize')
            ->setAllowedTypes('includedDataChunkSize', 'int');

        $resolver
            ->setDefined('splitterState')
            ->setAllowedTypes('splitterState', 'array');

        $resolver
            ->setDefined('aggregateTime')
            ->setDefault('aggregateTime', 0)
            ->setAllowedTypes('aggregateTime', 'int');
    }

    public function createJobName($messageBody): string
    {
        return sprintf('oro:batch_api:%d', $messageBody['operationId']);
    }
}
