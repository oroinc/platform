<?php

namespace Oro\Bundle\ActivityBundle\Tools;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityAssociationHelper
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Checks whether a given activity type is enabled for a given entity type.
     *
     * @param string $entityClass   The target entity class
     * @param string $activityClass The activity entity class
     * @param bool $accessible      Whether an association with the target entity should be checked
     *                              to be ready to use in a business logic.
     *                              It means that the association should exist and should not be marked as deleted.
     *
     * @return bool
     */
    public function isActivityAssociationEnabled($entityClass, $activityClass, $accessible = true)
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return false;
        }

        $activities = $this->configManager
            ->getEntityConfig('activity', $entityClass)
            ->get('activities', false, []);
        if (!in_array($activityClass, $activities, true)) {
            return false;
        }

        return
            !$accessible
            || $this->isActivityAssociationAccessible($entityClass, $activityClass);
    }

    /**
     * Checks whether a given entity type has at least one enabled activity association.
     *
     * @param string $entityClass The target entity class
     *
     * @return bool
     */
    public function hasActivityAssociations($entityClass)
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return false;
        }

        $activities = $this->configManager
            ->getEntityConfig('activity', $entityClass)
            ->get('activities', false, []);
        foreach ($activities as $activityClass) {
            if ($this->isActivityAssociationAccessible($entityClass, $activityClass)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an association between a given entity type and activity type is ready to be used in a business logic.
     * It means that the association should exist and should not be marked as deleted.
     *
     * @param string $entityClass   The target entity class
     * @param string $activityClass The activity entity class
     *
     * @return bool
     */
    protected function isActivityAssociationAccessible($entityClass, $activityClass)
    {
        $associationName = ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND);
        if (!$this->configManager->hasConfig($activityClass, $associationName)) {
            return false;
        }

        return ExtendHelper::isFieldAccessible(
            $this->configManager->getFieldConfig('extend', $activityClass, $associationName)
        );
    }
}
