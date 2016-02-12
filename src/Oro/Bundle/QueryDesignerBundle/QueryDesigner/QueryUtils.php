<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

class QueryUtils
{
    /**
     * Replaces all table aliases declared in the join expression part of a query with given aliases
     *
     * @param array $joins
     * @param array $aliases
     */
    public static function replaceTableAliasesInJoinAlias(&$joins, $aliases)
    {
        foreach ($joins as &$join) {
            $alias = $join['alias'];
            if (isset($aliases[$alias])) {
                $join['alias'] = $aliases[$alias];
            }
        }
    }

    /**
     * Replaces all table aliases declared in the join expression part of a query with given aliases
     *
     * @param array $joins
     * @param array $aliases
     */
    public static function replaceTableAliasesInJoinExpr(&$joins, $aliases)
    {
        // replace alias with {{newAlias}} - this is required to prevent collisions
        // between old and new aliases in case if some new alias has the same name as some old alias
        foreach ($joins as &$join) {
            $joinExpr = $join['join'];
            foreach ($aliases as $alias => $newAlias) {
                $tryFind = true;
                while ($tryFind) {
                    $tryFind = false;
                    $pos     = self::findTableAliasInJoinExpr($joinExpr, $alias);
                    if (false !== $pos) {
                        $joinExpr = sprintf(
                            '%s{{%s}}%s',
                            substr($joinExpr, 0, $pos),
                            $newAlias,
                            substr($joinExpr, $pos + strlen($alias))
                        );
                        $tryFind   = true;
                    }
                }
            }
            $join['join'] = $joinExpr;
        }
        // replace {{newAlias}} with newAlias
        foreach ($joins as &$join) {
            $joinExpr = $join['join'];
            foreach ($aliases as $newAlias) {
                $joinExpr = str_replace(sprintf('{{%s}}', $newAlias), $newAlias, $joinExpr);
            }
            $join['join'] = $joinExpr;
        }
    }

    /**
     * Replaces all table aliases declared in the join part of a query with given aliases
     *
     * @param array $joins
     * @param array $aliases
     */
    public static function replaceTableAliasesInJoinConditions(&$joins, $aliases)
    {
        // replace alias with {{newAlias}} - this is required to prevent collisions
        // between old and new aliases in case if some new alias has the same name as some old alias
        foreach ($joins as &$join) {
            if (isset($join['condition'])) {
                $condition = $join['condition'];
                foreach ($aliases as $alias => $newAlias) {
                    $tryFind = true;
                    while ($tryFind) {
                        $tryFind = false;
                        $pos     = self::findTableAliasInCondition($condition, $alias);
                        if (false !== $pos) {
                            $condition = sprintf(
                                '%s{{%s}}%s',
                                substr($condition, 0, $pos),
                                $newAlias,
                                substr($condition, $pos + strlen($alias))
                            );
                            $tryFind   = true;
                        }
                    }
                }
                $join['condition'] = $condition;
            }
        }
        // replace {{newAlias}} with newAlias
        foreach ($joins as &$join) {
            if (isset($join['condition'])) {
                $condition = $join['condition'];
                foreach ($aliases as $newAlias) {
                    $condition = str_replace(sprintf('{{%s}}', $newAlias), $newAlias, $condition);
                }
                $join['condition'] = $condition;
            }
        }
    }

    /**
     * Replaces all table aliases declared in the select part of a query with given aliases
     *
     * @param string $selectExpr
     * @param array  $aliases
     *
     * @return string The corrected select expression
     */
    public static function replaceTableAliasesInSelectExpr($selectExpr, $aliases)
    {
        // replace alias with {{newAlias}} - this is required to prevent collisions
        // between old and new aliases in case if some new alias has the same name as some old alias
        foreach ($aliases as $alias => $newAlias) {
            $tryFind = true;
            while ($tryFind) {
                $tryFind = false;
                $pos     = self::findTableAliasInSelect($selectExpr, $alias);
                if (false !== $pos) {
                    $selectExpr = sprintf(
                        '%s{{%s}}%s',
                        substr($selectExpr, 0, $pos),
                        $newAlias,
                        substr($selectExpr, $pos + strlen($alias))
                    );
                    $tryFind    = true;
                }
            }
        }
        // replace {{newAlias}} with newAlias
        foreach ($aliases as $newAlias) {
            $selectExpr = str_replace(sprintf('{{%s}}', $newAlias), $newAlias, $selectExpr);
        }

        return $selectExpr;
    }

    /**
     * Checks if $joinExpr contains the given table alias
     *
     * @param string $joinExpr
     * @param string $alias
     *
     * @return bool|int The position of $alias in $joinExpr or FALSE if it was not found
     */
    protected static function findTableAliasInJoinExpr($joinExpr, $alias)
    {
        $pos = strpos($joinExpr, $alias);
        while (false !== $pos) {
            if (0 === $pos) {
                $nextChar = substr($joinExpr, $pos + strlen($alias), 1);
                if ('.' === $nextChar) {
                    return $pos;
                }
            } elseif (strlen($joinExpr) !== $pos + strlen($alias) + 1) {
                $prevChar = substr($joinExpr, $pos - 1, 1);
                if (in_array($prevChar, [' ', '(', ','], true)) {
                    $nextChar = substr($joinExpr, $pos + strlen($alias), 1);
                    if ('.' === $nextChar) {
                        return $pos;
                    }
                }
            }
            $pos = strpos($joinExpr, $alias, $pos + strlen($alias));
        }

        return false;
    }

    /**
     * Checks if $condition contains the given table alias
     *
     * @param string $condition
     * @param string $alias
     * @param int    $offset
     *
     * @return bool|int The position of $alias in $condition or FALSE if it was not found
     */
    protected static function findTableAliasInCondition($condition, $alias, $offset = 0)
    {
        $pos = strpos($condition, $alias, $offset);
        if (false !== $pos) {
            if (0 === $pos) {
                // handle case "ALIAS.", "ALIAS.field"
                $nextChar = substr($condition, $pos + strlen($alias), 1);
                if (in_array($nextChar, ['.', ' ', '='], true)) {
                    return $pos;
                }

                // handle case "ALIASWord.entity = ALIAS"
                return self::findTableAliasInCondition($condition, $alias, ++$pos);
            } elseif (strlen($condition) === $pos + strlen($alias)) {
                // handle case "t2.someField = ALIAS"
                $prevChar = substr($condition, $pos - 1, 1);
                if (in_array($prevChar, [' ', '='], true)) {
                    return $pos;
                }
            } else {
                // handle case "t2.someField = ALIAS.id"
                $prevChar = substr($condition, $pos - 1, 1);
                if (in_array($prevChar, [' ', '=', '('], true)) {
                    $nextChar = substr($condition, $pos + strlen($alias), 1);
                    if (in_array($nextChar, ['.', ' ', '=', ')'], true)) {
                        return $pos;
                    }
                }

                // handle case "t2.ALIAS = ALIAS AND"
                return self::findTableAliasInCondition($condition, $alias, ++$pos);
            }
        }

        return false;
    }

    /**
     * Checks if $selectExpr contains the given table alias
     *
     * @param string $selectExpr
     * @param string $alias
     *
     * @return bool|int The position of $alias in selectExpr or FALSE if it was not found
     */
    protected static function findTableAliasInSelect($selectExpr, $alias)
    {
        $pos = strpos($selectExpr, $alias);
        while (false !== $pos) {
            if (0 === $pos) {
                $nextChar = substr($selectExpr, $pos + strlen($alias), 1);
                if ('.' === $nextChar) {
                    return $pos;
                }
            } elseif (strlen($selectExpr) !== $pos + strlen($alias) + 1) {
                $prevChar = substr($selectExpr, $pos - 1, 1);
                if (in_array($prevChar, [' ', '(', ','], true)) {
                    $nextChar = substr($selectExpr, $pos + strlen($alias), 1);
                    if ('.' === $nextChar) {
                        return $pos;
                    }
                }
            }
            $pos = strpos($selectExpr, $alias, $pos + strlen($alias));
        }

        return false;
    }
}
