<?php

namespace Oro\Bundle\ApiBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to delete an asynchronous operation.
 */
class DeleteAsyncOperationTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.api.delete_async_operation';
    }

    public static function getDescription(): string
    {
        return 'Deletes an asynchronous operation.';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('operationId')
            ->setAllowedTypes('operationId', 'int');
    }
}
