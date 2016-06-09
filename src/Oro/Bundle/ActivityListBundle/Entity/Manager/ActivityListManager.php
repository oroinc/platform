<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\ActivityListBundle\Event\ActivityListPreQueryBuildEvent;
use Oro\Bundle\ActivityListBundle\Helper\ActivityInheritanceTargetsHelper;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityListBundle\Model\ActivityListGroupProviderInterface;
use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\CommentBundle\Entity\Manager\CommentApiManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ActivityListBundle\Helper\ActivityListAclCriteriaHelper;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;

class ActivityListManager
{
    /** @var Pager */
    protected $pager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var ConfigManager */
    protected $config;

    /** @var ActivityListChainProvider */
    protected $chainProvider;

    /** @var ActivityListFilterHelper */
    protected $activityListFilterHelper;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActivityListAclCriteriaHelper */
    protected $activityListAclHelper;

    /** @var ActivityInheritanceTargetsHelper */
    protected $activityInheritanceTargetsHelper;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param SecurityFacade                $securityFacade
     * @param EntityNameResolver            $entityNameResolver
     * @param Pager                         $pager
     * @param ConfigManager                 $config
     * @param ActivityListChainProvider     $provider
     * @param ActivityListFilterHelper      $activityListFilterHelper
     * @param CommentApiManager             $commentManager
     * @param DoctrineHelper                $doctrineHelper
     * @param ActivityListAclCriteriaHelper $aclHelper
     * @param ActivityInheritanceTargetsHelper $activityInheritanceTargetsHelper
     * @param EventDispatcherInterface      $eventDispatcher
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SecurityFacade $securityFacade,
        EntityNameResolver $entityNameResolver,
        Pager $pager,
        ConfigManager $config,
        ActivityListChainProvider $provider,
        ActivityListFilterHelper $activityListFilterHelper,
        CommentApiManager $commentManager,
        DoctrineHelper $doctrineHelper,
        ActivityListAclCriteriaHelper $aclHelper,
        ActivityInheritanceTargetsHelper $activityInheritanceTargetsHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->securityFacade           = $securityFacade;
        $this->entityNameResolver       = $entityNameResolver;
        $this->pager                    = $pager;
        $this->config                   = $config;
        $this->chainProvider            = $provider;
        $this->activityListFilterHelper = $activityListFilterHelper;
        $this->commentManager           = $commentManager;
        $this->doctrineHelper           = $doctrineHelper;
        $this->activityListAclHelper    = $aclHelper;
        $this->activityInheritanceTargetsHelper = $activityInheritanceTargetsHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return ActivityListRepository
     */
    public function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository(ActivityList::ENTITY_NAME);
    }

    /**
     * @param string  $entityClass
     * @param integer $entityId
     * @param array   $filter
     * @param integer $page
     *
     * @return ActivityList[]
     */
    public function getList($entityClass, $entityId, $filter, $page)
    {
        return $this->getListData($entityClass, $entityId, $filter, $page)['data'];
    }

    /**
     * @param string  $entityClass
     * @param integer $entityId
     * @param array   $filter
     * @param integer $page
     *
     * @return array ('data' => [], 'count' => int)
     */
    public function getListData($entityClass, $entityId, $filter, $page)
    {
        $qb = $this->prepareQB($entityClass, $entityId, $filter);

        $pager = clone $this->pager;
        $pager->setQueryBuilder($qb);
        $pager->setPage($page);
        $pager->setMaxPerPage($this->config->get('oro_activity_list.per_page'));
        $pager->setSkipAclCheck(true);
        $pager->init();

        $targetEntityData = [
            'class' => $entityClass,
            'id'    => $entityId,
        ];

        return [
            'count' => $pager->getNbResults(),
            'data' => $this->getEntityViewModels($pager->getResults(), $targetEntityData),
        ];
    }

    /**
     * @param string  $entityClass
     * @param integer $entityId
     * @param array   $filter
     *
     * @return int
     */
    public function getListCount($entityClass, $entityId, $filter)
    {
        $qb = $this->prepareQB($entityClass, $entityId, $filter);
        return QueryCountCalculator::calculateCount($qb->getQuery());
    }

    /**
     * @param integer $activityListItemId
     *
     * @return array
     */
    public function getItem($activityListItemId)
    {
        /** @var ActivityList $activityListItem */
        $activityListItem = $this->getRepository()->find($activityListItemId);

        return $activityListItem
            ? $this->getEntityViewModel($activityListItem)
            : null;
    }

    /**
     * @param ActivityList[] $entities
     * @param array          $targetEntityData
     *
     * @return array
     */
    public function getEntityViewModels($entities, $targetEntityData = [])
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->getEntityViewModel($entity, $targetEntityData);
        }

        return $result;
    }

    /**
     * @param ActivityList $entity
     * @param []           $targetEntityData
     *
     * @return array
     */
    public function getEntityViewModel(ActivityList $entity, $targetEntityData = [])
    {
        $entityProvider = $this->chainProvider->getProviderForEntity($entity->getRelatedActivityClass());
        $activity       = $this->doctrineHelper->getEntity(
            $entity->getRelatedActivityClass(),
            $entity->getRelatedActivityId()
        );

        $ownerName = '';
        $ownerId   = '';
        $owner     = $entity->getOwner();
        if ($owner) {
            $ownerName = $this->entityNameResolver->getName($owner);
            if ($this->securityFacade->isGranted('VIEW', $owner)) {
                $ownerId = $owner->getId();
            }
        }

        $editorName = '';
        $editorId   = '';
        $editor     = $entity->getEditor();
        if ($editor) {
            $editorName = $this->entityNameResolver->getName($editor);
            if ($this->securityFacade->isGranted('VIEW', $editor)) {
                $editorId = $editor->getId();
            }
        }

        $isHead                  = $this->getHeadStatus($entity, $entityProvider);
        $relatedActivityEntities = $this->getRelatedActivityEntities($entity, $entityProvider);
        $numberOfComments        = $this->commentManager->getCommentCount(
            $entity->getRelatedActivityClass(),
            $relatedActivityEntities
        );

        $data = $entityProvider->getData($entity);
        if (isset($data['isHead']) && !$data['isHead']) {
            $isHead = false;
        }

        $result = [
            'id'                   => $entity->getId(),
            'owner'                => $ownerName,
            'owner_id'             => $ownerId,
            'editor'               => $editorName,
            'editor_id'            => $editorId,
            'verb'                 => $entity->getVerb(),
            'subject'              => $entity->getSubject(),
            'description'          => $entity->getDescription(),
            'data'                 => $data,
            'relatedActivityClass' => $entity->getRelatedActivityClass(),
            'relatedActivityId'    => $entity->getRelatedActivityId(),
            'createdAt'            => $entity->getCreatedAt()->format('c'),
            'updatedAt'            => $entity->getUpdatedAt()->format('c'),
            'editable'             => $this->securityFacade->isGranted('EDIT', $activity),
            'removable'            => $this->securityFacade->isGranted('DELETE', $activity),
            'commentCount'         => $numberOfComments,
            'commentable'          => $this->commentManager->isCommentable(),
            'targetEntityData'     => $targetEntityData,
            'is_head'              => $isHead,
        ];

        return $result;
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     *
     * @return QueryBuilder
     */
    protected function getBaseQB($entityClass, $entityId)
    {
        $event = new ActivityListPreQueryBuildEvent($entityClass, $entityId);
        $this->eventDispatcher->dispatch(ActivityListPreQueryBuildEvent::EVENT_NAME, $event);
        $entityIds = $event->getTargetIds();
        return $this->getRepository()->getBaseActivityListQueryBuilder(
            $entityClass,
            $entityIds,
            $this->config->get('oro_activity_list.sorting_field'),
            $this->config->get('oro_activity_list.sorting_direction'),
            $this->config->get('oro_activity_list.grouping')
        );
    }

    /**
     * Get Grouped Entities by Activity Entity
     *
     * @param object $entity
     * @param string $widgetId
     * @param array  $filterMetadata
     *
     * @return array
     */
    public function getGroupedEntities($entity, $targetActivityClass, $targetActivityId, $widgetId, $filterMetadata)
    {
        $results        = [];
        $entityProvider = $this->chainProvider->getProviderForEntity(ClassUtils::getRealClass($entity));
        if ($this->isGroupingApplicable($entityProvider)) {
            $groupedActivities = $entityProvider->getGroupedEntities($entity);
            $activityResults   = $this->getEntityViewModels($groupedActivities, [
                'class' => $targetActivityClass,
                'id'    => $targetActivityId,
            ]);

            $results = [
                'entityId'            => $entity->getId(),
                'ignoreHead'          => true,
                'widgetId'            => $widgetId,
                'activityListData'    => json_encode(['count' => count($activityResults), 'data' => $activityResults]),
                'commentOptions'      => [
                    'listTemplate' => '#template-activity-item-comment',
                    'canCreate'    => true,
                ],
                'activityListOptions' => [
                    'configuration'            => $this->chainProvider->getActivityListOption($this->config),
                    'template'                 => '#template-activity-list',
                    'itemTemplate'             => '#template-activity-item',
                    'urls'                     => [],
                    'loadingContainerSelector' => '.activity-list.sub-list',
                    'dateRangeFilterMetadata'  => $filterMetadata,
                    'routes'                   => [],
                    'pager'                    => false,
                ],
            ];
        }

        return $results;
    }

    /**
     * @param object $entityProvider
     *
     * @return bool
     */
    protected function isGroupingApplicable($entityProvider)
    {
        return $entityProvider instanceof ActivityListGroupProviderInterface;
    }

    /**
     * @param ActivityList $entity
     * @param object       $entityProvider
     *
     * @return bool
     */
    protected function getHeadStatus(ActivityList $entity, $entityProvider)
    {
        return $this->isGroupingApplicable($entityProvider) && $entity->isHead();
    }

    /**
     * @param ActivityList $entity
     * @param object       $entityProvider
     *
     * @return array
     */
    protected function getRelatedActivityEntities(ActivityList $entity, $entityProvider)
    {
        $relatedActivityEntities = [$entity];
        if ($this->isGroupingApplicable($entityProvider)) {
            $relationEntity = $this->doctrineHelper->getEntity(
                $entity->getRelatedActivityClass(),
                $entity->getRelatedActivityId()
            );
            $relatedActivityEntities = $entityProvider->getGroupedEntities($relationEntity);
            if (count($relatedActivityEntities) === 0) {
                $relatedActivityEntities = [$entity];
            }
        }

        return $relatedActivityEntities;
    }

    /**
     * @param string $entityClass
     * @param int $entityId
     * @param array $filter
     *
     * @return QueryBuilder
     */
    protected function prepareQB($entityClass, $entityId, $filter)
    {
        $qb = $this->getBaseQB($entityClass, $entityId);
        $this->activityInheritanceTargetsHelper
            ->applyInheritanceActivity($qb, $entityClass, $entityId);
        if ($this->config->get('oro_activity_list.grouping')) {
            $qb->andWhere($qb->expr()->andX('activity.head = true'));
        }
        $this->activityListFilterHelper->addFiltersToQuery($qb, $filter);
        $this->activityListAclHelper->applyAclCriteria($qb, $this->chainProvider->getProviders());
        return $qb;
    }

    /**
     * This method should be used for fast changing data in 'relation' tables, because
     * it uses Plain SQL for updating data in tables.
     * Currently there is no another way for updating big amount of data: with Doctrine way
     * it takes a lot of time(because of big amount of operations with objects, event listeners etc.);
     * with DQL currently it impossible to build query, because DQL works only with entities, but
     * 'relation' tables are not entities. For example: there is 'relation'
     * table 'oro_rel_c3990ba6b28b6f38c460bc' and it has activitylist_id and account_id columns,
     * in fact to solve initial issue with big amount of data we need update only account_id column
     * with new values.
     *
     * @param array       $activityIds
     * @param string      $targetClass
     * @param integer     $oldTargetId
     * @param integer     $newTargetId
     * @param null|string $activityClass
     *
     * @return $this
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function replaceActivityTargetWithPlainQuery(
        array $activityIds,
        $targetClass,
        $oldTargetId,
        $newTargetId,
        $activityClass = null
    ) {
        if (is_null($activityClass)) {
            $associationName = $this->getActivityListAssociationName($targetClass);
            $entityClass = ActivityList::ENTITY_NAME;
        } else {
            $associationName = $this->getActivityAssociationName($targetClass);
            $entityClass = $activityClass;
        }

        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        if (!empty($activityIds) && $entityMetadata->hasAssociation($associationName)) {
            $association = $entityMetadata->getAssociationMapping($associationName);
            $tableName = $association['joinTable']['name'];
            $activityField = current(array_keys($association['relationToSourceKeyColumns']));
            $targetField = current(array_keys($association['relationToTargetKeyColumns']));

            $where = "WHERE $targetField = :sourceEntityId AND $activityField IN(" . implode(',', $activityIds) . ")";
            $dbConnection = $this->doctrineHelper
                ->getEntityManager(ActivityList::ENTITY_NAME)
                ->getConnection()
                ->prepare("UPDATE $tableName SET $targetField = :masterEntityId $where");

            $dbConnection->bindValue('masterEntityId', $newTargetId);
            $dbConnection->bindValue('sourceEntityId', $oldTargetId);
            $dbConnection->execute();
        }

        return $this;
    }

    /**
     * Get Activity List Association name
     *
     * @param string $className
     *
     * @return string
     */
    protected function getActivityListAssociationName($className)
    {
        return ExtendHelper::buildAssociationName(
            $className,
            ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
        );
    }

    /**
     * Get Activity Association name
     *
     * @param string $className
     *
     * @return string
     */
    protected function getActivityAssociationName($className)
    {
        return ExtendHelper::buildAssociationName(
            $className,
            ActivityScope::ASSOCIATION_KIND
        );
    }
}
