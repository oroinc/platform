<?php

namespace Oro\Bundle\SearchBundle\Query;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

abstract class AbstractSearchQuery implements SearchQueryInterface
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var Result
     */
    protected $result;

    /**
     * Performing an internal query() to the engine, without
     * data postprocessing etc.
     *
     * @return mixed
     */
    abstract protected function query();

    /**
     * Getting the results from the query() and caching them.
     *
     * @return mixed|Result
     */
    public function getResult()
    {
        if (!$this->result) {
            $this->result = $this->query();
        }

        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->getResult()->getElements();
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param Query $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function setFirstResult($firstResult)
    {
        $this->query->getCriteria()->setFirstResult($firstResult);
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstResult()
    {
        return $this->query->getCriteria()->getFirstResult();
    }

    /**
     * {@inheritdoc}
     */
    public function setMaxResults($maxResults)
    {
        $this->query->getCriteria()->setMaxResults($maxResults);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxResults()
    {
        return $this->query->getCriteria()->getMaxResults();
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount()
    {
        return $this->getResult()->getRecordsCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getSortBy()
    {
        $orders    = array_keys($this->query->getCriteria()->getOrderings());
        $fieldName = array_pop($orders);

        return $fieldName === null ? null : Criteria::explodeFieldTypeName($fieldName)[1];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return $this->query->getOrderDirection();
    }

    /**
     * {@inheritdoc}
     */
    public function setOrderBy($fieldName, $direction = Query::ORDER_ASC, $type = Query::TYPE_TEXT)
    {
        return $this->query->setOrderBy($fieldName, $direction, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function addSelect($fieldName, $enforcedFieldType = null)
    {
        return $this->query->addSelect($fieldName, $enforcedFieldType);
    }

    /**
     * {@inheritdoc}
     */
    public function from($entities)
    {
        return $this->query->from($entities);
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectAliases()
    {
        return $this->query->getSelectAliases();
    }
}
