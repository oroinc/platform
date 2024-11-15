<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * Represents a service that provides a functionality to load a text representation of entities.
 */
interface EntityTitleProviderInterface
{
    /**
     * Returns a text representation of entities.
     *
     * @param array $targets [entity class => [entity id field name, [entity id, ...]], ...]
     *                       The entity id field name can be:
     *                       a string for entities with single field identifier
     *                       an array of strings for entities with composite identifier
     *
     * @return array [['id' => entity id, 'entity' => entity class, 'title' => entity title], ...]
     */
    public function getTitles(array $targets): array;
}
