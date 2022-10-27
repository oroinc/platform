<?php

namespace Oro\Bundle\ActivityListBundle\Model;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides a way to use different kind of entities in an activity list.
 */
interface ActivityListProviderInterface
{
    /**
     * Checks whether the activity list can contain a given entity
     *
     * @param string $entityClass The target entity class
     * @param bool   $accessible  Whether only targets are ready to be used in a business logic should be returned.
     *                            It means that an association with the target entity should exist
     *                            and should not be marked as deleted.
     *
     * @return bool
     */
    public function isApplicableTarget($entityClass, $accessible = true);

    /**
     * @param object $entity
     *
     * @return string
     */
    public function getSubject($entity);

    /**
     * Return text representation. Should be a plain text.
     *
     * @param object $entity
     *
     * @return string|null
     */
    public function getDescription($entity);

    /**
     * @param object $entity
     *
     * @return User|null
     */
    public function getOwner($entity);

    /**
     * Get array of ActivityOwners for list entity
     *
     * @param object $entity
     * @param ActivityList $activityList
     *
     * @return ActivityOwner[]
     */
    public function getActivityOwners($entity, ActivityList $activityList);

    /**
     * Checks whether the given item is applicable to be added to the activity list.
     */
    public function isActivityListApplicable(ActivityList $activityList): bool;

    /**
     * @param ActivityList $activityList
     *
     * @return array
     */
    public function getData(ActivityList $activityList);

    /**
     * @param object $entity
     *
     * @return Organization|null
     */
    public function getOrganization($entity);

    /**
     * @return string
     */
    public function getTemplate();

    /**
     * Should return array of route names as key => value
     * e.g. [
     *      'itemView'  => 'item_view_route',
     *      'itemEdit'  => 'item_edit_route',
     *      'itemDelete => 'item_delete_route'
     * ]
     *
     * @param object $entity
     * @return array
     */
    public function getRoutes($entity);

    /**
     * @param object $entity
     *
     * @return integer
     */
    public function getActivityId($entity);

    /**
     * Check if provider supports given activity
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isApplicable($entity);

    /**
     * Returns array of assigned entities for activity
     *
     * @param object $entity
     *
     * @return array
     */
    public function getTargetEntities($entity);
}
