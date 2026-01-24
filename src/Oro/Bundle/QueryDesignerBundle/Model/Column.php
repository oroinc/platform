<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

/**
 * Represents a column definition in a query designer query.
 *
 * This class encapsulates the configuration of a single column that will be
 * included in the query result set. It manages the column's name, display label,
 * sorting direction (`ASC`/`DESC`), and optional aggregation function. Columns are
 * the fundamental building blocks of query designer queries, defining which
 * fields from the data source should be selected and how they should be presented.
 */
class Column
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $sorting;

    /**
     * @var string
     */
    protected $func;

    /**
     * Get column name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set column name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get column label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set column label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get sorting mode.
     * Can be ASC, DESC or null.
     *
     * @return string|null
     */
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * Get sorting mode.
     * Can be ASC, DESC, empty string or null. The empty string or null are same and means no sorting.
     *
     * @param string|null $sorting
     */
    public function setSorting($sorting = null)
    {
        if ($sorting !== null && empty($sorting)) {
            $sorting = null;
        }
        $this->sorting = $sorting;
    }

    /**
     * Get function details.
     *
     * Note: getFunc name is used instead of getFunction due a problem with JS
     *
     * @return array|null
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * Get function details.
     *
     * Note: setFunc name is used instead of setFunction due a problem with JS
     *
     * @param array|null $func
     */
    public function setFunc($func = null)
    {
        if ($func !== null && empty($func)) {
            $func = null;
        }
        $this->func = $func;
    }
}
