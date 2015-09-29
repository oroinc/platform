<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Columns\ColumnsExtension;

class ColumnsHelper
{
    /**
     * Check if data changed
     *
     * @param array $viewData
     * @param array $urlData
     *
     * @return bool
     */
    public function compareColumnsData($viewData, $urlData)
    {
        if (!is_array($viewData) || !is_array($urlData) || empty($viewData) || empty($urlData)) {
            return false;
        }

        $diff = array_diff_key($viewData, $urlData);
        if (!empty($diff)) {
            return false;
        }
        $diff = array_diff_key($urlData, $viewData);
        if (!empty($diff)) {
            return false;
        }

        foreach ($viewData as $columnName => $columnData) {
            if (!isset($urlData[$columnName])) {
                return false;
            }
            $diff = array_diff_assoc($viewData[$columnName], $urlData[$columnName]);
            if (!empty($diff)) {
                return false;
            }
            $diff = array_diff_assoc($urlData[$columnName], $viewData[$columnName]);
            if (!empty($diff)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get Columns State from ColumnsParam string
     *
     * @param DatagridConfiguration $config
     * @param string $columns like '51.11.21.30.40.61.71'
     *
     * @return array $columnsData
     */
    public function prepareColumnsParam(DatagridConfiguration $config, $columns)
    {
        $columnsData = $config->offsetGet(ColumnsExtension::COLUMNS_PATH);

        //For non-minified saved grid views
        if (is_array($columns)) {
            foreach ($columns as $key => $value) {
                if (isset($value[ColumnsExtension::ORDER_FIELD_NAME])) {
                    $order = (int)$columns[$key][ColumnsExtension::ORDER_FIELD_NAME];
                    $columns[$key][ColumnsExtension::ORDER_FIELD_NAME] = $order;
                }
                if (isset($value[ColumnsExtension::RENDER_FIELD_NAME])) {
                    $renderable = filter_var($value[ColumnsExtension::RENDER_FIELD_NAME], FILTER_VALIDATE_BOOLEAN);
                    $columns[$key][ColumnsExtension::RENDER_FIELD_NAME] = $renderable;
                }
            }
            return $columns;
        }

        //For minified column params
        $columns = explode('.', $columns);
        $index = 0;
        foreach ($columnsData as $columnName => $columnData) {
            $newColumnData = $this->findColumnData($index, $columns);
            if (!empty($newColumnData)) {
                $columnsData[$columnName][ColumnsExtension::ORDER_FIELD_NAME] = $newColumnData['order'];
                $columnsData[$columnName][ColumnsExtension::RENDER_FIELD_NAME] = $newColumnData['renderable'];
            }
            $index++;
        }

        return  $columnsData;
    }

    /**
     * Get new columns data from parsed URL columns params
     *
     * @param int $index
     * @param array $columns
     * @return array
     */
    protected function findColumnData($index, $columns)
    {
        $result = array();

        if (!isset($columns[$index])) {
            return $result;
        }

        foreach ($columns as $key => $value) {
            $render = (bool)((int)(substr($value, -1)));
            $columnNumber = (int)(substr($value, 0, -1));
            if ($index === $columnNumber) {
                $result[ColumnsExtension::ORDER_FIELD_NAME] = $key;
                $result[ColumnsExtension::RENDER_FIELD_NAME] = $render;
                return $result;
            }
        }

        return $result;
    }

    /**
     * Get first number which is not in ignore list
     *
     * @param int   $iteration
     * @param array $ignoreList
     *
     * @return int
     */
    public function getFirstFreeOrder($iteration, array $ignoreList = [])
    {
        if (in_array($iteration, $ignoreList, true)) {
            ++$iteration;
            return $this->getFirstFreeOrder($iteration, $ignoreList);
        }

        return $iteration;
    }
}
