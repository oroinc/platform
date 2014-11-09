<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Doctrine\ORM\EntityManager;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ActivityListBundle\Manager\ActivityListManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class ActivityListChainProvider
{
    /** @var ServiceLink */
    protected $securityFacadeLink;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityListProviderInterface[] */
    protected $providers;

    /** @var array */
    protected $targetClasses = [];

    /**
     * @param ServiceLink $securityFacadeLink
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ServiceLink $securityFacadeLink, DoctrineHelper $doctrineHelper)
    {
        $this->securityFacadeLink = $securityFacadeLink;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return array
     */
    public function getTargetEntityClasses()
    {
        if (empty($this->targetClasses)) {
            foreach ($this->providers as $provider) {
                $this->targetClasses = array_merge($this->targetClasses, $provider->getTargetEntityClasses());
            }
            $this->targetClasses = array_unique($this->targetClasses);
        }

        return $this->targetClasses;
    }

    /**
     * @param ActivityListProviderInterface $provider
     */
    public function addProvider(ActivityListProviderInterface $provider)
    {
        $this->providers[$provider->getActivityClass()] = $provider;
    }

    /**
     * Get array with supported activity classes
     *
     * @return array
     */
    public function getSupportedActivities()
    {
        return array_keys($this->providers);
    }

    /**
     * Check if given activity entity supports by activity list providers
     *
     * @param $entity
     * @return bool
     */
    public function isSupportedEntity($entity)
    {
        return in_array($this->doctrineHelper->getEntityClass($entity), array_keys($this->providers));
    }

    /**
     * @param object $activityEntity
     *
     * @return ActivityList
     */
    public function getActivityListEntitiesByActivityEntity($activityEntity)
    {
        $provider = $this->getProviderForEntity($activityEntity);
        return $this->getActivityListEntityForEntity($activityEntity, $provider);
    }

    /**
     * @param object        $entity
     * @param EntityManager $entityManager
     * @return ActivityList
     */
    public function getUpdatedActivityList($entity, EntityManager $entityManager)
    {
        $provider = $this->getProviderForEntity($entity);
        $existListEntity = $entityManager->getRepository('OroActivityListBundle:ActivityList')->findOneBy(
            [
                'relatedActivityClass' => $this->doctrineHelper->getEntityClass($entity),
                'relatedActivityId'    => $this->doctrineHelper->getSingleEntityIdentifier($entity)
            ]
        );
        return $this->getActivityListEntityForEntity(
            $entity,
            $provider,
            ActivityListManager::STATE_UPDATE,
            $existListEntity
        );
    }

    /**
     * @param ActivityList $list
     * @param              $provider
     * @param              $entity
     * @param string       $state
     */
    protected function updateListEntity(
        ActivityList $list,
        $provider,
        $entity,
        $state = ActivityListManager::STATE_UPDATE
    ) {
        $list->setSubject($provider->getSubject($entity));
        $list->setVerb($state);
    }

    /**
     * @param object                        $entity
     * @param ActivityListProviderInterface $provider
     * @param string                        $state
     * @param null                          $list
     * @return ActivityList
     */
    protected function getActivityListEntityForEntity(
        $entity,
        ActivityListProviderInterface $provider,
        $state = ActivityListManager::STATE_CREATE,
        $list = null
    ) {
        if (!$list) {
            $list = new ActivityList();
        }

        $list->setOwner($entity->getOwner());
        $list->setOrganization($entity->getOrganization());
        $list->setRelatedActivityClass($this->doctrineHelper->getEntityClass($entity));
        $list->setRelatedActivityId($this->doctrineHelper->getSingleEntityIdentifier($entity));
        $targets = $provider->getTargets($entity);
        if ($state === ActivityListManager::STATE_UPDATE) {
            $activityListTargets = $list->getActivityListTargetEntities();
            foreach ($activityListTargets as $target) {
                $list->removeActivityListTarget($target);
            }
        }
        if (!empty($targets)) {
            foreach ($targets as $target) {
                if ($list->supportActivityListTarget(get_class($target))) {
                    $list->addActivityListTarget($target);
                }
            }
        }

        $this->updateListEntity($list, $provider, $entity, $state);

        return $list;
    }

    /**
     * Get activity list provider for given activity entity
     *
     * @param $entity
     * @return ActivityListProviderInterface
     */
    protected function getProviderForEntity($entity)
    {
        return $this->providers[$this->doctrineHelper->getEntityClass($entity)];
    }
} 