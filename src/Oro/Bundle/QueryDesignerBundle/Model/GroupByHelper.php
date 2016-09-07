<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

class GroupByHelper
{
    /**
     * Get fields that must appear in GROUP BY.
     *
     * @param string|array $groupBy
     * @param array        $selects
     * @return array
     */
    public function getGroupByFields($groupBy, $selects)
    {
        $groupBy = $this->getPreparedGroupBy($groupBy);
        $fields = [];
        $hasAggregate = false;

        foreach ($selects as $select) {
            $select = trim((string)$select);
            $selectHasAggregate = $this->hasAggregate($select);
            $hasAggregate = $hasAggregate || $selectHasAggregate;
            // Do not add fields with aggregate functions
            if ($selectHasAggregate) {
                continue;
            }

            $field = $this->getFieldForGroupBy($select);
            if ($field) {
                $fields[] = $field;
            }
        }

        if ($hasAggregate) {
            $groupBy = array_merge($groupBy, array_diff($fields, $groupBy));
        }

        return array_unique($groupBy);
    }

    /**
     * Get GROUP BY statements as array of trimmed parts.
     *
     * @param string|array $groupBy
     * @return array
     */
    protected function getPreparedGroupBy($groupBy)
    {
        if (!is_array($groupBy)) {
            $groupBy = explode(',', $groupBy);
        }

        $result = [];
        foreach ($groupBy as $groupByPart) {
            $groupByPart = trim((string)$groupByPart);
            if ($groupByPart) {
                $result[] = $groupByPart;
            }
        }

        return $result;
    }

    /**
     * @param string $select
     * @return bool
     */
    protected function hasAggregate($select)
    {
        // subselect
        if (stripos($select, '(SELECT') === 0) {
            return false;
        }

        preg_match('/(MIN|MAX|AVG|COUNT|SUM|GROUP_CONCAT)\(/i', $select, $matches);

        return (bool)$matches;
    }

    /**
     * Search for field alias if applicable or field name to use in group by
     *
     * @param string $select
     * @return string|null
     */
    protected function getFieldForGroupBy($select)
    {
        preg_match('/([^\s]+)\s+as\s+(\w+)$/i', $select, $parts);
        if (!empty($parts[2])) {
            // Add alias
            return $parts[2];
        } elseif (!$parts && strpos($select, ' ') === false) {
            // Add field itself when there is no alias
            return $select;
        }

        return null;
    }
}
