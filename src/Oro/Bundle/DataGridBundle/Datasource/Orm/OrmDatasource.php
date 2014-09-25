<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\QueryConverter\YamlConverter;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\Exception\DatasourceException;

class OrmDatasource implements DatasourceInterface
{
    const TYPE = 'orm';

    /** @var QueryBuilder */
    protected $qb;

    /**
     * @var array
     */
    protected $queryHints;

    /** @var EntityManager */
    protected $em;

    /** @var DatagridInterface */
    protected $datagrid;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    public function __construct(
        EntityManager $em,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
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
            $this->qb  = $converter->parse($queryConfig, $this->em->createQueryBuilder());

        } elseif (isset($config['entity']) and isset($config['repository_method'])) {
            $entity = $config['entity'];
            $method = $config['repository_method'];
            $repository = $this->em->getRepository($entity);
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
            $this->processQueryHints($config['hints']);
        }

        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $query = $this->qb->getQuery();

        $this->setQueryHints($query);

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
     * Parses 'hints' configuration and save it in $this->queryHints
     *
     * @param array $config
     */
    protected function processQueryHints(array $config)
    {
        if (!empty($config)) {
            $this->queryHints = [];
            foreach ($config as $hint) {
                if (is_array($hint)) {
                    $this->queryHints[$hint['name']] = isset($hint['value']) ? $hint['value'] : true;
                } elseif (is_string($hint)) {
                    $this->queryHints[$hint] = true;
                }
            }
        }
    }

    /**
     * Sets hints for result query
     *
     * @param Query $query
     */
    protected function setQueryHints(Query $query)
    {
        if (!empty($this->queryHints)) {
            foreach ($this->queryHints as $name => $value) {
                if (defined("Doctrine\\ORM\\Query::$name")) {
                    $name = constant("Doctrine\\ORM\\Query::$name");
                }
                $query->setHint($name, $value);
            }
        }
    }
}
