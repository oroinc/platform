<?php

namespace Oro\Bundle\QueryDesignerBundle\QueryDesigner;

/**
 * Provides a set of static methods to work with query expressions created by the query designer.
 */
final class QueryExprUtil
{
    /**
     * Replaces all table aliases declared in the join expression part of a query with the given aliases.
     */
    public static function replaceTableAliasesInJoinAlias(array &$joins, array $aliases): void
    {
        foreach ($joins as $key => $join) {
            $alias = $join['alias'];
            if (isset($aliases[$alias])) {
                $joins[$key]['alias'] = $aliases[$alias];
            }
        }
    }

    /**
     * Replaces all table aliases declared in the join expression part of a query with the given aliases.
     */
    public static function replaceTableAliasesInJoinExpr(array &$joins, array $aliases): void
    {
        // replace alias with {{newAlias}} - this is required to prevent collisions
        // between old and new aliases in case if some new alias has the same name as some old alias
        foreach ($joins as $key => $join) {
            $joins[$key]['join'] = self::replaceTableAliasesInExpr($join['join'], $aliases);
        }
        // replace {{newAlias}} with newAlias
        foreach ($joins as $key => $join) {
            $joinExpr = $join['join'];
            foreach ($aliases as $newAlias) {
                $joinExpr = self::resolveNewAliasPlaceholder($joinExpr, $newAlias);
            }
            $joins[$key]['join'] = $joinExpr;
        }
    }

    /**
     * Replaces all table aliases declared in the join part of a query with the given aliases.
     */
    public static function replaceTableAliasesInJoinConditions(array &$joins, array $aliases): void
    {
        // replace alias with {{newAlias}} - this is required to prevent collisions
        // between old and new aliases in case if some new alias has the same name as some old alias
        foreach ($joins as $key => $join) {
            if (isset($join['condition'])) {
                $joins[$key]['condition'] = self::replaceTableAliasesInCondition($join['condition'], $aliases);
            }
        }
        // replace {{newAlias}} with newAlias
        foreach ($joins as $key => $join) {
            if (isset($join['condition'])) {
                $condition = $join['condition'];
                foreach ($aliases as $newAlias) {
                    $condition = self::resolveNewAliasPlaceholder($condition, $newAlias);
                }
                $joins[$key]['condition'] = $condition;
            }
        }
    }

    /**
     * Replaces all table aliases declared in the select part of a query with the given aliases.
     *
     * @param string $selectExpr
     * @param array  $aliases
     *
     * @return string The corrected select expression
     */
    public static function replaceTableAliasesInSelectExpr(string $selectExpr, array $aliases): string
    {
        // replace alias with {{newAlias}} - this is required to prevent collisions
        // between old and new aliases in case if some new alias has the same name as some old alias
        $selectExpr = self::replaceTableAliasesInExpr($selectExpr, $aliases);
        // replace {{newAlias}} with newAlias
        foreach ($aliases as $newAlias) {
            $selectExpr = self::resolveNewAliasPlaceholder($selectExpr, $newAlias);
        }

        return $selectExpr;
    }

    /**
     * Replace the given aliases with placeholders of theirs new aliases in the given expression.
     *
     * @param string $expr
     * @param array  $aliases
     *
     * @return string The updated expression
     */
    private static function replaceTableAliasesInExpr(string $expr, array $aliases): string
    {
        foreach ($aliases as $alias => $newAlias) {
            $tryFind = true;
            while ($tryFind) {
                $tryFind = false;
                $pos = self::findTableAliasInExpr($expr, $alias);
                if (null !== $pos) {
                    $expr = self::replaceAliasWithNewAliasPlaceholder($expr, $pos, $alias, $newAlias);
                    $tryFind = true;
                }
            }
        }

        return $expr;
    }

    /**
     * Checks if the given expression contains the given table alias.
     *
     * @param string $expr
     * @param string $alias
     *
     * @return int|null The position of the alias in the expression or NULL if it was not found
     */
    private static function findTableAliasInExpr(string $expr, string $alias): ?int
    {
        $pos = strpos($expr, $alias);
        while (false !== $pos) {
            if (0 === $pos) {
                $nextChar = $expr[$pos + \strlen($alias)];
                if ('.' === $nextChar) {
                    return $pos;
                }
            } elseif (\strlen($expr) !== $pos + \strlen($alias) + 1) {
                $prevChar = $expr[$pos - 1];
                if (\in_array($prevChar, [' ', '(', ','], true)) {
                    $nextChar = $expr[$pos + \strlen($alias)];
                    if ('.' === $nextChar) {
                        return $pos;
                    }
                }
            }
            $pos = strpos($expr, $alias, $pos + \strlen($alias));
        }

        return null;
    }

    /**
     * Replace the given aliases with placeholders of theirs new aliases in the given condition.
     *
     * @param string $condition
     * @param array  $aliases
     *
     * @return string The updated condition
     */
    private static function replaceTableAliasesInCondition(string $condition, array $aliases): string
    {
        foreach ($aliases as $alias => $newAlias) {
            $tryFind = true;
            while ($tryFind) {
                $tryFind = false;
                $pos = self::findTableAliasInCondition($condition, $alias);
                if (null !== $pos) {
                    $condition = self::replaceAliasWithNewAliasPlaceholder($condition, $pos, $alias, $newAlias);
                    $tryFind = true;
                }
            }
        }

        return $condition;
    }

    /**
     * Checks if the given condition contains the given table alias.
     *
     * @param string $condition
     * @param string $alias
     * @param int    $offset
     *
     * @return int|null The position of the alias in the condition or NULL if it was not found
     */
    private static function findTableAliasInCondition(string $condition, string $alias, int $offset = 0): ?int
    {
        $pos = strpos($condition, $alias, $offset);
        if (false !== $pos) {
            if (0 === $pos) {
                // handle case "ALIAS.", "ALIAS.field"
                $nextChar = $condition[$pos + \strlen($alias)];
                if (\in_array($nextChar, ['.', ' ', '='], true)) {
                    return $pos;
                }

                // handle case "ALIASWord.entity = ALIAS"
                return self::findTableAliasInCondition($condition, $alias, ++$pos);
            }
            if (\strlen($condition) === $pos + \strlen($alias)) {
                // handle case "t2.someField = ALIAS"
                $prevChar = $condition[$pos - 1];
                if (\in_array($prevChar, [' ', '='], true)) {
                    return $pos;
                }
            } else {
                // handle case "t2.someField = ALIAS.id"
                $prevChar = $condition[$pos - 1];
                if (\in_array($prevChar, [' ', '=', '('], true)) {
                    $nextChar = $condition[$pos + \strlen($alias)];
                    if (\in_array($nextChar, ['.', ' ', '=', ')'], true)) {
                        return $pos;
                    }
                }

                // handle case "t2.ALIAS = ALIAS AND"
                return self::findTableAliasInCondition($condition, $alias, ++$pos);
            }
        }

        return null;
    }

    private static function replaceAliasWithNewAliasPlaceholder(
        string $expr,
        int $offset,
        string $alias,
        string $newAlias
    ): string {
        return sprintf(
            '%s{{%s}}%s',
            substr($expr, 0, $offset),
            $newAlias,
            substr($expr, $offset + \strlen($alias))
        );
    }

    private static function resolveNewAliasPlaceholder(string $expr, string $newAlias): string
    {
        return str_replace(sprintf('{{%s}}', $newAlias), $newAlias, $expr);
    }
}
