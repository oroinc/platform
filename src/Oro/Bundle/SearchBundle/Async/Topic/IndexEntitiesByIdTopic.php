<?php

namespace Oro\Bundle\SearchBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Index entities by id.
 */
class IndexEntitiesByIdTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.search.index_entities';
    }

    public static function getDescription(): string
    {
        return 'Index entities by id';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['class', 'entityIds'])
            ->addAllowedTypes('class', 'string')
            ->addAllowedTypes('entityIds', ['string[]', 'int[]']);
    }
}
