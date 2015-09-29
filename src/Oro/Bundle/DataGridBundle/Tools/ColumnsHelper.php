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
     *
     * @param array        $columnsData
     * @param string|array $columns
     *
     * Example $columns:
     * default: name1.contactName1.contactEmail1.contactPhone1.ownerName1.createdAt0.updatedAt1
     * modified: updatedAt1.name1.contactName1.contactEmail1.contactPhone1.ownerName0.createdAt1
     *
     * @return array $columnsData
     */
    public function prepareColumnsParam($columnsData, $columns)
    {
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
     * Get new columns data from parsed URL columns params
     *
     * @param array $columns
     * @param string $name
     *
     * @return array
     */
    protected function findColumnData($columns, $name)
    {
        $result = array();

        foreach ($columns as $key => $value) {
            $render = (bool)((int)(substr($value, -1)));
            $columnNameParam = substr($value, 0, -1);
            if ($columnNameParam === $name) {
                $result[ColumnsExtension::ORDER_FIELD_NAME]  = $key;
                $result[ColumnsExtension::RENDER_FIELD_NAME] = $render;
                return $result;
            }
        }

        return $result;
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
                array_push($ignoreList, $orders[$name]);
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
                array_push($ignoreList, $order);
            }
        }
        unset($order);

        return $orders;
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
