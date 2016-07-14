<?php
namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

interface ViewInterface
{
    /**
     * @param array $filtersData
     * @return ViewInterface
     */
    public function setFiltersData(array $filtersData);

    /**
     * @param array $sortersData
     * @return ViewInterface
     */
    public function setSortersData(array $sortersData = []);

    /**
     * @param array $columnsData
     * @return ViewInterface
     */
    public function setColumnsData(array $columnsData = []);

    /**
     * @param string $gridName
     * @return ViewInterface
     */
    public function setGridName($gridName);

    /** @return array */
    public function getSortersData();

    /** @return array */
    public function getFiltersData();

    /** @return array */
    public function getColumnsData();

    /** @return string */
    public function getGridName();
}
