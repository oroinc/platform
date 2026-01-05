<?php

namespace Oro\Bundle\SearchBundle\Query;

use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * The base class for search queries.
 */
abstract class AbstractSearchQuery implements SearchQueryInterface
{
    public const WHERE_AND = 'and';
    public const WHERE_OR  = 'or';

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
    #[\Override]
    public function getResult()
    {
        if (!$this->result) {
            $this->result = $this->query();
        }

        return $this->result;
    }

    #[\Override]
    public function execute()
    {
        return $this->getResult()->getElements();
    }

    #[\Override]
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

    #[\Override]
    public function setFirstResult($firstResult)
    {
        $this->query->getCriteria()->setFirstResult($firstResult);

        return $this;
    }

    #[\Override]
    public function getFirstResult()
    {
        return $this->query->getCriteria()->getFirstResult();
    }

    #[\Override]
    public function setMaxResults($maxResults)
    {
        $this->query->getCriteria()->setMaxResults($maxResults);

        return $this;
    }

    #[\Override]
    public function getMaxResults()
    {
        return $this->query->getCriteria()->getMaxResults();
    }

    #[\Override]
    public function getTotalCount()
    {
        return $this->getResult()->getRecordsCount();
    }

    #[\Override]
    public function getSortBy()
    {
        $orderings = $this->query->getCriteria()->getOrderings();

        if (empty($orderings)) {
            return null;
        }

        $orders    = array_keys($orderings);
        $fieldName = array_pop($orders);

        return $fieldName === null ? null : Criteria::explodeFieldTypeName($fieldName)[1];
    }

    #[\Override]
    public function getSortOrder()
    {
        $orders = $this->query
            ->getCriteria()
            ->getOrderings();

        if (empty($orders)) {
            return null;
        }

        return array_pop($orders);
    }

    #[\Override]
    public function setOrderBy($fieldName, $direction = Query::ORDER_ASC, $type = Query::TYPE_TEXT)
    {
        if (\str_contains($fieldName, '.')) {
            $field = $fieldName;
        } else {
            $field = $type . '.' . $fieldName;
        }

        $this->query
            ->getCriteria()
            ->orderBy([$field => $direction]);

        return $this;
    }

    #[\Override]
    public function addOrderBy(
        string $fieldName,
        string $direction = Query::ORDER_ASC,
        string $type = Query::TYPE_TEXT,
        bool $prepend = false
    ): SearchQueryInterface {
        if (\str_contains($fieldName, '.')) {
            $field = $fieldName;
        } else {
            $field = $type . '.' . $fieldName;
        }

        $orders = $this->query->getCriteria()->getOrderings();
        if ($prepend) {
            $orders = [$field => $direction, ...$orders];
        } else {
            $orders[$field] = $direction;
        }

        $this->query
            ->getCriteria()
            ->orderBy($orders);

        return $this;
    }

    #[\Override]
    public function addSelect($fieldName, $enforcedFieldType = null)
    {
        $this->query->addSelect($fieldName, $enforcedFieldType);

        return $this;
    }

    #[\Override]
    public function getFrom()
    {
        $from = $this->query->getFrom();

        return false !== $from ? $from : null;
    }

    #[\Override]
    public function setFrom($entities)
    {
        $this->query->from($entities);

        return $this;
    }

    #[\Override]
    public function addWhere(Expression $expression, $type = self::WHERE_AND)
    {
        if (self::WHERE_AND === $type) {
            $this->query->getCriteria()->andWhere($expression);
        } elseif (self::WHERE_OR === $type) {
            $this->query->getCriteria()->orWhere($expression);
        }

        return $this;
    }

    #[\Override]
    public function getSelectAliases()
    {
        return $this->query->getSelectAliases();
    }

    #[\Override]
    public function getSelect()
    {
        return $this->query->getSelect();
    }

    #[\Override]
    public function getSelectDataFields()
    {
        return $this->query->getSelectDataFields();
    }

    #[\Override]
    public function getCriteria()
    {
        return $this->query->getCriteria();
    }

    #[\Override]
    public function addAggregate($name, $field, $function, array $parameters = [])
    {
        $this->query->addAggregate($name, $field, $function, $parameters);

        return $this;
    }

    #[\Override]
    public function getAggregations()
    {
        return $this->query->getAggregations();
    }

    #[\Override]
    public function setHint(string $name, $value): self
    {
        $this->query->setHint($name, $value);

        return $this;
    }

    #[\Override]
    public function getHint(string $name)
    {
        return $this->query->getHint($name);
    }

    #[\Override]
    public function hasHint(string $name): bool
    {
        return $this->query->hasHint($name);
    }

    #[\Override]
    public function getHints(): array
    {
        return $this->query->getHints();
    }

    public function __clone()
    {
        $this->query = clone $this->query;
        $this->result = null;
    }
}
