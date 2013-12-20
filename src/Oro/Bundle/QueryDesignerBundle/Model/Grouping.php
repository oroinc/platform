<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;

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
