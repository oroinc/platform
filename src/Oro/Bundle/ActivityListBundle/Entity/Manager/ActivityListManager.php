<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Security\Core\Util\ClassUtils;

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

class ActivityListManager
{
    /** @var EntityManager */
    protected $em;

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

    /**
     * @param Registry                      $doctrine
     * @param SecurityFacade                $securityFacade
     * @param EntityNameResolver            $entityNameResolver
     * @param Pager                         $pager
     * @param ConfigManager                 $config
     * @param ActivityListChainProvider     $provider
     * @param ActivityListFilterHelper      $activityListFilterHelper
     * @param CommentApiManager             $commentManager
     * @param DoctrineHelper                $doctrineHelper
     * @param ActivityListAclCriteriaHelper $aclHelper
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Registry $doctrine,
        SecurityFacade $securityFacade,
        EntityNameResolver $entityNameResolver,
        Pager $pager,
        ConfigManager $config,
        ActivityListChainProvider $provider,
        ActivityListFilterHelper $activityListFilterHelper,
        CommentApiManager $commentManager,
        DoctrineHelper $doctrineHelper,
        ActivityListAclCriteriaHelper $aclHelper
    ) {
        $this->em                       = $doctrine->getManager();
        $this->securityFacade           = $securityFacade;
        $this->entityNameResolver       = $entityNameResolver;
        $this->pager                    = $pager;
        $this->config                   = $config;
        $this->chainProvider            = $provider;
        $this->activityListFilterHelper = $activityListFilterHelper;
        $this->commentManager           = $commentManager;
        $this->doctrineHelper           = $doctrineHelper;
        $this->activityListAclHelper    = $aclHelper;
    }

    /**
     * @return ActivityListRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(ActivityList::ENTITY_NAME);
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
        $qb = $this->getBaseQB($entityClass, $entityId);

        $this->activityListFilterHelper->addFiltersToQuery($qb, $filter);
        $this->activityListAclHelper->applyAclCriteria($qb, $this->chainProvider->getProviders());

        $pager = $this->pager;
        $pager->setQueryBuilder($qb);
        $pager->setPage($page);
        $pager->setMaxPerPage($this->config->get('oro_activity_list.per_page'));
        $pager->setSkipAclCheck(true);
        $pager->init();

        $targetEntityData = [
            'class' => $entityClass,
            'id'    => $entityId,
        ];

        return $this->getEntityViewModels($pager->getResults(), $targetEntityData);
    }

    /**
     * @param string  $entityClass
     * @param integer $entityId
     * @param array   $filter
     *
     * @return ActivityList[]
     */
    public function getListCount($entityClass, $entityId, $filter)
    {
        $qb = $this->getBaseQB($entityClass, $entityId);
        $this->activityListFilterHelper->addFiltersToQuery($qb, $filter);
        $this->activityListAclHelper->applyAclCriteria($qb, $this->chainProvider->getProviders());
        $qb->resetDQLPart('orderBy');

        $query             = $qb->getQuery();
        $parserResult      = QueryUtils::parseQuery($query);
        $parameterMappings = $parserResult->getParameterMappings();
        list($params, $types) = QueryUtils::processParameterMappings($query, $parameterMappings);
        $statement = $query->getEntityManager()->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM (' . $query->getSQL() . ') AS e',
            $params,
            $types
        );

        $result = $statement->fetchColumn();

        return $result;
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

        if ($activityListItem) {
            return $this->getEntityViewModel($activityListItem);
        }

        return null;
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
     * @param string $entityId
     *
     * @return QueryBuilder
     */
    protected function getBaseQB($entityClass, $entityId)
    {
        return $this->getRepository()->getBaseActivityListQueryBuilder(
            $entityClass,
            $entityId,
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
        $isHead = false;
        if ($this->isGroupingApplicable($entityProvider)) {
            $isHead = $entity->isHead();
        }

        return $isHead;
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
}
