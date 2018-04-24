<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper;
use Oro\Bundle\ActivityListBundle\Helper\ActivityInheritanceTargetsHelper;
use Oro\Bundle\ActivityListBundle\Helper\ActivityListAclCriteriaHelper;
use Oro\Bundle\ActivityListBundle\Model\ActivityListGroupProviderInterface;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides a set of methods to get identifiers of activity list items to manage
 * a pagination and groupping of activity lists.
 */
class ActivityListIdProvider
{
    /** need to load more ids due to duplication and grouping possibility */
    private const PAGE_SIZE_MULTIPLIER = 3;

    /** @var ConfigManager */
    private $config;

    /** @var ActivityListChainProvider */
    private $chainProvider;

    /** @var ActivityListAclCriteriaHelper */
    private $activityListAclHelper;

    /** @var ActivityListFilterHelper */
    private $activityListFilterHelper;

    /** @var ActivityInheritanceTargetsHelper */
    private $activityInheritanceTargetsHelper;

    /**
     * @param ConfigManager                    $config
     * @param ActivityListChainProvider        $chainProvider
     * @param ActivityListAclCriteriaHelper    $activityListAclHelper
     * @param ActivityListFilterHelper         $activityListFilterHelper
     * @param ActivityInheritanceTargetsHelper $activityInheritanceTargetsHelper
     */
    public function __construct(
        ConfigManager $config,
        ActivityListChainProvider $chainProvider,
        ActivityListAclCriteriaHelper $activityListAclHelper,
        ActivityListFilterHelper $activityListFilterHelper,
        ActivityInheritanceTargetsHelper $activityInheritanceTargetsHelper
    ) {
        $this->config = $config;
        $this->chainProvider = $chainProvider;
        $this->activityListAclHelper = $activityListAclHelper;
        $this->activityListFilterHelper = $activityListFilterHelper;
        $this->activityInheritanceTargetsHelper = $activityInheritanceTargetsHelper;
    }

    /**
     * Gets identifiers of activity list items for the requested page and based on the current configuration.
     *
     * @param QueryBuilder $qb
     * @param string       $entityClass
     * @param int          $entityId
     * @param array        $filter
     * @param array        $pageFilter
     *
     * @return array
     */
    public function getActivityListIds(QueryBuilder $qb, $entityClass, $entityId, $filter, $pageFilter)
    {
        $pageSize = $this->config->get('oro_activity_list.per_page');
        $ids = $this->loadListDataIds($qb, $entityClass, $entityId, $filter, $pageFilter, $pageSize);
        $ids = array_unique(array_column($ids, 'id'));
        $ids = array_slice($ids, 0, $pageSize);

        return $ids;
    }

    /**
     * Gets identifiers of activity list items combined in a group.
     *
     * @param QueryBuilder $qb
     * @param string $entityClass
     * @param int    $entityId
     *
     * @return array
     */
    public function getGroupedActivityListIds(QueryBuilder $qb, $entityClass, $entityId)
    {
        $qb = clone $qb;
        $qb
            ->resetDQLParts(['select'])
            ->addSelect('activity.id');

        $getIdsQb = clone $qb;
        $getIdsQb
            ->leftJoin(
                QueryBuilderUtil::getField('activity', $this->getActivityListAssociationName($entityClass)),
                'associatedEntity'
            )
            ->andWhere('associatedEntity = :associatedEntityId')
            ->setParameter(':associatedEntityId', $entityId);

        $this->activityListAclHelper->applyAclCriteria($getIdsQb, $this->chainProvider->getProviders());

        $ids = array_merge(
            $getIdsQb->getQuery()->getArrayResult(),
            $this->getGroupedActivityListIdsForInheritances($qb, $entityClass, $entityId)
        );

        $ids = array_unique(array_column($ids, 'id'));

        return $ids;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $entityClass
     * @param int          $entityId
     * @param array        $filter
     * @param array        $pageFilter
     * @param int          $pageSize
     *
     * @return array
     */
    private function loadListDataIds(QueryBuilder $qb, $entityClass, $entityId, $filter, $pageFilter, $pageSize)
    {
        $orderBy = $this->config->get('oro_activity_list.sorting_field');
        $grouping = $this->config->get('oro_activity_list.grouping');
        $orderByPath = QueryBuilderUtil::getField('activity', $orderBy);

        $getIdsQb = clone $qb;
        $getIdsQb->setMaxResults($pageSize * self::PAGE_SIZE_MULTIPLIER);
        $getIdsQb->resetDQLParts(['select']);
        $getIdsQb->addSelect('activity.id', $orderByPath);
        if ($grouping) {
            $getIdsQb->addSelect('activity.relatedActivityClass, activity.relatedActivityId');
        }

        $this->applyPageFilter($getIdsQb, $pageFilter);

        $this->activityListFilterHelper->addFiltersToQuery($getIdsQb, $filter);
        $this->activityListAclHelper->applyAclCriteria($getIdsQb, $this->chainProvider->getProviders());

        $ids = array_merge(
            $getIdsQb->getQuery()->getArrayResult(),
            $this->getListDataIdsForInheritances($getIdsQb, $entityClass, $entityId, $filter, $pageFilter)
        );

        $this->sortListDataIds($ids, $pageFilter, $orderBy);

        $numberOfUnfilteredIds = count($ids);
        if ($grouping) {
            $ids = $this->filterGroupedIds($ids);
        }

        // check if the requested number of items is loaded, and if not, load more items
        $numberOfIds = count($ids);
        if ($numberOfIds > 0 && $numberOfIds < $pageSize && $numberOfUnfilteredIds > $pageSize) {
            $lastRow = $ids[$numberOfIds - 1];
            $offsetDate = $lastRow[$orderBy];
            if (null === $qb->getParameter('offsetDate')) {
                if ($this->isAscendingOrderForListData($pageFilter)) {
                    $qb->andWhere($qb->expr()->gt($orderByPath, ':offsetDate'));
                } else {
                    $qb->andWhere($qb->expr()->lt($orderByPath, ':offsetDate'));
                }
            }
            $qb->setParameter('offsetDate', $offsetDate);
            $rows = $this->loadListDataIds(
                $qb,
                $entityClass,
                $entityId,
                $filter,
                $pageFilter,
                $pageSize - $numberOfIds
            );
            if (!empty($rows)) {
                $existingIds = array_unique(array_column($ids, 'id'));
                foreach ($rows as $row) {
                    if (!in_array($row['id'], $existingIds)) {
                        $ids[] = $row;
                    }
                }
            }
        }

        return $ids;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $entityClass
     * @param int          $entityId
     * @param array        $filter
     * @param array        $pageFilter
     *
     * @return array
     */
    private function getListDataIdsForInheritances(QueryBuilder $qb, $entityClass, $entityId, $filter, $pageFilter)
    {
        $ids = [];

        // due to performance issue - perform separate data request per each inherited entity
        $inheritanceTargets = $this->activityInheritanceTargetsHelper->getInheritanceTargetsRelations($entityClass);
        foreach ($inheritanceTargets as $key => $inheritanceTarget) {
            $inheritanceQb = clone $qb;
            $inheritanceQb->resetDQLParts(['where', 'orderBy']);
            $inheritanceQb->setParameters([]);
            $inheritanceQb->setParameter(':entityId', $entityId);

            $this->applyPageFilter($inheritanceQb, $pageFilter);

            $this->activityInheritanceTargetsHelper->applyInheritanceActivity(
                $inheritanceQb,
                $inheritanceTarget,
                $key,
                ':entityId'
            );

            $this->activityListFilterHelper->addFiltersToQuery($inheritanceQb, $filter);
            $this->activityListAclHelper->applyAclCriteria($inheritanceQb, $this->chainProvider->getProviders());

            $ids = array_merge($ids, $inheritanceQb->getQuery()->getArrayResult());
        }

        return $ids;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $entityClass
     * @param int          $entityId
     *
     * @return array
     */
    private function getGroupedActivityListIdsForInheritances(QueryBuilder $qb, $entityClass, $entityId)
    {
        $ids = [];

        // due to performance issue - perform separate data request per each inherited entity
        $inheritanceTargets = $this->activityInheritanceTargetsHelper->getInheritanceTargetsRelations($entityClass);
        foreach ($inheritanceTargets as $key => $inheritanceTarget) {
            $inheritanceQb = clone $qb;
            $inheritanceQb->setParameter(':associatedEntityId', $entityId);
            $this->activityInheritanceTargetsHelper->applyInheritanceActivity(
                $inheritanceQb,
                $inheritanceTarget,
                $key,
                ':associatedEntityId'
            );

            $this->activityListAclHelper->applyAclCriteria($inheritanceQb, $this->chainProvider->getProviders());

            $ids = array_merge($ids, $inheritanceQb->getQuery()->getArrayResult());
        }

        return $ids;
    }

    /**
     * @param QueryBuilder $qb
     * @param array        $pageFilter
     */
    private function applyPageFilter(QueryBuilder $qb, $pageFilter)
    {
        $orderByPath = QueryBuilderUtil::getField('activity', $this->config->get('oro_activity_list.sorting_field'));

        $orderDirection = 'ASC';
        if (!$this->isAscendingOrderForListData($pageFilter)) {
            $orderDirection = 'DESC';
        }

        if (!empty($pageFilter['date']) && !empty($pageFilter['ids'])) {
            $dateFilter = new \DateTime($pageFilter['date'], new \DateTimeZone('UTC'));
            if ($this->isAscendingOrderForListData($pageFilter)) {
                $qb->andWhere($qb->expr()->gte($orderByPath, ':dateFilter'));
            } else {
                $qb->andWhere($qb->expr()->lte($orderByPath, ':dateFilter'));
            }
            $qb->setParameter(':dateFilter', $dateFilter->format('Y-m-d H:i:s'));
            $qb->andWhere($qb->expr()->notIn('activity.id', implode(',', $pageFilter['ids'])));
        }

        $qb->orderBy($orderByPath, $orderDirection);
    }

    /**
     * @param array $pageFilter
     *
     * @return bool
     */
    private function isAscendingOrderForListData($pageFilter)
    {
        $orderDirection = $this->config->get('oro_activity_list.sorting_direction');

        if (!array_key_exists('action', $pageFilter)) {
            return $orderDirection === 'ASC';
        }

        return
            ($orderDirection === 'ASC' && $pageFilter['action'] === 'next')
            || ($orderDirection === 'DESC' && $pageFilter['action'] === 'prev');
    }

    /**
     * @param array  $ids
     * @param array  $pageFilter
     * @param string $orderBy
     *
     * @return array
     */
    private function sortListDataIds(array $ids, $pageFilter, $orderBy)
    {
        if ($this->isAscendingOrderForListData($pageFilter)) {
            // ASC sorting
            usort($ids, function ($a, $b) use ($orderBy) {
                return $a[$orderBy]->getTimestamp() - $b[$orderBy]->getTimestamp();
            });
        } else {
            // DESC sorting
            usort($ids, function ($a, $b) use ($orderBy) {
                return $b[$orderBy]->getTimestamp() - $a[$orderBy]->getTimestamp();
            });
        }

        return $ids;
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    private function filterGroupedIds(array $ids)
    {
        foreach ($this->chainProvider->getProviders() as $entityProvider) {
            if ($entityProvider instanceof ActivityListGroupProviderInterface) {
                $ids = $entityProvider->collapseGroupedItems($ids);
            }
        }

        return $ids;
    }

    /**
     * @param string $targetEntityClass
     *
     * @return string
     */
    private function getActivityListAssociationName($targetEntityClass)
    {
        return ExtendHelper::buildAssociationName(
            $targetEntityClass,
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );
    }
}
