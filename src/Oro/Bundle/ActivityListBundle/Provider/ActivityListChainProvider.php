<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Factory\ActivityListFactory;
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
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides information required to build the activity list, delegating the retrieving of this information
 * to providers registered for each of activity entity.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ActivityListChainProvider implements ResetInterface
{
    /** @var array [accessible flag => [class name, ...], ...] */
    private $targetClasses;

    /** @var string[] */
    private $ownerActivityClasses;

    /** @var ActivityListProviderInterface[] */
    private $providers;

    /**
     * @param string[]               $activityClasses
     * @param string[]               $activityAclClasses
     * @param ContainerInterface     $providerContainer
     * @param DoctrineHelper         $doctrineHelper
     * @param ConfigManager          $configManager
     * @param TranslatorInterface    $translator
     * @param EntityRoutingHelper    $routingHelper
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        private array $activityClasses,
        private array $activityAclClasses,
        private ContainerInterface $providerContainer,
        private DoctrineHelper $doctrineHelper,
        private ConfigManager $configManager,
        private TranslatorInterface $translator,
        private EntityRoutingHelper $routingHelper,
        private TokenAccessorInterface $tokenAccessor,
        private ActivityListFactory $activityListFactory
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function reset()
    {
        $this->ownerActivityClasses = null;
        $this->targetClasses = null;
        $this->providers = null;
    }

    /**
     * Gets all registered activity list providers.
     *
     * @return ActivityListProviderInterface[] [activity class => provider, ...]
     */
    public function getProviders(): array
    {
        if (null === $this->providers) {
            $this->providers = [];
            foreach ($this->activityClasses as $activityClass) {
                $this->providers[$activityClass] = $this->providerContainer->get($activityClass);
            }
        }

        return $this->providers;
    }

    /**
     * Gets the list of all target entity classes (entities to which an activity can be assigned to).
     *
     * @param bool $accessible Whether only targets are ready to be used in a business logic should be returned.
     *                         It means that an association with the target entity should exist
     *                         and should not be marked as deleted.
     *
     * @return string[]
     */
    public function getTargetEntityClasses(bool $accessible = true): array
    {
        $accessibleKey = (string)$accessible;
        if (null === $this->targetClasses || !isset($this->targetClasses[$accessibleKey])) {
            $targetClasses = [];
            /** @var ConfigIdInterface[] $configIds */
            $configIds = $this->configManager->getIds('entity');
            foreach ($configIds as $configId) {
                $entityClass = $configId->getClassName();
                $providers = $this->getProviders();
                foreach ($providers as $provider) {
                    if ($provider->isApplicableTarget($entityClass, $accessible)) {
                        $targetClasses[] = $entityClass;
                        break;
                    }
                }
            }
            $this->targetClasses[$accessibleKey] = $targetClasses;
        }

        return $this->targetClasses[$accessibleKey];
    }

    public function isApplicableTarget(string $targetClass, string $activityClass): bool
    {
        return
            \in_array($activityClass, $this->activityClasses, true)
            && $this->getProviderByClass($activityClass)->isApplicableTarget($targetClass);
    }

    /**
     * Gets the list of supported activity classes.
     *
     * @return string[]
     */
    public function getSupportedActivities(): array
    {
        return $this->activityClasses;
    }

    /**
     * Gets the list of supported activity owner classes.
     *
     * @return string[]
     */
    public function getSupportedOwnerActivities(): array
    {
        if (null === $this->ownerActivityClasses) {
            $this->ownerActivityClasses = [];
            foreach ($this->activityClasses as $activityClass) {
                $this->ownerActivityClasses[] = $this->activityAclClasses[$activityClass] ?? $activityClass;
            }
        }

        return $this->ownerActivityClasses;
    }

    /**
     * Gets a supported activity owner class for the given activity class.
     */
    public function getSupportedOwnerActivity(string $activityClass): string
    {
        return $this->activityAclClasses[$activityClass] ?? $activityClass;
    }

    /**
     * Checks if the given activity entity is supported by activity list providers.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isSupportedEntity($entity): bool
    {
        return \in_array(
            $this->doctrineHelper->getEntityClass($entity),
            $this->getSupportedActivities(),
            true
        );
    }

    /**
     * Checks if the given target entity (an entity to which an activity can be assigned to) is supported
     * by activity list providers.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isSupportedTargetEntity($entity): bool
    {
        return \in_array(
            $this->doctrineHelper->getEntityClass($entity),
            $this->getTargetEntityClasses(),
            true
        );
    }

    /**
     * Checks if the given owner activity entity is supported by activity list providers.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isSupportedOwnerEntity($entity): bool
    {
        return \in_array(
            $this->doctrineHelper->getEntityClass($entity),
            $this->getSupportedOwnerActivities(),
            true
        );
    }

    /**
     * Gets a new ActivityList entity for the given activity entity.
     *
     * @param object $activityEntity
     *
     * @return ActivityList|null
     */
    public function getActivityListEntitiesByActivityEntity($activityEntity): ?ActivityList
    {
        return $this->getActivityListEntityForEntity($activityEntity, $this->getProviderForEntity($activityEntity));
    }

    /**
     * Gets an activity list by class and id of an entity.
     */
    public function getActivityListByEntity(object $entity, EntityManagerInterface $entityManager): ?ActivityList
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        foreach ($this->activityClasses as $activityClass) {
            $aclClass = $this->activityAclClasses[$activityClass] ?? $activityClass;
            if ($entityClass === $aclClass) {
                $entityClass = $activityClass;
                $entityId = $this->getProviderByClass($activityClass)->getActivityId($entity);
            }
        }

        return $entityManager->getRepository(ActivityList::class)->findOneBy([
            'relatedActivityClass' => $entityClass,
            'relatedActivityId'    => $entityId
        ]);
    }

    /**
     * Returns an updated activity list entity for the given activity.
     */
    public function getUpdatedActivityList(object $entity, EntityManagerInterface $entityManager): ?ActivityList
    {
        $existListEntity = $this->getActivityListByEntity($entity, $entityManager);
        if (!$existListEntity) {
            return null;
        }

        return $this->getActivityListEntityForEntity(
            $entity,
            $this->getProviderForEntity($entity),
            ActivityList::VERB_UPDATE,
            $existListEntity
        );
    }

    /**
     * Tries to create a new instance of activity list entity for the given activity entity.
     */
    public function getNewActivityList(object $entity): ?ActivityList
    {
        return $this->getActivityListEntityForEntity(
            $entity,
            $this->getProviderForEntity($entity)
        );
    }

    public function getActivityListOption(Config $config): array
    {
        $templates = [];
        $entityConfigProvider = $this->configManager->getProvider('entity');
        $providers = $this->getProviders();
        foreach ($providers as $activityClass => $provider) {
            if ($provider instanceof FeatureToggleableInterface && !$provider->isFeaturesEnabled()) {
                continue;
            }

            $hasComment = false;
            if ($provider instanceof CommentProviderInterface) {
                $hasComment = $provider->isCommentsEnabled($activityClass);
            }

            $template = $provider->getTemplate();
            if ($provider instanceof ActivityListGroupProviderInterface
                && $config->get('oro_activity_list.grouping')
            ) {
                $template = $provider->getGroupedTemplate();
            }

            $entityConfig = $entityConfigProvider->getConfig($activityClass);
            $templates[$this->routingHelper->getUrlSafeClassName($activityClass)] = [
                'icon'         => $entityConfig->get('icon'),
                'label'        => $this->translator->trans((string) $entityConfig->get('label')),
                'template'     => $template,
                'has_comments' => $hasComment
            ];
        }

        return $templates;
    }

    /**
     * @param object $entity
     *
     * @return string|null
     */
    public function getSubject($entity): ?string
    {
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
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
    public function getDescription($entity): ?string
    {
        $providers = $this->getProviders();
        foreach ($providers as $provider) {
            if ($provider->isApplicable($entity)) {
                return $provider->getDescription($entity);
            }
        }

        return null;
    }

    /**
     * Gets an activity list provider for the given activity class.
     *
     * @throws \LogicException if a provider for the given activity class does not exist
     */
    public function getProviderByClass(string $activityClass): ActivityListProviderInterface
    {
        if (null === $this->providers) {
            if (!$this->providerContainer->has($activityClass)) {
                throw $this->createProviderNotFoundException($activityClass);
            }

            return $this->providerContainer->get($activityClass);
        }

        if (!isset($this->providers[$activityClass])) {
            throw $this->createProviderNotFoundException($activityClass);
        }

        return $this->providers[$activityClass];
    }

    /**
     * Gets an activity list provider for the given activity entity.
     *
     * @param object $activityEntity
     *
     * @return ActivityListProviderInterface
     *
     * @throws \LogicException if a provider for the given activity entity does not exist
     */
    public function getProviderForEntity($activityEntity): ActivityListProviderInterface
    {
        return $this->getProviderByClass($this->doctrineHelper->getEntityClass($activityEntity));
    }

    /**
     * Gets an activity list provider for the given activity owner class.
     *
     * @throws \LogicException if a provider for the given owner class does not exist
     */
    public function getProviderByOwnerClass(string $activityOwnerClass): ActivityListProviderInterface
    {
        foreach ($this->activityClasses as $activityClass) {
            $aclClass = $this->activityAclClasses[$activityClass] ?? $activityClass;
            if ($aclClass === $activityOwnerClass) {
                return $this->getProviderByClass($activityClass);
            }
        }

        throw $this->createProviderNotFoundException($activityOwnerClass);
    }

    /**
     * Gets an activity list provider for the given activity owner entity.
     *
     * @param object $activityOwnerEntity
     *
     * @return ActivityListProviderInterface
     *
     * @throws \LogicException if a provider for the given owner entity does not exist
     */
    public function getProviderForOwnerEntity($activityOwnerEntity): ActivityListProviderInterface
    {
        return $this->getProviderByOwnerClass($this->doctrineHelper->getEntityClass($activityOwnerEntity));
    }

    /**
     * @param object                        $entity
     * @param ActivityListProviderInterface $provider
     * @param string                        $verb
     * @param ActivityList|null             $list
     *
     * @return ActivityList|null
     */
    private function getActivityListEntityForEntity(
        $entity,
        ActivityListProviderInterface $provider,
        string $verb = ActivityList::VERB_CREATE,
        ActivityList $list = null
    ): ?ActivityList {
        if (!$provider->isApplicable($entity)) {
            return null;
        }

        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        if (ActivityList::VERB_CREATE === $verb && null === $entityId) {
            // return null if for some reason the activity entity have no id
            // to avoid crush during new activity list save
            return null;
        }

        if (null === $list) {
            $list = $this->activityListFactory->createActivityList();
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
            $list->setRelatedActivityId($entityId);
            $list->setOrganization($provider->getOrganization($entity));
        }

        $this->addActivityListTargets($entity, $provider, $list);

        return $list;
    }

    private function addActivityListTargets($entity, ActivityListProviderInterface $provider, ActivityList $list): void
    {
        $targets = $provider->getTargetEntities($entity);
        foreach ($targets as $target) {
            if ($list->supportActivityListTarget($this->doctrineHelper->getEntityClass($target))) {
                $list->addActivityListTarget($target);
            }
        }
    }

    /**
     * Sets CreatedAt and UpdatedAt fields for the given activity list.
     *
     * @param object                        $entity
     * @param ActivityListProviderInterface $provider
     * @param ActivityList                  $list
     */
    private function setDate($entity, ActivityListProviderInterface $provider, ActivityList $list): void
    {
        if ($provider instanceof ActivityListDateProviderInterface) {
            $createdAt = $provider->getCreatedAt($entity);
            if ($createdAt) {
                $list->setCreatedAt($createdAt);
            }
            $updatedAt = $provider->getUpdatedAt($entity);
            if ($updatedAt) {
                $list->setUpdatedAt($updatedAt);
            }
        }
    }

    private function createProviderNotFoundException(string $className): \LogicException
    {
        return new \LogicException(sprintf('An activity list provider for "%s" does not exist.', $className));
    }
}
