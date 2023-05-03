<?php

namespace Oro\Bundle\DataAuditBundle\Async\Topic;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Create audit entries for entity relations.
 */
class AuditChangedEntitiesRelationsTopic extends AbstractAuditTopic
{
    public static function getName(): string
    {
        return 'oro.data_audit.entities_relations_changed';
    }

    public static function getDescription(): string
    {
        return 'Create audit entries for entity relations';
    }

    public function getDefaultPriority(string $queueName): string
    {
        return MessagePriority::VERY_LOW;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setRequired([
                'collections_updated',
            ])
            ->addAllowedTypes('collections_updated', 'array')
            ->addAllowedValues('collections_updated', static function ($value) {
                if (!count($value)) {
                    throw new InvalidOptionsException('The "collections_updated" was expected to be not empty.');
                }

                return true;
            });
    }
}
