<?php

namespace Oro\Bundle\DataAuditBundle\Async\Topic;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Create audit entries for entity inverse collections relations.
 */
class AuditChangedEntitiesInverseCollectionsTopic extends AbstractAuditTopic implements JobAwareTopicInterface
{
    public static function getName(): string
    {
        return 'oro.data_audit.entities_inversed_relations_changed.collections';
    }

    public static function getDescription(): string
    {
        return 'Create audit entries for entity inverse collections relations';
    }

    public function getDefaultPriority(string $queueName): string
    {
        return MessagePriority::VERY_LOW;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        parent::configureMessageBody($resolver);

        $resolver
            ->setDefined([
                'entities_inserted',
                'entities_updated',
                'entities_deleted',
                'collections_updated',
            ])
            ->setDefaults([
                'entities_inserted' => [],
                'entities_updated' => [],
                'entities_deleted' => [],
                'collections_updated' => [],
            ])
            ->addAllowedTypes('entities_inserted', 'array')
            ->addAllowedTypes('entities_updated', 'array')
            ->addAllowedTypes('entities_deleted', 'array')
            ->addAllowedTypes('collections_updated', 'array');
    }

    public function createJobName($messageBody): string
    {
        return uniqid(sprintf('%s_', self::getName()));
    }
}
