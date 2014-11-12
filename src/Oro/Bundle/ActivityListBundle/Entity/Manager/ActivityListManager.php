<?php

namespace Oro\Bundle\ActivityListBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ConfigBundle\Config\UserConfigManager;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ActivityListManager
{
    const STATE_CREATE = 'create';
    const STATE_UPDATE = 'update';

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

    /**
     * @param Registry          $doctrine
     * @param SecurityFacade    $securityFacade
     * @param NameFormatter     $nameFormatter
     * @param Pager             $pager
     * @param UserConfigManager $config
     */
    public function __construct(
        Registry $doctrine,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter,
        Pager $pager,
        UserConfigManager $config,
        ActivityListChainProvider $provider
    ) {
        $this->em             = $doctrine->getManager();
        $this->securityFacade = $securityFacade;
        $this->nameFormatter  = $nameFormatter;
        $this->pager          = $pager;
        $this->config         = $config;
        $this->chainProvider  = $provider;
    }

    /**
     * @return ActivityListRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(ActivityList::ENTITY_NAME);
    }

    /**
     * @param string         $entityClass
     * @param integer        $entityId
     * @param array          $activityEntityСlasses
     * @param \DateTime|bool $dateFrom
     * @param \DateTime|bool $dateTo
     * @param integer        $page
     *
     * @return ActivityList[]
     */
    public function getList(
        $entityClass,
        $entityId,
        $activityEntityСlasses,
        $dateFrom,
        $dateTo,
        $page
    ) {
        $qb = $this->getRepository()->getActivityListQueryBuilder(
            $entityClass,
            $entityId,
            $activityEntityСlasses,
            $dateFrom,
            $dateTo,
            $this->config->get('oro_activity_list.sorting_field'),
            $this->config->get('oro_activity_list.sorting_direction')
        );

        $pager = $this->pager;
        $pager->setQueryBuilder($qb);
        $pager->setPage($page);
        $pager->setMaxPerPage($this->config->get('oro_activity_list.per_page'));
        $pager->init();

        /** @var ActivityList[] $result */
        $results = $pager->getResults();

        $results = $this->getEntityViewModels($results);

        return $results;
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

        return $this->getEntityViewModel($activityListItem);
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
        $result = [
            'id'                   => $entity->getId(),
            'owner'                => $entity->getOwner() ? $this->nameFormatter->format($entity->getOwner()) : '',
            'owner_id'             => $entity->getOwner() ? $entity->getOwner()->getId() : '',
            'owner_route'          => '',
            'editor'               => $entity->getEditor() ? $this->nameFormatter->format($entity->getEditor()) : '',
            'editor_id'            => $entity->getEditor() ? $entity->getEditor()->getId() : '',
            'editor_route'         => '',
            'verb'                 => $entity->getVerb(),
            'subject'              => $entity->getSubject(),
            'data'                 => $entityProvider->getDataForView($entity),
            'relatedActivityClass' => $entity->getRelatedActivityClass(),
            'relatedActivityId'    => $entity->getRelatedActivityId(),
            'createdAt'            => $entity->getCreatedAt()->format('c'),
            'updatedAt'            => $entity->getUpdatedAt()->format('c'),
            'editable'             => $this->securityFacade->isGranted('EDIT', $entity),
            'removable'            => $this->securityFacade->isGranted('DELETE', $entity),
        ];

        return $result;
    }
}
