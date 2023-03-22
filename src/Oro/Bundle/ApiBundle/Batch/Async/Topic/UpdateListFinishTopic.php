<?php

namespace Oro\Bundle\ApiBundle\Batch\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to finish the processing of API batch update request
 */
class UpdateListFinishTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.api.update_list.finish';
    }

    public static function getDescription(): string
    {
        return 'Finishes the processing of API batch update request.';
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
    }
}
