<?php

namespace Oro\Bundle\ActivityListBundle\Model;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

interface ActivityListProviderInterface
{
    /**
     * Returns true if given target $configId is supported by activity
     *
     * @param ConfigIdInterface $configId
     * @param ConfigManager     $configManager
     *
     * @return bool
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager);

    /**
     * @param object $entity
     *
     * @return string
     */
    public function getSubject($entity);

    /**
     * @param object $entity
     *
     * @return string|null
     */
    public function getDescription($entity);

    /**
     * @param ActivityList $activityListEntity
     *
     * @return array
     */
    public function getData(ActivityList $activityListEntity);

    /**
     * @param object $activityEntity
     * @return Organization|null
     */
    public function getOrganization($activityEntity);

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
     * @return array
     */
    public function getRoutes();

    /**
     * returns a class name of entity for which we monitor changes
     *
     * @return string
     */
    public function getActivityClass();

    /**
     * @param object $entity
     *
     * @return integer
     */
    public function getActivityId($entity);

    /**
     * Check if provider supports given activity
     *
     * @param  object $entity
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
