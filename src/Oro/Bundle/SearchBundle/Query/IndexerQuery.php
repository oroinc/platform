<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\Expr\Expression;

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
    public function addWhere(Expression $expression, $type = self::WHERE_AND)
    {
        if (self::WHERE_AND === $type) {
            $this->query->getCriteria()->andWhere($expression);
        } elseif (self::WHERE_OR === $type) {
            $this->query->getCriteria()->orWhere($expression);
        }

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
