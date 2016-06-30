<?php
namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

interface ViewInterface
{
    /**
     * @param array $filtersData
     * @return mixed
     */
    public function setFiltersData(array $filtersData);

    /**
     * @param array $sortersData
     * @return mixed
     */
    public function setSortersData(array $sortersData = []);

    /**
     * @param array $columnsData
     * @return mixed
     */
    public function setColumnsData(array $columnsData = []);

    /** @return array */
    public function getSortersData();

    /** @return array */
    public function getFiltersData();

    /** @return array */
    public function getColumnsData();
}
