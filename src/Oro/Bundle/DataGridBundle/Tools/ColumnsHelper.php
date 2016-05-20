<?php

namespace Oro\Bundle\DataGridBundle\Tools;

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
     * Example $columns:
     * default: name1.contactName1.contactEmail1.contactPhone1.ownerName1.createdAt0.updatedAt1
     * modified: updatedAt1.name1.contactName1.contactEmail1.contactPhone1.ownerName0.createdAt1
     *
     * @param array        $columnsData
     * @param string|array $columns
     *
     * @return array $columnsData
     */
    public function prepareColumnsParam($columnsData, $columns)
    {
        if (is_array($columns)) {
            return $this->prepareNonMinifiedColumnsData($columns);
        }

        //For minified column params
        $columns = explode('.', $columns);
        foreach ($columnsData as $columnName => $columnData) {
            $newColumnData = $this->findColumnData($columns, $columnName);
            if (!empty($newColumnData)) {
                $columnsData[$columnName][ColumnsExtension::ORDER_FIELD_NAME]  = $newColumnData['order'];
                $columnsData[$columnName][ColumnsExtension::RENDER_FIELD_NAME] = $newColumnData['renderable'];
            }
        }

        return  $columnsData;
    }

    /**
     * @param array $columns
     *
     * @return array
     */
    public function buildColumnsOrder(array $columns = [])
    {
        $orders = [];

        $ignoreList = [];
        foreach ($columns as $name => $column) {
            if (array_key_exists(ColumnsExtension::ORDER_FIELD_NAME, $column)) {
                $orders[$name] = (int)$column[ColumnsExtension::ORDER_FIELD_NAME];
                $ignoreList[] = $orders[$name];
            } else {
                $orders[$name] = 0;
            }
        }

        $iteration  = 0;
        foreach ($orders as $name => &$order) {
            $iteration = $this->getFirstFreeOrder($iteration, $ignoreList);
            if (0 === $order) {
                $order = $iteration;
                $iteration++;
            } else {
                $ignoreList[] = $order;
            }
        }
        unset($order);

        return $orders;
    }

    /**
     * Reorder columns array by order for export
     *
     * @param array  $columns
     * @param string $columnsParams
     *
     * @return array
     */
    public function reorderColumns($columns, $columnsParams)
    {
        if ($columnsParams) {
            $columns = $this->prepareColumnsParam($columns, $columnsParams);
            $orders = [];
            foreach ($columns as $column) {
                $orders[] = isset($column['order']) ? $column['order'] : 0;
            }
            array_multisort($orders, $columns);
        }

        return $columns;
    }

    /**
     * Prepare non-minified saved grid views
     *
     * @param $columns
     *
     * @return array
     */
    protected function prepareNonMinifiedColumnsData($columns)
    {
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

    /**
     * Get new columns data from parsed URL columns params
     *
     * @param array $columns
     * @param string $name
     *
     * @return array
     */
    protected function findColumnData($columns, $name)
    {
        foreach ($columns as $key => $value) {
            $columnNameParam = substr($value, 0, -1);
            if ($columnNameParam === $name) {
                $render = (bool)((int)(substr($value, -1)));
                return [
                    ColumnsExtension::ORDER_FIELD_NAME => $key,
                    ColumnsExtension::RENDER_FIELD_NAME => $render,
                ];
            }
        }

        return [];
    }

    /**
     * Get first number which is not in ignore list
     *
     * @param int   $iteration
     * @param array $ignoreList
     *
     * @return int
     */
    protected function getFirstFreeOrder($iteration, array $ignoreList = [])
    {
        if (in_array($iteration, $ignoreList, true)) {
            ++$iteration;
            return $this->getFirstFreeOrder($iteration, $ignoreList);
        }

        return $iteration;
    }
}
