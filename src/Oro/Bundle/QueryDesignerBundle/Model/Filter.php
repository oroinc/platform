<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

/**
 * Represents a filter condition in a query designer query.
 *
 * This class encapsulates a single filter condition that restricts the query results
 * based on a specific column and a criterion. Filters are combined with logical operators
 * (`AND`/`OR`) to form complex filter expressions that determine which records are included
 * in the query result set.
 */
class Filter
{
    /**
     * @var string
     */
    protected $columnName;

    /**
     * @var string
     */
    protected $criterion;

    /**
     * Get column name
     *
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * Set column name
     *
     * @param string $columnName
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;
    }

    /**
     * Get filter criterion
     *
     * @return string
     */
    public function getCriterion()
    {
        return $this->criterion;
    }

    /**
     * Set filter criterion
     *
     * @param string $criterion
     */
    public function setCriterion($criterion)
    {
        $this->criterion = $criterion;
    }
}
