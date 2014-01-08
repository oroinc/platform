<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;

/**
 * Iterates query result with elements of ResultRecord type
 */
class IterableResult extends BufferedQueryResultIterator implements IterableResultInterface
{
    /**
     * @var QueryBuilder
     */
    private $source;

    /**
     * Current ResultRecord, populated from query result row
     *
     * @var mixed
     */
    private $current = null;

    /**
     * Constructor
     *
     * @param QueryBuilder $source
     */
    public function __construct(QueryBuilder $source)
    {
        parent::__construct($source);
        $this->source = $source;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        parent::next();

        $current = parent::current();
        $this->current = $current === null
            ? null
            : new ResultRecord($current);
    }

    /**
     * {@inheritDoc}
     */
    public function getSource()
    {
        return $this->source;
    }
}
