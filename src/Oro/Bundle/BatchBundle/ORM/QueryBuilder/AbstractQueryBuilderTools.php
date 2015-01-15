<?php

namespace Oro\Bundle\BatchBundle\ORM\QueryBuilder;

abstract class AbstractQueryBuilderTools
{
    /** @var array */
    protected $fieldAliases = [];

    /** @var array */
    protected $joinTablePaths = [];

    /**
     * Get mapping of filed aliases to real field expressions.
     *
     * @param array $selects DQL parts
     * @return array
     */
    abstract public function prepareFieldAliases(array $selects);

    /**
     * Prepares an array of state passes by alias used in join WITH|ON condition
     *
     * @param array $joins
     */
    abstract public function prepareJoinTablePaths(array $joins);

    /**
     * @param array $selects
     * @param array  $joins
     */
    public function __construct(array $selects = null, array $joins = null)
    {
        if (null !== $selects) {
            $this->prepareFieldAliases($selects);
        }
        if (null !== $joins) {
            $this->prepareJoinTablePaths($joins);
        }
    }

    /**
     * Get field by alias.
     *
     * @param string $alias
     * @return null|string
     */
    public function getFieldByAlias($alias)
    {
        if (isset($this->fieldAliases[$alias])) {
            return $this->fieldAliases[$alias];
        }

        return null;
    }

    /**
     * Reset field aliases.
     */
    public function resetFieldAliases()
    {
        $this->fieldAliases = [];
    }

    /**
     * Get field aliases.
     *
     * @return array
     */
    public function getFieldAliases()
    {
        return $this->fieldAliases;
    }

    /**
     * Reset join table paths
     */
    public function resetJoinTablePaths()
    {
        $this->joinTablePaths = [];
    }

    /**
     * Get join table paths
     *
     * @return array
     */
    public function getJoinTablePaths()
    {
        return $this->joinTablePaths;
    }

    /**
     * Set join table paths.
     *
     * @param array $joinTablePaths
     */
    public function setJoinTablePaths(array $joinTablePaths)
    {
        $this->joinTablePaths = $joinTablePaths;
    }
}
