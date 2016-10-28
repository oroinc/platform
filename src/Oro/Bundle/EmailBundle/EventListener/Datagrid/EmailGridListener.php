<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Doctrine\ORM\Query\Expr\GroupBy;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailGridListener
{
    /**
     * @var EmailQueryFactory
     */
    protected $factory;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var GridViewManager
     */
    protected $gridViewManager;

    /**
     * Stores join's root and alias if joins for filters are added - ['eu' => ['alias1']]
     *
     * @var []
     */
    protected $filterJoins;

    /**
     * @param EmailQueryFactory $factory
     * @param SecurityFacade $securityFacade
     * @param GridViewManager $gridViewManager
     */
    public function __construct(
        EmailQueryFactory $factory,
        SecurityFacade $securityFacade,
        GridViewManager $gridViewManager
    ) {
        $this->factory = $factory;
        $this->securityFacade = $securityFacade;
        $this->gridViewManager = $gridViewManager;
    }

    /**
     * @param OrmResultBeforeQuery $event
     */
    public function onResultBeforeQuery(OrmResultBeforeQuery $event)
    {
        $qb = $event->getQueryBuilder();
        if ($this->filterJoins) {
            $this->removeJoinByRootAndAliases($qb, $this->filterJoins);
            $this->removeGroupByPart($qb, 'eu.id');
        }
    }

    /**
     * Add required filters
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $ormDataSource */
        $ormDataSource = $event->getDatagrid()->getDatasource();
        $queryBuilder  = $ormDataSource->getQueryBuilder();
        $countQb       = $ormDataSource->getCountQb();
        $parameters    = $event->getDatagrid()->getParameters();

        $this->factory->applyAcl($queryBuilder);
        if ($countQb) {
            $this->factory->applyAcl($countQb);
        }

        if ($parameters->has('emailIds')) {
            $emailIds = $parameters->get('emailIds');
            if (!is_array($emailIds)) {
                $emailIds = explode(',', $emailIds);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->in('e.id', $emailIds));
        }

        $this->prepareQueryToFilter($parameters, $queryBuilder, $countQb);
    }

    /**
     * Add joins and group by for query just if filter used. For performance optimization - BAP-10674
     *
     * @param ParameterBag $parameters
     * @param QueryBuilder $queryBuilder
     * @param QueryBuilder $countQb
     */
    protected function prepareQueryToFilter($parameters, QueryBuilder $queryBuilder, QueryBuilder $countQb = null)
    {
        $filters = $parameters->get('_filter');
        if (!$filters || !is_array($filters)) {
            $filters = $this->getGridViewFiltersData();
        }
        if (!$filters) {
            return;
        }
        $this->filterJoins = [];
        $groupByFilters = ['cc', 'bcc', 'to', 'folders', 'folder', 'mailbox'];

        // As now optimizer could not automatically remove joins for these filters
        // (they do not affect number of rows)
        // adding group by statement
        if (array_intersect_key($filters, array_flip($groupByFilters))) {
            // CountQb doesn't need group by statement cos it already added in grid config
            $queryBuilder->addGroupBy('eu.id');
        }
        $rFilters = [
            'cc'  => ['r_cc', 'WITH', 'r_cc.type = :ccType', ['ccType' => 'cc']],
            'bcc' => ['r_bcc', 'WITH', 'r_bcc.type = :bccType', ['bccType' => 'bcc']],
            'to'  => ['r_to', null, null, []],
        ];
        $rParams  = [];
        // Add join for each filter which is based on e.recipients table
        foreach ($rFilters as $rKey => $rFilter) {
            if (array_key_exists($rKey, $filters)) {
                $queryBuilder->leftJoin('e.recipients', $rFilter[0], $rFilter[1], $rFilter[2]);
                $countQb->leftJoin('e.recipients', $rFilter[0], $rFilter[1], $rFilter[2]);
                $rParams = array_merge($rParams, $rFilter[3]);
                $this->filterJoins['eu'][] = $rFilter[0];
            }
        }
        foreach ($rParams as $rParam => $rParamValue) {
            $queryBuilder->setParameter($rParam, $rParamValue);
            $countQb->setParameter($rParam, $rParamValue);
        }
        $fFilters = ['folder', 'folders', 'mailbox'];
        if (array_intersect_key($filters, array_flip($fFilters))) {
            $queryBuilder->leftJoin('eu.folders', 'f');
            $this->filterJoins['eu'][] = 'f';
        }
    }

    /**
     * @return array
     */
    protected function getGridViewFiltersData()
    {
        $filters = [];
        $user = $this->securityFacade->getLoggedUser();
        if (!$user) {
            return $filters;
        }
        /** @var GridView|null $gridView */
        $gridView = $this->gridViewManager->getDefaultView($user, 'user-email-grid');
        if (!$gridView) {
            return $filters;
        }

        return $gridView->getFiltersData();
    }

    /**
     *
     * @param QueryBuilder $qb
     * @param              $part
     */
    protected function removeGroupByPart(QueryBuilder $qb, $part)
    {
        $groupByParts = $qb->getDQLPart('groupBy');
        $qb->resetDQLPart('groupBy');
        /** @var GroupBy $groupByPart */
        foreach ($groupByParts as $i => $groupByPart) {
            $newGroupByPart = [];
            foreach ($groupByPart->getParts() as $j => $val) {
                if ($val !== $part) {
                    $newGroupByPart[] = $val;
                }
            }
            if ($newGroupByPart) {
                call_user_func_array([$qb, 'addGroupBy'], $newGroupByPart);
            }
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param array        $rootAndAliases ['root1' => ['aliasToRemove1', 'aliasToRemove2', ...], ...]
     */
    protected function removeJoinByRootAndAliases(QueryBuilder $qb, array $rootAndAliases)
    {
        $joins    = $qb->getDQLPart('join');

        /** @var Join $join */
        foreach ($joins as $root => $rJoins) {
            if (!empty($rootAndAliases[$root]) && is_array($rootAndAliases[$root])) {
                foreach ($rJoins as $key => $join) {
                    if (in_array($join->getAlias(), $rootAndAliases[$root], true)) {
                        unset($rJoins[$key]);
                    }
                }
            }
            $qb->add('join', [$root => $rJoins]);
        }
    }
}
