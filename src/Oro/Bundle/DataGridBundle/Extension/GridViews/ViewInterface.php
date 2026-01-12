<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

/**
 * Defines the contract for datagrid views.
 *
 * Datagrid views represent saved configurations of filters, sorters, and column settings that
 * users can create and apply to customize their datagrid display. This interface provides methods
 * for managing view data and associating views with specific datagrids.
 */
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
     * @return string
     */
    public function getName();

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

    /** @return string */
    public function getAppearanceTypeName();
}
