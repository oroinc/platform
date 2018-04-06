<?php

namespace Oro\Bundle\ActivityBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Event\ActivityEvent;
use Oro\Bundle\ActivityBundle\Event\Events;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ActivityManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var ConfigProvider */
    protected $activityConfigProvider;

    /** @var ConfigProvider */
    protected $groupingConfigProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var AssociationManager */
    protected $associationManager;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param EntityClassResolver $entityClassResolver
     * @param ConfigProvider      $activityConfigProvider
     * @param ConfigProvider      $groupingConfigProvider
     * @param ConfigProvider      $entityConfigProvider
     * @param ConfigProvider      $extendConfigProvider
     * @param AssociationManager  $associationManager
     * @param FeatureChecker      $featureChecker
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver,
        ConfigProvider $activityConfigProvider,
        ConfigProvider $groupingConfigProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        AssociationManager $associationManager,
        FeatureChecker $featureChecker
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->entityClassResolver    = $entityClassResolver;
        $this->activityConfigProvider = $activityConfigProvider;
        $this->groupingConfigProvider = $groupingConfigProvider;
        $this->entityConfigProvider   = $entityConfigProvider;
        $this->extendConfigProvider   = $extendConfigProvider;
        $this->associationManager     = $associationManager;
        $this->featureChecker         = $featureChecker;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Indicates whether the given entity type has any activity associations or not
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function hasActivityAssociations($entityClass)
    {
        if (!$this->activityConfigProvider->hasConfig($entityClass)) {
            return false;
        }

        $activityClassNames = $this->activityConfigProvider->getConfig($entityClass)->get('activities');

        return !empty($activityClassNames);
    }

    /**
     * Indicates whether the given entity type can be associated with the given activity or not
     *
     * @param string $entityClass
     * @param string $activityEntityClass
     *
     * @return bool
     */
    public function hasActivityAssociation($entityClass, $activityEntityClass)
    {
        if (!$this->activityConfigProvider->hasConfig($entityClass)) {
            return false;
        }

        $activityClassNames = $this->activityConfigProvider->getConfig($entityClass)->get('activities');

        return !empty($activityClassNames) && in_array($activityEntityClass, $activityClassNames);
    }

    /**
     * Associates the given target entity with the activity entity
     * If the target entity has no association with the given activity entity it will be skipped
     *
     * @param ActivityInterface $activityEntity
     * @param object            $targetEntity
     *
     * @return bool TRUE if an association was added; otherwise, FALSE
     */
    public function addActivityTarget(ActivityInterface $activityEntity, $targetEntity)
    {
        if ($targetEntity !== null
            && $activityEntity->supportActivityTarget(get_class($targetEntity))
            && !$activityEntity->hasActivityTarget($targetEntity)
            && $this->featureChecker->isResourceEnabled(ClassUtils::getClass($targetEntity), 'entities')
        ) {
            $activityEntity->addActivityTarget($targetEntity);

            if ($this->eventDispatcher) {
                $event = new ActivityEvent($activityEntity, $targetEntity);
                $this->eventDispatcher->dispatch(Events::ADD_ACTIVITY, $event);
            }

            return true;
        }

        return false;
    }

    /**
     * Associates the given target entities with the activity entity
     * If some target entity has no association with the given activity entity it will be skipped
     *
     * @param ActivityInterface $activityEntity
     * @param object|object[]   $targetEntities
     *
     * @return bool TRUE if at least one association was added; otherwise, FALSE
     */
    public function addActivityTargets(ActivityInterface $activityEntity, array $targetEntities)
    {
        $hasChanges = false;

        foreach ($targetEntities as $targetEntity) {
            if ($this->addActivityTarget($activityEntity, $targetEntity)) {
                $hasChanges = true;
            }
        }

        return $hasChanges;
    }

    /**
     * Removes all activity entity associations and associates with the given target entities
     * If some target entity has no association with the given activity entity it will be skipped
     *
     * @param ActivityInterface $activityEntity
     * @param object|object[]   $targetEntities
     *
     * @return bool TRUE if at least one association was changed; otherwise, FALSE
     */
    public function setActivityTargets(ActivityInterface $activityEntity, array $targetEntities)
    {
        $hasChanges = false;

        $oldTargetEntities = $activityEntity->getActivityTargets();

        foreach ($oldTargetEntities as $oldTargetEntity) {
            if (!in_array($oldTargetEntity, $targetEntities, true)) {
                $this->removeActivityTarget($activityEntity, $oldTargetEntity);
                $hasChanges = true;
            }
        }

        if ($this->addActivityTargets($activityEntity, $targetEntities)) {
            $hasChanges = true;
        }

        return $hasChanges;
    }

    /**
     * Removes an association of the given target entity with the activity entity
     * If the target entity has no association with the given activity entity it will be skipped
     *
     * @param ActivityInterface $activityEntity
     * @param object            $targetEntity
     *
     * @return bool TRUE if an association was removed; otherwise, FALSE
     */
    public function removeActivityTarget(ActivityInterface $activityEntity, $targetEntity)
    {
        if ($targetEntity !== null
            && $activityEntity->supportActivityTarget(get_class($targetEntity))
            && $activityEntity->hasActivityTarget($targetEntity)
            && $this->featureChecker->isResourceEnabled(ClassUtils::getClass($targetEntity), 'entities')
        ) {
            $activityEntity->removeActivityTarget($targetEntity);
            if ($this->eventDispatcher) {
                $event = new ActivityEvent($activityEntity, $targetEntity);
                $this->eventDispatcher->dispatch(Events::REMOVE_ACTIVITY, $event);
            }

            return true;
        }

        return false;
    }

    /**
     * Removes an association of the given $oldTargetEntity and associates the given $newTargetEntity
     * with the activity entity
     * If some target entity has no association with the given activity entity it will be skipped
     *
     * @param ActivityInterface $activityEntity
     * @param object            $oldTargetEntity
     * @param object            $newTargetEntity
     *
     * @return bool TRUE if an association was removed; otherwise, FALSE
     */
    public function replaceActivityTarget(ActivityInterface $activityEntity, $oldTargetEntity, $newTargetEntity)
    {
        $hasChanges = false;

        if ($this->removeActivityTarget($activityEntity, $oldTargetEntity)) {
            $hasChanges = true;
        }
        if ($this->addActivityTarget($activityEntity, $newTargetEntity)) {
            $hasChanges = true;
        }

        return $hasChanges;
    }

    /**
     * Returns the list of FQCN of all activity entities
     *
     * @return string[]
     */
    public function getActivityTypes()
    {
        return array_values(
            array_map(
                function (ConfigInterface $config) {
                    return $config->getId()->getClassName();
                },
                $this->groupingConfigProvider->filter(
                    function (ConfigInterface $config) {
                        // filter activity entities
                        $groups = $config->get('groups');

                        return
                            !empty($groups)
                            && in_array(ActivityScope::GROUP_ACTIVITY, $groups, true);
                    }
                )
            )
        );
    }

    /**
     * Returns the list of fields responsible to store activity associations for the given activity entity type
     *
     * @param string $activityClassName The FQCN of the activity entity
     *
     * @return array [target_entity_class => field_name]
     */
    public function getActivityTargets($activityClassName)
    {
        return $this->associationManager->getAssociationTargets(
            $activityClassName,
            $this->associationManager->getMultiOwnerFilter('activity', 'activities'),
            RelationType::MANY_TO_MANY,
            ActivityScope::ASSOCIATION_KIND
        );
    }

    /**
     * Returns a query builder that could be used for fetching the list of entities
     * associated with the given activity
     *
     * @param string        $activityClassName The FQCN of the activity entity
     * @param mixed         $filters           Criteria is used to filter activity entities
     *                                         e.g. ['age' => 20, ...] or \Doctrine\Common\Collections\Criteria
     * @param array|null    $joins             Additional associations required to filter activity entities
     * @param int|null      $limit             The maximum number of items per page
     * @param int|null      $page              The page number
     * @param string|null   $orderBy           The ordering expression for the result
     * @param callable|null $callback          A callback function which can be used to modify child queries
     *                                         function (QueryBuilder $qb, $targetEntityClass)
     *
     * @return SqlQueryBuilder|null SqlQueryBuilder object or NULL if the given entity type has no activity associations
     */
    public function getActivityTargetsQueryBuilder(
        $activityClassName,
        $filters,
        $joins = null,
        $limit = null,
        $page = null,
        $orderBy = null,
        $callback = null
    ) {
        $targets = $this->getActivityTargets($activityClassName);
        if (empty($targets)) {
            return null;
        }

        return $this->associationManager->getMultiAssociationsQueryBuilder(
            $activityClassName,
            $filters,
            $joins,
            $targets,
            $limit,
            $page,
            $orderBy,
            $callback
        );
    }

    /**
     * Returns the list of fields responsible to store activity associations for the given target entity type
     *
     * @param string $targetClassName The FQCN of the target entity
     *
     * @return array [activity_entity_class => field_name]
     */
    public function getActivities($targetClassName)
    {
        $result = [];
        foreach ($this->getActivityTypes() as $activityClass) {
            $targets = $this->getActivityTargets($activityClass);
            if (isset($targets[$targetClassName])) {
                $result[$activityClass] = $targets[$targetClassName];
            }
        }

        return $result;
    }

    /**
     * Returns a query builder that could be used for fetching the list of activity entities
     * associated with the given target entity
     *
     * @param string        $targetClassName The FQCN of the activity entity
     * @param mixed         $filters         Criteria is used to filter activity entities
     *                                       e.g. ['age' => 20, ...] or \Doctrine\Common\Collections\Criteria
     * @param array|null    $joins           Additional associations required to filter activity entities
     * @param int|null      $limit           The maximum number of items per page
     * @param int|null      $page            The page number
     * @param string|null   $orderBy         The ordering expression for the result
     * @param callable|null $callback        A callback function which can be used to modify child queries
     *                                       function (QueryBuilder $qb, $ownerEntityClass)
     *
     * @return SqlQueryBuilder|null SqlQueryBuilder object or NULL if the given entity type has no activity associations
     */
    public function getActivitiesQueryBuilder(
        $targetClassName,
        $filters,
        $joins = null,
        $limit = null,
        $page = null,
        $orderBy = null,
        $callback = null
    ) {
        $activities = $this->getActivities($targetClassName);
        if (empty($activities)) {
            return null;
        }

        return $this->associationManager->getMultiAssociationOwnersQueryBuilder(
            $targetClassName,
            $filters,
            $joins,
            $activities,
            $limit,
            $page,
            $orderBy,
            $callback
        );
    }

    /**
     * Returns an array contains info about all activity associations for the given entity type
     *
     * @param string $entityClass
     *
     * @return array
     */
    public function getActivityAssociations($entityClass)
    {
        $result = [];

        $activityClassNames = $this->activityConfigProvider
            ->getConfig($entityClass)
            ->get('activities', false, []);
        foreach ($activityClassNames as $activityClassName) {
            $associationName = ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND);
            if (!$this->isActivityAssociationEnabled($activityClassName, $associationName)) {
                continue;
            }

            $entityConfig   = $this->entityConfigProvider->getConfig($activityClassName);
            $activityConfig = $this->activityConfigProvider->getConfig($activityClassName);

            $item = [
                'className'       => $activityClassName,
                'associationName' => $associationName,
                'label'           => $entityConfig->get('plural_label'),
                'route'           => $activityConfig->get('route')
            ];

            $priority = $activityConfig->get('priority');
            if (!empty($priority)) {
                $item['priority'] = $priority;
            }
            $acl = $activityConfig->get('acl');
            if (!empty($acl)) {
                $item['acl'] = $acl;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Returns an array contains info about all activity actions for the given entity type
     *
     * @param string $entityClass
     *
     * @return array
     */
    public function getActivityActions($entityClass)
    {
        $result = [];

        $activityClassNames = $this->activityConfigProvider
            ->getConfig($entityClass)
            ->get('activities', false, []);
        foreach ($activityClassNames as $activityClassName) {
            $associationName = ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND);
            if (!$this->isActivityAssociationEnabled($activityClassName, $associationName)) {
                continue;
            }

            $activityConfig = $this->activityConfigProvider->getConfig($activityClassName);
            $buttonWidget   = $activityConfig->get('action_button_widget');
            if (!empty($buttonWidget)) {
                $item = [
                    'className'       => $activityClassName,
                    'associationName' => $associationName,
                    'button_widget'   => $buttonWidget
                ];

                $linkWidget = $activityConfig->get('action_link_widget');
                if (!empty($linkWidget)) {
                    $item['link_widget'] = $linkWidget;
                }
                $priority = $activityConfig->get('priority');
                if (!empty($priority)) {
                    $item['priority'] = $priority;
                }

                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Adds filter by $entity DQL to the given query builder
     *
     * @param QueryBuilder $qb                  The query builder that is used to get the list of activities
     * @param string       $entityClass         The target entity class
     * @param mixed        $entityId            The target entity id
     * @param string|null  $activityEntityClass This parameter should be specified
     *                                          if the query has more than one root entity
     *
     * @throws \RuntimeException
     */
    public function addFilterByTargetEntity(
        QueryBuilder $qb,
        $entityClass,
        $entityId,
        $activityEntityClass = null
    ) {
        $activityEntityAlias = null;
        $rootEntities        = $qb->getRootEntities();
        if (empty($rootEntities)) {
            throw new \RuntimeException('The query must have at least one root entity.');
        }
        if (empty($activityEntityClass)) {
            if (count($rootEntities) > 1) {
                throw new \RuntimeException(
                    'The $activityEntityClass must be specified if the query has several root entities.'
                );
            }
            $activityEntityClass = $rootEntities[0];
            $activityEntityAlias = $qb->getRootAliases()[0];
        } else {
            $normalizedActivityEntityClass = ClassUtils::getRealClass(
                $this->entityClassResolver->getEntityClass($activityEntityClass)
            );
            foreach ($rootEntities as $i => $className) {
                $className = $this->entityClassResolver->getEntityClass($className);
                if ($className === $normalizedActivityEntityClass) {
                    $activityEntityAlias = $qb->getRootAliases()[$i];
                    break;
                }
            }
            if (empty($activityEntityAlias)) {
                throw new \RuntimeException(sprintf('The "%s" must be the root entity.', $activityEntityClass));
            }
        }
        $activityIdentifierFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($activityEntityClass);
        $targetIdentifierFieldName   = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);

        $filterQuery = $qb->getEntityManager()->createQueryBuilder()
            ->select(sprintf('filterActivityEntity.%s', $activityIdentifierFieldName))
            ->from($activityEntityClass, 'filterActivityEntity')
            ->innerJoin(
                sprintf(
                    'filterActivityEntity.%s',
                    ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND)
                ),
                'filterTargetEntity'
            )
            ->where(sprintf('filterTargetEntity.%s = :targetEntityId', $targetIdentifierFieldName))
            ->getQuery();

        $qb
            ->andWhere(
                $qb->expr()->in(
                    sprintf(
                        '%s.%s',
                        $activityEntityAlias,
                        $activityIdentifierFieldName
                    ),
                    $filterQuery->getDQL()
                )
            )
            ->setParameter('targetEntityId', $entityId);
    }

    /**
     * @param string $activityClassName
     * @param string $activityAssociationName
     *
     * @return bool
     */
    protected function isActivityAssociationEnabled($activityClassName, $activityAssociationName)
    {
        if (!$this->extendConfigProvider->hasConfig($activityClassName, $activityAssociationName)) {
            return false;
        }

        return ExtendHelper::isFieldAccessible(
            $this->extendConfigProvider->getConfig($activityClassName, $activityAssociationName)
        );
    }
}
