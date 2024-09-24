<?php

namespace Oro\Bundle\SearchBundle\Async\Topic;

use Oro\Bundle\SearchBundle\Transformer\MessageTransformerInterface;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Index entities by id.
 */
class IndexEntitiesByIdTopic extends AbstractTopic implements JobAwareTopicInterface
{
    #[\Override]
    public static function getName(): string
    {
        return 'oro.search.index_entities';
    }

    #[\Override]
    public static function getDescription(): string
    {
        return 'Index entities by id';
    }

    #[\Override]
    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['class', 'entityIds'])
            ->addAllowedTypes('class', 'string')
            ->addAllowedTypes('entityIds', ['string[]', 'int[]']);
    }

    #[\Override]
    public function createJobName($messageBody): string
    {
        $entityClass = $messageBody[MessageTransformerInterface::MESSAGE_FIELD_ENTITY_CLASS];
        $ids = $messageBody[MessageTransformerInterface::MESSAGE_FIELD_ENTITY_IDS];
        sort($ids);

        return 'search_reindex|' . md5(serialize($entityClass) . serialize($ids));
    }
}
