<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;

use Oro\Bundle\DataGridBundle\Datasource\Orm\Configs\ConfigProcessorInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderAwareInterface;
use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;

use Oro\Bundle\DataGridBundle\Exception\BadMethodCallException;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

class OrmDatasource implements DatasourceInterface, ParameterBinderAwareInterface
{
    const TYPE = 'orm';

    /** @var QueryBuilder */
    protected $qb;

    /** @var QueryBuilder */
    protected $countQb;

    /** @var array|null */
    protected $queryHints;

    /** @var ConfigProcessorInterface */
    protected $configProcessor;

    /** @var DatagridInterface */
    protected $datagrid;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var ParameterBinderInterface */
    protected $parameterBinder;

    /** @var QueryHintResolver */
    protected $queryHintResolver;

    /**
     * @param ConfigProcessorInterface $processor
     * @param EventDispatcherInterface $eventDispatcher
     * @param ParameterBinderInterface $parameterBinder
     * @param QueryHintResolver        $queryHintResolver
     */
    public function __construct(
        ConfigProcessorInterface $processor,
        EventDispatcherInterface $eventDispatcher,
        ParameterBinderInterface $parameterBinder,
        QueryHintResolver $queryHintResolver
    ) {
        $this->configProcessor   = $processor;
        $this->eventDispatcher   = $eventDispatcher;
        $this->parameterBinder   = $parameterBinder;
        $this->queryHintResolver = $queryHintResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $this->datagrid = $grid;
        $this->processConfigs($config);
        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $this->eventDispatcher->dispatch(
            OrmResultBeforeQuery::NAME,
            new OrmResultBeforeQuery($this->datagrid, $this->qb)
        );

        $query = $this->qb->getQuery();

        $this->queryHintResolver->resolveHints(
            $query,
            null !== $this->queryHints ? $this->queryHints : []
        );

        $event = new OrmResultBefore($this->datagrid, $query);
        $this->eventDispatcher->dispatch(OrmResultBefore::NAME, $event);

        $results = $event->getQuery()->execute();
        $rows    = [];
        foreach ($results as $result) {
            $rows[] = new ResultRecord($result);
        }
        $event = new OrmResultAfter($this->datagrid, $rows, $query);
        $this->eventDispatcher->dispatch(OrmResultAfter::NAME, $event);

        return $event->getRecords();
    }

    /**
     * Returns QueryBuilder for count query if it was set
     *
     * @return QueryBuilder|null
     */
    public function getCountQb()
    {
        return $this->countQb;
    }

    /**
     * Returns query builder
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->qb;
    }

    /**
     * Set QueryBuilder
     *
     * @param QueryBuilder $qb
     *
     * @return $this
     */
    public function setQueryBuilder(QueryBuilder $qb)
    {
        $this->qb = $qb;

        return $this;
    }

    /**
     * {@inheritdoc}
     *  @deprecated since 1.10.
     */
    public function getParameterBinder()
    {
        return $this->parameterBinder;
    }

    /**
     * {@inheritdoc}
     */
    public function bindParameters(array $datasourceToDatagridParameters, $append = true)
    {
        if (!$this->datagrid) {
            throw new BadMethodCallException('Method is not allowed when datasource is not processed.');
        }

        return $this->parameterBinder->bindParameters($this->datagrid, $datasourceToDatagridParameters, $append);
    }

    public function __clone()
    {
        $this->qb      = clone $this->qb;
        $this->countQb = $this->countQb ? clone $this->countQb : null;
    }

    /**
     * @param array $config
     */
    protected function processConfigs(array $config)
    {
        $this->qb        = $this->configProcessor->processQuery($config);
        $this->countQb   = $this->configProcessor->processCountQuery($config);
        if (isset($config['hints'])) {
            $this->queryHints = $config['hints'];
        }
    }
}
