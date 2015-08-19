<?php

namespace Oro\Bundle\ActivityListBundle\Placeholder;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UIBundle\Event\BeforeGroupingChainWidgetEvent;

class PlaceholderFilter
{
    /** @var ActivityListChainProvider */
    protected $activityListProvider;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ActivityListChainProvider $activityListChainProvider
     * @param ManagerRegistry           $doctrine
     * @param DoctrineHelper            $doctrineHelper
     * @param ConfigProvider            $configProvider
     */
    public function __construct(
        ActivityListChainProvider $activityListChainProvider,
        ManagerRegistry $doctrine,
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider
    ) {
        $this->activityListProvider = $activityListChainProvider;
        $this->doctrine             = $doctrine;
        $this->doctrineHelper       = $doctrineHelper;
        $this->configProvider       = $configProvider;
    }

    /**
     * Checks if the entity can have activities
     *
     * @param object|null $entity
     * @param int|null    $pageType
     * @return bool
     */
    public function isApplicable($entity = null, $pageType = null)
    {
        if ($pageType === null || !is_object($entity) || !$this->doctrineHelper->isManageableEntity($entity) ||
            $this->doctrineHelper->isNewEntity($entity)) {
            return false;
        }

        $pageType         = (int) $pageType;
        $id               = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        $entityClass      = $this->doctrineHelper->getEntityClass($entity);
        $activityListRepo = $this->doctrine->getRepository('OroActivityListBundle:ActivityList');

        return $this->isAllowedOnPage($entity, $pageType) && (
            in_array($entityClass, $this->activityListProvider->getTargetEntityClasses())
            || (bool)$activityListRepo->getRecordsCountForTargetClassAndId($entityClass, $id)
        );
    }

    /**
     * @param object $entity
     * @param int    $pageType
     * @return bool
     */
    protected function isAllowedOnPage($entity, $pageType)
    {
        if (!$this->configProvider->hasConfig($entity)) {
            return false;
        }

        $config = $this->configProvider->getConfig($entity);
        if (!$config->has(ActivityScope::SHOW_ON_PAGE)) {
            return false;
        }

        $configValue = $config->get(ActivityScope::SHOW_ON_PAGE);
        if (!defined($configValue)) {
            throw new \InvalidArgumentException(sprintf('Constant %s is not defined', $configValue));
        }

        $configValue = constant($configValue);

        return $configValue !== ActivityScope::NONE_PAGE && ($configValue & $pageType) === $pageType;
    }

    /**
     * @param BeforeGroupingChainWidgetEvent $event
     */
    public function isAllowedButton(BeforeGroupingChainWidgetEvent $event)
    {
        $entity   = $event->getEntity();
        $pageType = $event->getPageType();

        if ($pageType === null || !is_object($entity) || !$this->isAllowedOnPage($entity, $pageType)) {
            // Clear allowed widgets
            $event->setWidgets([]);
        }
    }
}
