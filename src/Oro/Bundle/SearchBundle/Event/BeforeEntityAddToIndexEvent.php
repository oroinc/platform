<?php

namespace Oro\Bundle\SearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * An event triggered before a message produce for indexing an entity. usual to filter out invalid entity.
 */
class BeforeEntityAddToIndexEvent extends Event
{
    public const EVENT_NAME = 'oro_search.before_add_index';
    protected ?object $entity = null;

    public function __construct(object $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    public function setEntity(?object $entity): void
    {
        $this->entity = $entity;
    }
}
