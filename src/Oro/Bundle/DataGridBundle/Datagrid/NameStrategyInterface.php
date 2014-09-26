<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

/**
 * Class is responsible for handling grid name string that could additionally contain scope of grid.
 */
interface NameStrategyInterface
{
    /**
     * String that delimites grid name and grid scope in full name
     *
     * @return string
     */
    public function getDelimiter();

    /**
     * Returns parsed grid name, without scope
     *
     * @param string $fullName
     * @return string
     */
    public function parseGridName($fullName);

    /**
     * Returns parsed grid scope, without grid name
     *
     * @param string $fullName
     * @return string
     */
    public function parseGridScope($fullName);

    /**
     * Combines grid name and grid scope to single string and returns it.
     *
     * @param string $name
     * @param string $scope
     * @return string
     */
    public function buildGridFullName($name, $scope);
}
