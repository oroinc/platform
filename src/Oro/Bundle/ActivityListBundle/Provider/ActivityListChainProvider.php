<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListGroupProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListUpdatedByProviderInterface;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager as Config;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides information required to build the activity list, delegating the retrieving of this information
 * to providers registered for each of activity entity.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ActivityListChainProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityListProviderInterface[] */
    protected $providers;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var EntityRoutingHelper */
    protected $routingHelper;

    /** @var array */
    protected $targetClasses;

    /** @var string[] */
    protected $activities;

    /** @var string[] */
    protected $ownerActivities;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /**
     * @param DoctrineHelper         $doctrineHelper
     * @param ConfigManager          $configManager
     * @param TranslatorInterface    $translator
     * @param EntityRoutingHelper    $routingHelper
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        EntityRoutingHelper $routingHelper,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager  = $configManager;
        $this->translator     = $translator;
        $this->routingHelper  = $routingHelper;
        $this->tokenAccessor  = $tokenAccessor;
    }

    /**
     * Add activity list provider
     *
     * @param ActivityListProviderInterface $provider
     */
    public function addProvider(ActivityListProviderInterface $provider)
    {
        $this->providers[$provider->getActivityClass()] = $provider;

        $this->activities      = null;
        $this->ownerActivities = null;
        $this->targetClasses   = null;
    }

    /**
     * Get all registered providers
     *
     * @return ActivityListProviderInterface[] [activity class => provider, ...]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * Get array with all target classes (entities where activity can be assigned to)
     *
     * @param bool $accessible Whether only targets are ready to be used in a business logic should be returned.
     *                         It means that an association with the target entity should exist
     *                         and should not be marked as deleted.
     *
     * @return string[]
     */
    public function getTargetEntityClasses($accessible = true)
    {
        if (null === $this->targetClasses || !isset($this->targetClasses[$accessible])) {
            $targetClasses = [];
            /** @var ConfigIdInterface[] $configIds */
            $configIds = $this->configManager->getIds('entity');
            foreach ($configIds as $configId) {
                $entityClass = $configId->getClassName();
                foreach ($this->providers as $provider) {
                    if ($provider->isApplicableTarget($entityClass, $accessible)) {
                        $targetClasses[] = $entityClass;
                        break;
                    }
                }
            }
            $this->targetClasses[$accessible] = $targetClasses;
        }

        return $this->targetClasses[$accessible];
    }

    /**
     * @param string $targetClassName
     * @param string $activityClassName
     *
     * @return bool
     */
    public function isApplicableTarget($targetClassName, $activityClassName)
    {
        return
            isset($this->providers[$activityClassName])
            && $this->providers[$activityClassName]->isApplicableTarget($targetClassName);
    }

    /**
     * Get array with supported activity classes
     *
     * @return array
     */
    public function getSupportedActivities()
    {
        if (null === $this->activities) {
            $this->activities = array_keys($this->providers);
        }

        return $this->activities;
    }

    /**
     * Get array with supported activity owner classes
     *
     * @return array
     */
    public function getSupportedOwnerActivities()
    {
        if (null === $this->ownerActivities) {
            $this->ownerActivities = [];
            foreach ($this->providers as $provider) {
                $this->ownerActivities[] = $provider->getAclClass();
            }
        }

        return $this->ownerActivities;
    }

    /**
     * Check if given activity entity supports by activity list providers
     *
     * @param $entity
     *
     * @return bool
     */
    public function isSupportedEntity($entity)
    {
        return in_array(
            $this->doctrineHelper->getEntityClass($entity),
            $this->getSupportedActivities(),
            true
        );
    }

    /**
     * Check if given target entity supports by target classes list
     *
     * @param $entity
     *
     * @return bool
     */
    public function isSupportedTargetEntity($entity)
    {
        return in_array(
            $this->doctrineHelper->getEntityClass($entity),
            $this->getTargetEntityClasses(),
            true
        );
    }

    /**
     * Check if given owner activity entity supports by activity list providers
     *
     * @param $entity
     *
     * @return bool
     */
    public function isSupportedOwnerEntity($entity)
    {
        return in_array(
            $this->doctrineHelper->getEntityClass($entity),
            $this->getSupportedOwnerActivities(),
            true
        );
    }

    /**
     * Returns new activity list entity for given activity
     *
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
     * Get activity list by class and id of entity
     *
     * @param object $entity
     * @param EntityManager $entityManager
     *
     * @return mixed
     */
    public function getActivityListByEntity($entity, EntityManager $entityManager)
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        foreach ($this->providers as $provider) {
            if ($entityClass === $provider->getAclClass()) {
                $entityClass = $provider->getActivityClass();
                $entityId = $provider->getActivityId($entity);
            }
        }

        return $entityManager->getRepository(ActivityList::ENTITY_NAME)->findOneBy(
            [
                'relatedActivityClass' => $entityClass,
                'relatedActivityId'    => $entityId
            ]
        );
    }

    /**
     * Returns updated activity list entity for given activity
     *
     * @param object        $entity
     * @param EntityManager $entityManager
     *
     * @return ActivityList
     */
    public function getUpdatedActivityList($entity, EntityManager $entityManager)
    {
        $provider        = $this->getProviderForEntity($entity);
        $existListEntity = $this->getActivityListByEntity($entity, $entityManager);

        if ($existListEntity) {
            return $this->getActivityListEntityForEntity(
                $entity,
                $provider,
                ActivityList::VERB_UPDATE,
                $existListEntity
            );
        }

        return null;
    }

    /**
     * @param Config $config
     *
     * @return array
     */
    public function getActivityListOption(Config $config)
    {
        $entityConfigProvider = $this->configManager->getProvider('entity');
        $templates            = [];

        foreach ($this->providers as $provider) {
            $hasComment = false;

            if ($provider instanceof FeatureToggleableInterface && !$provider->isFeaturesEnabled()) {
                continue;
            }

            if ($provider instanceof CommentProviderInterface) {
                $hasComment = $provider->isCommentsEnabled($provider->getActivityClass());
            }
            $template = $provider->getTemplate();
            if ($provider instanceof ActivityListGroupProviderInterface &&
                $config->get('oro_activity_list.grouping')) {
                $template = $provider->getGroupedTemplate();
            }

            $entityConfig = $entityConfigProvider->getConfig($provider->getActivityClass());
            $templates[$this->routingHelper->getUrlSafeClassName($provider->getActivityClass())] = [
                'icon'         => $entityConfig->get('icon'),
                'label'        => $this->translator->trans($entityConfig->get('label')),
                'template'     => $template,
                'has_comments' => $hasComment,
            ];
        }

        return $templates;
    }

    /**
     * @param object $entity
     *
     * @return string|null
     */
    public function getSubject($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($entity)) {
                return $provider->getSubject($entity);
            }
        }

        return null;
    }

    /**
     * @param object $entity
     *
     * @return string|null
     */
    public function getDescription($entity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($entity)) {
                return $provider->getDescription($entity);
            }
        }

        return null;
    }

    /**
     * Get activity list provider for given activity owner entity
     *
     * @param $activityOwnerEntity
     *
     * @return ActivityListProviderInterface
     */
    public function getProviderForOwnerEntity($activityOwnerEntity)
    {
        foreach ($this->providers as $provider) {
            if ($provider->getAclClass() === $this->doctrineHelper->getEntityClass($activityOwnerEntity)) {
                return $this->getProviderForEntity($provider->getActivityClass());
            }
        }
    }

    /**
     * Get activity list provider for given activity entity
     *
     * @param $activityEntity
     *
     * @return ActivityListProviderInterface
     */
    public function getProviderForEntity($activityEntity)
    {
        return $this->getProviderByClass($this->doctrineHelper->getEntityClass($activityEntity));
    }

    /**
     * Get activity list provider for entity class name
     *
     * @param string $className
     *
     * @return ActivityListProviderInterface
     */
    public function getProviderByClass($className)
    {
        return $this->providers[$className];
    }

    /**
     * Get activity list provider for entity owner class name
     *
     * @param string $className
     *
     * @return ActivityListProviderInterface
     */
    public function getProviderByOwnerClass($className)
    {
        return $this->providers[$className];
    }

    /**
     * @param object                        $entity
     * @param ActivityListProviderInterface $provider
     * @param string                        $verb
     * @param ActivityList|null             $list
     *
     * @return ActivityList|null
     */
    protected function getActivityListEntityForEntity(
        $entity,
        ActivityListProviderInterface $provider,
        $verb = ActivityList::VERB_CREATE,
        $list = null
    ) {
        if ($provider->isApplicable($entity)) {
            if (!$list) {
                $list = new ActivityList();
            }

            $list->setSubject($provider->getSubject($entity));
            $list->setDescription($provider->getDescription($entity));
            $this->setDate($entity, $provider, $list);
            $list->setOwner($provider->getOwner($entity));
            if ($provider instanceof ActivityListUpdatedByProviderInterface) {
                $list->setUpdatedBy($provider->getUpdatedBy($entity));
            } else {
                $updatedByUser = $this->tokenAccessor->getUser();
                if ($updatedByUser instanceof User) {
                    $list->setUpdatedBy($updatedByUser);
                }
            }

            $list->setVerb($verb);

            if ($verb === ActivityList::VERB_UPDATE) {
                $activityListTargets = $list->getActivityListTargets();
                foreach ($activityListTargets as $target) {
                    $list->removeActivityListTarget($target);
                }
            } else {
                $className = $this->doctrineHelper->getEntityClass($entity);
                $list->setRelatedActivityClass($className);
                $list->setRelatedActivityId($this->doctrineHelper->getSingleEntityIdentifier($entity));
                $list->setOrganization($provider->getOrganization($entity));
            }

            $targets = $provider->getTargetEntities($entity);
            foreach ($targets as $target) {
                if ($list->supportActivityListTarget($this->doctrineHelper->getEntityClass($target))) {
                    $list->addActivityListTarget($target);
                }
            }

            return $list;
        }

        return null;
    }

    /**
     * Set Create and Update fields
     *
     * @param $entity
     * @param ActivityListProviderInterface $provider
     * @param ActivityList $list
     */
    protected function setDate($entity, ActivityListProviderInterface $provider, $list)
    {
        if ($provider instanceof ActivityListDateProviderInterface) {
            if ($provider->getCreatedAt($entity)) {
                $list->setCreatedAt($provider->getCreatedAt($entity));
            }
            if ($provider->getUpdatedAt($entity)) {
                $list->setUpdatedAt($provider->getUpdatedAt($entity));
            }
        }
    }
}
