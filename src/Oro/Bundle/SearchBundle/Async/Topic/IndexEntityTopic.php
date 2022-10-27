<?php

namespace Oro\Bundle\SearchBundle\Async\Topic;

use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Index single entity by id.
 */
class IndexEntityTopic extends AbstractTopic
{
    public static function getName(): string
    {
        return 'oro.search.index_entity';
    }

    public static function getDescription(): string
    {
        return 'Index single entity by id';
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['class', 'id'])
            ->addAllowedTypes('class', 'string')
            ->addAllowedTypes('id', ['string', 'int']);
    }
}
