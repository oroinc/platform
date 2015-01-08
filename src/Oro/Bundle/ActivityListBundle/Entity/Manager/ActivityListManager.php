<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\CommentBundle\Entity\Manager\CommentApiManager;
use Oro\Bundle\ConfigBundle\Config\UserConfigManager;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ActivityListManager
{
    /** @var EntityManager */
    protected $em;

    /** @var Pager */
    protected $pager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var UserConfigManager */
    protected $config;

    /** @var ActivityListChainProvider */
    protected $chainProvider;

    /** @var ActivityListFilterHelper */
    protected $activityListFilterHelper;

    /**
     * @param Registry                  $doctrine
     * @param SecurityFacade            $securityFacade
     * @param NameFormatter             $nameFormatter
     * @param Pager                     $pager
     * @param UserConfigManager         $config
     * @param ActivityListChainProvider $provider
     * @param ActivityListFilterHelper  $activityListFilterHelper
     * @param CommentApiManager         $commentManager
     */
    public function __construct(
        Registry $doctrine,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter,
        Pager $pager,
        UserConfigManager $config,
        ActivityListChainProvider $provider,
        ActivityListFilterHelper $activityListFilterHelper,
        CommentApiManager $commentManager
    ) {
        $this->em                       = $doctrine->getManager();
        $this->securityFacade           = $securityFacade;
        $this->nameFormatter            = $nameFormatter;
        $this->pager                    = $pager;
        $this->config                   = $config;
        $this->chainProvider            = $provider;
        $this->activityListFilterHelper = $activityListFilterHelper;
        $this->commentManager           = $commentManager;
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

        $pager = $this->pager;
        $pager->setQueryBuilder($qb);
        $pager->setPage($page);
        $pager->setMaxPerPage($this->config->get('oro_activity_list.per_page'));
        $pager->init();

        return $this->getEntityViewModels($pager->getResults());
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

        $qb->select('COUNT(activity.id)');
        $qb->resetDQLPart('orderBy');

        $this->activityListFilterHelper->addFiltersToQuery($qb, $filter);

        return $qb->getQuery()->getSingleScalarResult();
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
     *
     * @return array
     */
    public function getEntityViewModels($entities)
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->getEntityViewModel($entity);
        }

        return $result;
    }

    /**
     * @param ActivityList $entity
     *
     * @return array
     */
    public function getEntityViewModel(ActivityList $entity)
    {
        $entityProvider = $this->chainProvider->getProviderForEntity($entity->getRelatedActivityClass());

        $ownerName = '';
        $ownerId   = '';
        if ($entity->getOwner()) {
            $ownerName = $this->nameFormatter->format($entity->getOwner());
            $ownerId   = $entity->getOwner()->getId();
        }

        $editorName = '';
        $editorId   = '';
        if ($entity->getEditor()) {
            $editorName = $this->nameFormatter->format($entity->getEditor());
            $editorId   = $entity->getEditor()->getId();
        }

        $numberOfComments = $this->commentManager->getCommentCount(
            $entity->getRelatedActivityClass(),
            $entity->getRelatedActivityId()
        );

        $result = [
            'id'                   => $entity->getId(),
            'owner'                => $ownerName,
            'owner_id'             => $ownerId,
            'editor'               => $editorName,
            'editor_id'            => $editorId,
            'verb'                 => $entity->getVerb(),
            'subject'              => $entity->getSubject(),
            'data'                 => $entityProvider->getData($entity),
            'relatedActivityClass' => $entity->getRelatedActivityClass(),
            'relatedActivityId'    => $entity->getRelatedActivityId(),
            'createdAt'            => $entity->getCreatedAt()->format('c'),
            'updatedAt'            => $entity->getUpdatedAt()->format('c'),
            'editable'             => $this->securityFacade->isGranted('EDIT', $entity),
            'removable'            => $this->securityFacade->isGranted('DELETE', $entity),
            'commentCount'         => $numberOfComments,
            'commentable'          => $this->commentManager->isCommentable(),
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
            $this->config->get('oro_activity_list.sorting_direction')
        );
    }
}
