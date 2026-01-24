<?php

namespace Oro\Bundle\ActivityListBundle\Model;

/**
 * Defines the contract for providing date information from activity entities.
 *
 * Implementations of this interface are responsible for extracting creation and update
 * timestamps from activity entities. These dates are used to populate the activity list
 * with temporal information, enabling proper sorting, filtering, and display of activities
 * in chronological order. Custom activity providers should implement this interface to
 * ensure their activities are correctly timestamped in the activity list.
 */
interface ActivityListDateProviderInterface
{
    /**
     * Get created at from entity.
     *
     * @param object $entity
     * @return \DateTime|null
     */
    public function getCreatedAt($entity);

    /**
     * Get updated at from entity.
     *
     * @param object $entity
     * @return \DateTime|null
     */
    public function getUpdatedAt($entity);
}
