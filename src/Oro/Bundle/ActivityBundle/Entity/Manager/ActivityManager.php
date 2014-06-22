<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param EntityClassResolver $entityClassResolver
     * @param ConfigProvider      $activityConfigProvider
     * @param ConfigProvider      $entityConfigProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassResolver $entityClassResolver,
        ConfigProvider $activityConfigProvider,
        ConfigProvider $entityConfigProvider
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->entityClassResolver    = $entityClassResolver;
        $this->activityConfigProvider = $activityConfigProvider;
        $this->entityConfigProvider   = $entityConfigProvider;
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
            $entityConfig   = $this->entityConfigProvider->getConfig($activityClassName);
            $activityConfig = $this->activityConfigProvider->getConfig($activityClassName);

            $item = [
                'className'       => $activityClassName,
                'associationName' => ExtendHelper::buildAssociationName($entityClass),
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
            $activityConfig = $this->activityConfigProvider->getConfig($activityClassName);
            $widget         = $activityConfig->get('action_widget');
            if (!empty($widget)) {
                $item = [
                    'className'       => $activityClassName,
                    'associationName' => ExtendHelper::buildAssociationName($entityClass),
                    'widget'          => $widget
                ];

                $group = $activityConfig->get('action_group');
                if (!empty($group)) {
                    $item['group'] = $group;
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
     * @param QueryBuilder  $qb                  The query builder that is used to get the list of activities
     * @param object|string $entity              The target entity
     * @param string|null   $activityEntityClass This parameter should be specified
     *                                           if the query has more than one root entity
     * @throws \RuntimeException
     */
    public function addFilterByTargetEntity(
        QueryBuilder $qb,
        $entity,
        $activityEntityClass = null
    ) {
        $entityClass = ClassUtils::getClass($entity);
        $entityId    = $this->doctrineHelper->getSingleEntityIdentifier($entity);

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
                    ExtendHelper::buildAssociationName($entityClass)
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
}
