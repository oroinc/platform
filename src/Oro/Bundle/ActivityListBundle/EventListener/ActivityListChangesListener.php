<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\EntityBundle\EventListener\ModifyCreatedAndUpdatedPropertiesListener;

/**
 * Class ActivityListChangesListener
 *
 * @package Oro\Bundle\ActivityListBundle\EventListener
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ActivityListChangesListener extends ModifyCreatedAndUpdatedPropertiesListener
{
    /** @var  ActivityListChainProvider */
    protected $activityListChainProvider;

    /**
     * @param ServiceLink $securityFacadeLink
     * @param ActivityListChainProvider $activityListChainProvider
     */
    public function __construct(ServiceLink $securityFacadeLink, ActivityListChainProvider $activityListChainProvider)
    {
        parent::__construct($securityFacadeLink);
        $this->activityListChainProvider = $activityListChainProvider;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $entity = $args->getEntity();
        if (!$this->isActivityListEntity($entity)) {
            return;
        }
        $this->entityManager = $args->getEntityManager();
        $this->setCreatedProperties($entity);
        $this->setUpdatedProperties($entity);
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }
        $entity = $args->getEntity();
        if (!$this->isActivityListEntity($entity)) {
            return;
        }

        $this->entityManager = $args->getEntityManager();
        /** @var ActivityList $entity */
        if ($this->isDateUpdatable($entity)) {
            $this->setUpdatedProperties($entity);
        }
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    protected function isActivityListEntity($entity)
    {
        return $entity instanceof ActivityList;
    }

    /**
     * @param ActivityList $entity
     *
     * @return bool
     */
    protected function isDateUpdatable($entity)
    {
        $provider = $this->activityListChainProvider->getProviderByClass($entity->getRelatedActivityClass());
        if ($provider instanceof ActivityListDateProviderInterface) {
            return $provider->isDateUpdatable();
        } else {
            return true;
        }
    }
}
