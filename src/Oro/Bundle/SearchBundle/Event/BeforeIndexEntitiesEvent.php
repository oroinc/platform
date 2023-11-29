<?php

namespace Oro\Bundle\SearchBundle\Event;

/**
 * An event triggered before a start indexing entities in @link IndexListener::indexEntities().
 */
class BeforeIndexEntitiesEvent
{
    public const EVENT_NAME = 'oro_search.before_index_entities';

    public function __construct(
        private array $entities = []
    ) {
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function addEntity(object $entity): void
    {
        $this->entities = array_replace(
            $this->entities,
            [spl_object_id($entity) => $entity]
        );
    }

    public function removeEntity(object $entity): void
    {
        unset($this->entities[spl_object_id($entity)]);
    }
}
