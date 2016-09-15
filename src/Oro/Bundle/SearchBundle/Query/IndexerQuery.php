<?php

namespace Oro\Bundle\SearchBundle\Query;

use Oro\Bundle\SearchBundle\Engine\Indexer;

class IndexerQuery extends AbstractSearchQuery
{
    /**
     * @var Indexer
     */
    protected $indexer;

    /**
     * @param Indexer $indexer
     * @param Query   $query
     */
    public function __construct(Indexer $indexer, Query $query)
    {
        $this->indexer = $indexer;
        $this->query   = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->query, $name), $args);
    }

    /**
     * {@inheritdoc}
     */
    public function setWhere($expression, $type = null)
    {
        if (self::WHERE_OR === $type) {
            $this->query->getCriteria()->orWhere($expression);

            return $this;
        }

        $this->query->getCriteria()->andWhere($expression);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function query()
    {
        return $this->indexer->query($this->query);
    }
}
