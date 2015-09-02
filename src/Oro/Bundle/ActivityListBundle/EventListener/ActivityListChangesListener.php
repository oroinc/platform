<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\EntityBundle\EventListener\EntityLifecycleListener;

/**
 * Class ActivityListChangesListener
 *
 * @package Oro\Bundle\ActivityListBundle\EventListener
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ActivityListChangesListener extends EntityLifecycleListener
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
        $entity = $args->getEntity();
        if (!$this->isActivityEntity($entity)) {
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
        $entity = $args->getEntity();
        if (!$this->isActivityEntity($entity)) {
            return;
        }

        $this->entityManager = $args->getEntityManager();
        /** @var ActivityList $entity */
        if ($this->isDateUpdatable($entity)) {
            $this->setUpdatedProperties($entity, true);
        }
    }

    /**
     * @param object $entity
     * @param bool $update
     */
    protected function setUpdatedProperties($entity, $update = false)
    {
        $newUpdatedBy = $this->getUser();
        $unitOfWork = $this->entityManager->getUnitOfWork();
        if ($update && $newUpdatedBy != $entity->getEditor()) {
            $unitOfWork->propertyChanged($entity, 'updatedAt', $entity->getUpdatedAt(), $this->getNowDate());
            $unitOfWork->propertyChanged($entity, 'editor', $entity->getEditor(), $newUpdatedBy);
        }
        parent::setUpdatedProperties($entity);
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    protected function isActivityEntity($entity)
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
