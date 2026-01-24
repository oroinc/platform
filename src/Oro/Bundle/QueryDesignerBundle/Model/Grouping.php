<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Represents grouping configuration for query results.
 *
 * This class manages the set of columns by which query results should be grouped.
 * Grouping is typically used in conjunction with aggregate functions to summarize
 * data across multiple records. The class maintains a collection of column names
 * that define the grouping criteria for the query.
 */
class Grouping
{
    /**
     * @var string[]
     */
    protected $columnNames;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->columnNames = new ArrayCollection();
    }

    /**
     * Get column names
     *
     * @return string
     */
    public function getColumnNames()
    {
        return $this->columnNames;
    }

    /**
     * Set column names
     *
     * @param string $columnNames
     */
    public function setColumnNames($columnNames)
    {
        $this->columnNames = $columnNames;
    }
}
