<?php

namespace Oro\Bundle\PlatformBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines MQ topic that should delete the materialized view specified in the message body.
 */
class DeleteMaterializedViewTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.platform.delete_materialized_view';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Deletes the materialized view specified in the message body.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'materializedViewName',
            ])
            ->setRequired([
                'materializedViewName',
            ])
            ->addAllowedTypes('materializedViewName', 'string');
    }
}
