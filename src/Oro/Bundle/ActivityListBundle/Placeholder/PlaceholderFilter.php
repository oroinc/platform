<?php

namespace Oro\Bundle\ActivityListBundle\Placeholder;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;

/**
 * Can be used in placeholders to determine applicability of activity to certain entity (e.g. whether this entity has
 * any applicable activities).
 * It also serves as a listener on oro.ui.grouping_chain_widget.before event.
 */
class PlaceholderFilter
{
    /** @var ActivityListChainProvider */
    protected $activityListProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var array[] */
    protected $applicableCache = [];

    public function __construct(
        ActivityListChainProvider $activityListChainProvider,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->activityListProvider = $activityListChainProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * Checks if the entity can have activities
     *
     * @param object|null $entity
     * @param int|null    $pageType
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isApplicable($entity = null, $pageType = null)
    {
        if (null === $pageType
            || !is_object($entity)
            || !$this->doctrineHelper->isManageableEntity($entity)
            || $this->doctrineHelper->isNewEntity($entity)
        ) {
            return false;
        }

        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        if (isset($this->applicableCache[$entityClass])) {
            return $this->applicableCache[$entityClass];
        }

        $result = false;
        if ($this->configManager->hasConfig($entityClass)
            && $this->isAllowedOnPage($entityClass, $pageType)
            && $this->hasApplicableActivityAssociations($entityClass)
        ) {
            $result =
                in_array($entityClass, $this->activityListProvider->getTargetEntityClasses(), true)
                || !$this->isActivityListEmpty(
                    $entityClass,
                    $this->doctrineHelper->getSingleEntityIdentifier($entity)
                );
        }

        $this->applicableCache[$entityClass] = $result;

        return $result;
    }

    public function isAllowedButton(BeforeGroupingChainWidgetEvent $event)
    {
        $entity   = $event->getEntity();
        $pageType = $event->getPageType();

        if ($pageType === null
            || !is_object($entity)
            || !$this->configManager->hasConfig($this->doctrineHelper->getEntityClass($entity))
            || !$this->isAllowedOnPage($this->doctrineHelper->getEntityClass($entity), $pageType)
            || $this->doctrineHelper->isNewEntity($entity)
        ) {
            // Clear allowed widgets
            $event->setWidgets([]);
        }
    }

    /**
     * Checks whether the activity list has at least one accessible activity type
     *
     * @param string $entityClass
     *
     * @return bool
     */
    protected function hasApplicableActivityAssociations($entityClass)
    {
        $supportedActivities = $this->activityListProvider->getSupportedActivities();
        foreach ($supportedActivities as $supportedActivity) {
            if ($this->activityListProvider->isApplicableTarget($entityClass, $supportedActivity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $entityClass
     * @param int    $pageType
     *
     * @return bool
     */
    protected function isAllowedOnPage($entityClass, $pageType)
    {
        return ActivityScope::isAllowedOnPage(
            $pageType,
            $this->configManager->getEntityConfig('activity', $entityClass)->get(ActivityScope::SHOW_ON_PAGE)
        );
    }

    /**
     * Checks whether the activity list has data regarding a given entity
     *
     * @param string $targetEntityClass
     * @param int    $targetEntityId
     *
     * @return bool
     */
    protected function isActivityListEmpty($targetEntityClass, $targetEntityId)
    {
        /** @var ActivityListRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepositoryForClass(ActivityList::class);

        return 0 === $repo->getRecordsCountForTargetClassAndId($targetEntityClass, $targetEntityId);
    }
}
