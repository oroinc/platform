<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

class EmailGridListener
{
    /**
     * @var EmailQueryFactory
     */
    protected $factory;

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
        $queryBuilder = $ormDataSource->getQueryBuilder();
        $parameters = $event->getDatagrid()->getParameters();

        $this->factory->applyAcl($queryBuilder);

        if ($parameters->has('emailIds')) {
            $emailIds = $parameters->get('emailIds');
            if (!is_array($emailIds)) {
                $emailIds = explode(',', $emailIds);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->in('e.id', $emailIds));
        }

        $this->prepareQueryToFilter($parameters, $queryBuilder);
    }

    /**
     * Add join for query just if filter used. For performance optimization - BAP-10674
     *
     * @param ParameterBag $parameters
     * @param QueryBuilder $queryBuilder
     */
    protected function prepareQueryToFilter($parameters, $queryBuilder)
    {
        $filters = $parameters->get('_filter');
        if ($filters && array_key_exists('cc', $filters)) {
            $queryBuilder->leftJoin('e.recipients', 'r_cc', 'WITH', "r_cc.type = 'cc'");
        }
        if ($filters && array_key_exists('bcc', $filters)) {
            $queryBuilder->leftJoin('e.recipients', 'r_bcc', 'WITH', "r_bcc.type = 'bcc'");
        }
    }
}
