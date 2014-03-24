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

class OrmDatasource implements DatasourceInterface
{
    const TYPE = 'orm';

    /** @var QueryBuilder */
    protected $qb;

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
                    throw new \Exception(
                        sprintf(
                            '%s::%s() must return an instance of Doctrine\ORM\QueryBuilder, %s given',
                            get_class($repository),
                            $method,
                            is_object($qb) ? get_class($qb) : gettype($qb)
                        )
                    );
                }
            } else {
                throw new \Exception(sprintf('%s has no method %s', get_class($repository), $method));
            }

        } else {
            throw new \Exception(get_class($this).' expects to be configured with query or repository method');
        }

        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $query = $this->qb->getQuery();

        $event = new OrmResultBefore($this->datagrid, $query);
        $this->eventDispatcher->dispatch(OrmResultBefore::NAME, $event);
        $this->eventDispatcher->dispatch(OrmResultBefore::NAME . '.' . $this->datagrid->getName(), $event);

        $results = $event->getQuery()->execute();
        $rows    = [];
        foreach ($results as $result) {
            $rows[] = new ResultRecord($result);
        }

        $event = new OrmResultAfter($this->datagrid, $rows);
        $this->eventDispatcher->dispatch(OrmResultAfter::NAME, $event);
        $this->eventDispatcher->dispatch(OrmResultAfter::NAME . '.' . $this->datagrid->getName(), $event);

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
}
