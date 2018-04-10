<?php

namespace Oro\Bundle\ActivityListBundle\Model;

/**
 * This interface should be implemented by activity list provider
 * in case if the corresponding activity entity supports grouping.
 */
interface ActivityListGroupProviderInterface
{
    /**
     * Gets all activity entities from the same group as the given entity.
     * In case if both $associatedEntityClass and $associatedEntityId are specified,
     * the activity entities will be filtered by the entity they are associated with.
     *
     * @param object      $entity
     * @param string|null $associatedEntityClass
     * @param int|null    $associatedEntityId
     *
     * @return array
     */
    public function getGroupedEntities($entity, $associatedEntityClass = null, $associatedEntityId = null): array;

    /**
     * Collapses the given activity list items to keep only one item from each group.
     * It is supposed that each element in $items array is an array contains at least two keys,
     * "relatedActivityClass" and "relatedActivityId".
     *
     * @param array $items
     *
     * @return array
     */
    public function collapseGroupedItems(array $items): array;

    /**
     * Gets TWIG template that should be used to render a grouped activity entities in the activity list.
     *
     * @return string
     */
    public function getGroupedTemplate(): string;
}
