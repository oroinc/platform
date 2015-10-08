<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolver;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderAwareInterface;
use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryConverter\YamlConverter;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\Exception\BadMethodCallException;
use Oro\Bundle\DataGridBundle\Exception\DatasourceException;

class OrmDatasource implements DatasourceInterface, ParameterBinderAwareInterface
{
    const TYPE = 'orm';

    /** @var QueryBuilder */
    protected $qb;

    /** @var array|null */
    protected $queryHints;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var DatagridInterface */
    protected $datagrid;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var ParameterBinderInterface */
    protected $parameterBinder;

    /** @var QueryHintResolver */
    protected $queryHintResolver;

    /**
     * @param ManagerRegistry          $doctrine
     * @param EventDispatcherInterface $eventDispatcher
     * @param ParameterBinderInterface $parameterBinder
     * @param QueryHintResolver        $queryHintResolver
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EventDispatcherInterface $eventDispatcher,
        ParameterBinderInterface $parameterBinder,
        QueryHintResolver $queryHintResolver
    ) {
        $this->doctrine          = $doctrine;
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

        if (isset($config['query'])) {
            $queryConfig = array_intersect_key($config, array_flip(['query']));
            $converter = new YamlConverter();
            $this->qb  = $converter->parse($queryConfig, $this->doctrine);

        } elseif (isset($config['entity']) && isset($config['repository_method'])) {
            $entity = $config['entity'];
            $method = $config['repository_method'];
            $repository = $this->doctrine->getRepository($entity);
            if (method_exists($repository, $method)) {
                $qb = $repository->$method();
                if ($qb instanceof QueryBuilder) {
                    $this->qb = $qb;
                } else {
                    throw new DatasourceException(
                        sprintf(
                            '%s::%s() must return an instance of Doctrine\ORM\QueryBuilder, %s given',
                            get_class($repository),
                            $method,
                            is_object($qb) ? get_class($qb) : gettype($qb)
                        )
                    );
                }
            } else {
                throw new DatasourceException(sprintf('%s has no method %s', get_class($repository), $method));
            }

        } else {
            throw new DatasourceException(get_class($this).' expects to be configured with query or repository method');
        }

        if (isset($config['hints'])) {
            $this->queryHints = $config['hints'];
        }

        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
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

        $event = new OrmResultAfter($this->datagrid, $rows);
        $this->eventDispatcher->dispatch(OrmResultAfter::NAME, $event);

        return $event->getRecords();
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
        $this->qb = clone $this->qb;
    }
}
