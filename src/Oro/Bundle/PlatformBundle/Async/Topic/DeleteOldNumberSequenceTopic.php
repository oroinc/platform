<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines MQ topic that should delete old number sequence entries specified in the message body.
 */
class DeleteOldNumberSequenceTopic extends AbstractTopic
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.platform.delete_old_number_sequence';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Deletes old number sequence entries by sequence and discriminator type.';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([
                'sequenceType',
                'discriminatorType',
            ])
            ->setRequired([
                'sequenceType',
                'discriminatorType',
            ])
            ->addAllowedTypes('sequenceType', 'string')
            ->addAllowedTypes('discriminatorType', 'string');
    }
}
