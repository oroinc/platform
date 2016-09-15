<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;

class EmailGridListener
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var EmailQueryFactory
     */
    protected $factory;

    /**
     * @var QueryBuilder|null
     */
    protected $qb;

    /**
     * @var string|null
     */
    protected $select;

    /**
     * @param EmailQueryFactory $factory
     */
    public function __construct(EmailQueryFactory $factory)
    {
        $this->factory = $factory;
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
     * Add join for query just if filter used. For performance optimization - BAP-10674
     *
     * @param ParameterBag $parameters
     * @param QueryBuilder $queryBuilder
     * @param QueryBuilder $countQb
     */
    protected function prepareQueryToFilter($parameters, QueryBuilder $queryBuilder, QueryBuilder $countQb = null)
    {
        $filters = $parameters->get('_filter');
        if (!$filters || !is_array($filters)) {
            return;
        }
        $groupByFilters = ['cc', 'bcc', 'to', 'folders', 'folder', 'mailbox'];
        // @TODO Remove this after removing joins
        // which affect rows and do not need will be implemented(folders, recipients)
        if (array_intersect_key($filters, array_flip($groupByFilters))) {
            // do not added to countQb cos it already added in grid config
            $queryBuilder->groupBy('eu.id');
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
            }
        }
        foreach ($rParams as $rParam => $rParamValue) {
            $queryBuilder->setParameter($rParam, $rParamValue);
            $countQb->setParameter($rParam, $rParamValue);
        }
        $fFilters = ['folder', 'folders', 'mailbox'];
        if (array_intersect_key($filters, array_flip($fFilters))) {
            $queryBuilder->leftJoin('eu.folders', 'f');
        }
    }
}
