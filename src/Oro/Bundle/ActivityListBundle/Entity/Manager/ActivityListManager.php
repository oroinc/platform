<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Manager;

use Doctrine\DBAL\Connection;
use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Event\ActivityListPreQueryBuildEvent;
use Oro\Bundle\ActivityListBundle\Model\ActivityListGroupProviderInterface;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListIdProvider;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\CommentBundle\Entity\Manager\CommentApiManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowDataHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * A set of methods to simplify retrieving activity list items.
 */
class ActivityListManager
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var ConfigManager */
    protected $config;

    /** @var ActivityListChainProvider */
    protected $chainProvider;

    /** @var ActivityListIdProvider */
    protected $activityListIdProvider;

    /** @var CommentApiManager */
    protected $commentManager;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var WorkflowDataHelper */
    protected $workflowHelper;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param EntityNameResolver            $entityNameResolver
     * @param ConfigManager                 $config
     * @param ActivityListChainProvider     $provider
     * @param ActivityListIdProvider        $activityListIdProvider
     * @param CommentApiManager             $commentManager
     * @param DoctrineHelper                $doctrineHelper
     * @param EventDispatcherInterface      $eventDispatcher
     * @param WorkflowDataHelper            $workflowHelper
     * @param HtmlTagHelper                 $htmlTagHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityNameResolver $entityNameResolver,
        ConfigManager $config,
        ActivityListChainProvider $provider,
        ActivityListIdProvider $activityListIdProvider,
        CommentApiManager $commentManager,
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher,
        WorkflowDataHelper $workflowHelper,
        HtmlTagHelper $htmlTagHelper
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityNameResolver = $entityNameResolver;
        $this->config = $config;
        $this->chainProvider = $provider;
        $this->activityListIdProvider = $activityListIdProvider;
        $this->commentManager = $commentManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->workflowHelper = $workflowHelper;
        $this->htmlTagHelper = $htmlTagHelper;
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
     * @param array   $pageFilter
     *
     * @return array ['data' => [], 'count' => int]
     */
    public function getListData($entityClass, $entityId, $filter, $pageFilter = [])
    {
        $result = [];

        $event = new ActivityListPreQueryBuildEvent($entityClass, $entityId);
        $this->eventDispatcher->dispatch(ActivityListPreQueryBuildEvent::EVENT_NAME, $event);
        $qb = $this->getRepository()->getBaseActivityListQueryBuilder(
            $entityClass,
            $event->getTargetIds()
        );

        $ids = $this->activityListIdProvider->getActivityListIds($qb, $entityClass, $entityId, $filter, $pageFilter);
        if (!empty($ids)) {
            $qb = $this->getRepository()->createQueryBuilder('activity')
                ->where($qb->expr()->in('activity.id', ':activitiesIds'))
                ->setParameter('activitiesIds', $ids)
                ->orderBy(
                    QueryBuilderUtil::getField('activity', $this->config->get('oro_activity_list.sorting_field')),
                    QueryBuilderUtil::getSortOrder($this->config->get('oro_activity_list.sorting_direction'))
                );

            $result = $qb->getQuery()->getResult();
        }

        $viewModels = $this->getEntityViewModels(
            $result,
            [
                'class' => $entityClass,
                'id'    => $entityId,
            ]
        );

        return [
            'count' => count($viewModels),
            'data' => $viewModels
        ];
    }

    /**
     * @param integer $activityListItemId
     *
     * @return array|null
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
            if ($viewModel = $this->getEntityViewModel($entity, $targetEntityData)) {
                $result[] = $viewModel;
            }
        }

        return $result;
    }

    /**
     * @param ActivityList $entity
     * @param array        $targetEntityData
     *
     * @return array|null
     */
    public function getEntityViewModel(ActivityList $entity, $targetEntityData = [])
    {
        $entityProvider = $this->chainProvider->getProviderForEntity($entity->getRelatedActivityClass());

        if ($entityProvider instanceof FeatureToggleableInterface && !$entityProvider->isFeaturesEnabled()) {
            return null;
        }

        $activity       = $this->doctrineHelper->getEntity(
            $entity->getRelatedActivityClass(),
            $entity->getRelatedActivityId()
        );

        $ownerName = '';
        $ownerId   = '';
        $owner     = $entity->getOwner();
        if ($owner) {
            $ownerName = $this->entityNameResolver->getName($owner);
            if ($this->authorizationChecker->isGranted('VIEW', $owner)) {
                $ownerId = $owner->getId();
            }
        }

        $editorName = '';
        $editorId   = '';
        $editor     = $entity->getUpdatedBy();
        if ($editor) {
            $editorName = $this->entityNameResolver->getName($editor);
            if ($this->authorizationChecker->isGranted('VIEW', $editor)) {
                $editorId = $editor->getId();
            }
        }

        $relatedActivityEntities = $this->getRelatedActivityEntities($entity, $entityProvider);
        $numberOfComments = $this->commentManager->getCommentCount(
            $entity->getRelatedActivityClass(),
            $relatedActivityEntities
        );
        $data = $entityProvider->getData($entity);

        $isHead = false;
        if (isset($data['isHead'])) {
            $isHead = $data['isHead'];
        }

        $workflowsData = $this->workflowHelper->getEntityWorkflowsData($activity);

        $result = [
            'id'                   => $entity->getId(),
            'owner'                => $ownerName,
            'owner_id'             => $ownerId,
            'editor'               => $editorName,
            'editor_id'            => $editorId,
            'verb'                 => $entity->getVerb(),
            'subject'              => $this->htmlTagHelper->purify($entity->getSubject()),
            'description'          => $this->htmlTagHelper->purify($entity->getDescription()),
            'data'                 => $data,
            'relatedActivityClass' => $entity->getRelatedActivityClass(),
            'relatedActivityId'    => $entity->getRelatedActivityId(),
            'createdAt'            => $entity->getCreatedAt()->format('c'),
            'updatedAt'            => $entity->getUpdatedAt()->format('c'),
            'editable'             => $this->authorizationChecker->isGranted('EDIT', $activity),
            'removable'            => $this->authorizationChecker->isGranted('DELETE', $activity),
            'commentCount'         => $numberOfComments,
            'commentable'          => $this->commentManager->isCommentable(),
            'workflowsData'        => $workflowsData,
            'targetEntityData'     => $targetEntityData,
            'is_head'              => $isHead,
            'routes'               => $entityProvider->getRoutes($activity)
        ];

        return $result;
    }

    /**
     * Get Grouped Entities by Activity Entity
     *
     * @param object $entity
     * @param string $targetActivityClass
     * @param int    $targetActivityId
     * @param string $widgetId
     * @param array  $filterMetadata
     *
     * @return array
     */
    public function getGroupedEntities($entity, $targetActivityClass, $targetActivityId, $widgetId, $filterMetadata)
    {
        $activityLists = [];
        $ids = $this->getGroupedActivityListIds($entity, $targetActivityClass, $targetActivityId);
        if (!empty($ids)) {
            $qb = $this->getRepository()->createQueryBuilder('activity');
            $qb = $qb
                ->where($qb->expr()->in('activity.id', ':activitiesIds'))
                ->setParameter('activitiesIds', $ids)
                ->orderBy(
                    QueryBuilderUtil::getField('activity', $this->config->get('oro_activity_list.sorting_field')),
                    QueryBuilderUtil::getSortOrder($this->config->get('oro_activity_list.sorting_direction'))
                );

            $activityLists = $qb->getQuery()->getResult();
        }

        $results = [];
        if (!empty($activityLists)) {
            $activityResults = $this->getEntityViewModels($activityLists, [
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
     * @param object[] $entities
     * @param object   $rootEntity
     * @param string   $targetActivityClass
     * @param int      $targetActivityId
     *
     * @return ActivityList[]
     */
    public function filterGroupedEntitiesByActivityLists(
        array $entities,
        $rootEntity,
        $targetActivityClass,
        $targetActivityId
    ) {
        $entityClass = ClassUtils::getRealClass($rootEntity);

        $activityLists = [];
        $ids = $this->getGroupedActivityListIds($rootEntity, $targetActivityClass, $targetActivityId);
        if (!empty($ids)) {
            $qb = $this->getRepository()->createQueryBuilder('activity');
            $qb = $qb
                ->where($qb->expr()->in('activity.id', ':activitiesIds'))
                ->setParameter('activitiesIds', $ids);

            $activityLists = $qb->getQuery()->getResult();
        }

        $entityIdsFromActivityLists = [];
        foreach ($activityLists as $activityList) {
            if ($activityList->getRelatedActivityClass() === $entityClass) {
                $entityIdsFromActivityLists[] = $activityList->getRelatedActivityId();
            }
        }
        $filteredEntities = [];
        foreach ($entities as $entity) {
            if (in_array($this->doctrineHelper->getSingleEntityIdentifier($entity), $entityIdsFromActivityLists)) {
                $filteredEntities[] = $entity;
            }
        }

        return $filteredEntities;
    }

    /**
     * @param object $entity
     * @param string $targetActivityClass
     * @param int    $targetActivityId
     *
     * @return array
     */
    private function getGroupedActivityListIds($entity, $targetActivityClass, $targetActivityId)
    {
        $entityClass = ClassUtils::getRealClass($entity);
        $event = new ActivityListPreQueryBuildEvent(
            $entityClass,
            $this->doctrineHelper->getSingleEntityIdentifier($entity)
        );
        $this->eventDispatcher->dispatch(ActivityListPreQueryBuildEvent::EVENT_NAME, $event);
        $entityIds = $event->getTargetIds();
        $qb = $this->getRepository()->createQueryBuilder('activity')
            ->leftJoin('activity.activityOwners', 'ao')
            ->where('activity.relatedActivityClass = :entityClass')
            ->setParameter('entityClass', $entityClass);
        if (count($entityIds) > 1) {
            $qb
                ->andWhere('activity.relatedActivityId IN (:entityIds)')
                ->setParameter('entityIds', $entityIds);
        } else {
            $qb
                ->andWhere('activity.relatedActivityId = :entityId')
                ->setParameter('entityId', reset($entityIds));
        }

        return $this->activityListIdProvider->getGroupedActivityListIds($qb, $targetActivityClass, $targetActivityId);
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
        if ($entityProvider instanceof ActivityListGroupProviderInterface) {
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
        if (empty($activityIds)) {
            return $this;
        }

        if (is_null($activityClass)) {
            $associationName = $this->getActivityListAssociationName($targetClass);
            $entityClass = ActivityList::ENTITY_NAME;
        } else {
            $associationName = $this->getActivityAssociationName($targetClass);
            $entityClass = $activityClass;
        }

        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        if ($entityMetadata->hasAssociation($associationName)) {
            $association = $entityMetadata->getAssociationMapping($associationName);
            $tableName = $association['joinTable']['name'];
            $activityField = current(array_keys($association['relationToSourceKeyColumns']));
            $targetField = current(array_keys($association['relationToTargetKeyColumns']));

            $dbConnection = $this->doctrineHelper
                ->getEntityManager(ActivityList::ENTITY_NAME)
                ->getConnection();

            // to avoid of duplication activity lists and activities items we need to clear these relations
            // from the master record before update
            $deleteQb = $dbConnection->createQueryBuilder();
            $deleteQb->delete($tableName)
                ->where($deleteQb->expr()->eq($targetField, ':masterEntityId'))
                ->andWhere($deleteQb->expr()->in($activityField, ':activityIds'))
                ->setParameter('masterEntityId', $newTargetId)
                ->setParameter('activityIds', $activityIds, Connection::PARAM_INT_ARRAY);
            $deleteQb->execute();

            $updateQb = $dbConnection->createQueryBuilder();
            $updateQb->update($tableName)
                ->set($targetField, ':masterEntityId')
                ->where($updateQb->expr()->eq($targetField, ':sourceEntityId'))
                ->andWhere($deleteQb->expr()->in($activityField, ':activityIds'))
                ->setParameter('masterEntityId', $newTargetId)
                ->setParameter('sourceEntityId', $oldTargetId)
                ->setParameter('activityIds', $activityIds, Connection::PARAM_INT_ARRAY);
            $updateQb->execute();
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
