<?php

namespace Oro\Bundle\ActivityListBundle\Model;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Defines the contract for providing the user who last updated an activity.
 *
 * Implementations of this interface are responsible for extracting the user information
 * from activity entities to identify who last modified or updated the activity. This
 * information is stored in the activity list to provide audit trails and attribution
 * of activity changes. Custom activity providers should implement this interface to
 * ensure proper tracking of who updated each activity in the system.
 */
interface ActivityListUpdatedByProviderInterface
{
    /**
     * @param object $entity
     *
     * @return User|null
     */
    public function getUpdatedBy($entity);
}
