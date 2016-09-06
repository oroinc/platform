<?php

namespace Oro\Bundle\EmailBundle\EventListener\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\EmailBundle\Datagrid\EmailQueryFactory;
use Oro\Component\DoctrineUtils\ORM\QueryUtils;

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
     * @param OrmResultBeforeQuery $event
     */
    public function onResultBeforeQuery(OrmResultBeforeQuery $event)
    {
        $this->qb = $event->getQueryBuilder();

        $selectParts = $this->qb->getDQLPart('select');
        $stringSelectParts = [];
        foreach ($selectParts as $selectPart) {
            $stringSelectParts[] = (string) $selectPart;
        }
        $this->select = implode(', ', $stringSelectParts);

        $this->qb->select('eu.id');
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $originalRecords = $event->getRecords();
        if (!$originalRecords) {
            return;
        }

        $ids = [];
        foreach ($originalRecords as $record) {
            $ids[] = $record->getValue('id');
        }

        $this->qb
            ->select($this->select)
            ->resetDQLPart('groupBy')
            ->where($this->qb->expr()->in('eu.id', ':ids'))
            ->setMaxResults(null)
            ->setFirstResult(null)
            ->setParameter('ids', $ids);
        QueryUtils::removeUnusedParameters($this->qb);
        $result = $this->qb
            ->getQuery()
            ->getResult();

        $records = [];
        foreach ($result as $row) {
            $records[] = new ResultRecord($row);
        }
        $event->setRecords($records);
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
