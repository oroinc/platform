<?php

namespace Oro\Bundle\ActivityBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ActivityBundle\Event\ActivityEvent;
use Oro\Bundle\ActivityBundle\Event\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ActivityManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var ConfigProvider */
    protected $activityConfigProvider;

    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param EntityClassResolver $entityClassResolver
     * @param ConfigProvider      $activityConfigProvider
     * @param ConfigProvider      $entityConfigProvider
     * @param ConfigProvider      $extendConfigProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver,
        ConfigProvider $activityConfigProvider,
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->entityClassResolver    = $entityClassResolver;
        $this->activityConfigProvider = $activityConfigProvider;
        $this->entityConfigProvider   = $entityConfigProvider;
        $this->extendConfigProvider   = $extendConfigProvider;
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
     * Returns an array contains info about all activity associations for the given entity type
     *
     * @param string $entityClass
     *
     * @return array
     */
    public function getActivityAssociations($entityClass)
    {
        $result = [];

        $activityClassNames = $this->activityConfigProvider->getConfig($entityClass)->get('activities');
        foreach ($activityClassNames as $activityClassName) {
            if (!$this->isActivityAssociationEnabled($entityClass, $activityClassName)) {
                continue;
            }

            $entityConfig   = $this->entityConfigProvider->getConfig($activityClassName);
            $activityConfig = $this->activityConfigProvider->getConfig($activityClassName);

            $associationName = ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND);

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

        $activityClassNames = $this->activityConfigProvider->getConfig($entityClass)->get('activities');
        foreach ($activityClassNames as $activityClassName) {
            if (!$this->isActivityAssociationEnabled($entityClass, $activityClassName)) {
                continue;
            }

            $activityConfig = $this->activityConfigProvider->getConfig($activityClassName);
            $buttonWidget   = $activityConfig->get('action_button_widget');
            if (!empty($buttonWidget)) {
                $associationName = ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND);

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
     * @param string $entityClass
     * @param string $activityClassName
     *
     * @return bool
     */
    protected function isActivityAssociationEnabled($entityClass, $activityClassName)
    {
        $extendConfig = $this->extendConfigProvider->getConfig($activityClassName);
        $relations    = $extendConfig->get('relation', false, []);
        $relationKey  = ExtendHelper::buildRelationKey(
            $activityClassName,
            ExtendHelper::buildAssociationName($entityClass, ActivityScope::ASSOCIATION_KIND),
            RelationType::MANY_TO_MANY,
            $entityClass
        );

        return isset($relations[$relationKey]);
    }
}
