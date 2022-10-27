<?php

namespace Oro\Component\DoctrineUtils\ORM\Walker;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

/**
 * Replaces the whole 'FROM ... table_alias ...' clause with 'FROM "your_materialized_view" table_alias'.
 * Replaces 'SELECT ...' clause with 'SELECT *' to fetch all columns that are present in the backing query of
 * the materialized view.
 * Removes WHERE, GROUP BY, HAVING, ORDER BY as all these parts are already present in the backing query of
 * the materialized view.
 * Removes all parameters as they are already present in the backing query of the materialized view.
 * As a result, any SELECT statement is transformed into:
 *      SELECT * FROM "your_materialized_view" table_alias
 */
class MaterializedViewOutputResultModifier extends AbstractOutputResultModifier
{
    public const USE_MATERIALIZED_VIEW = 'USE_MATERIALIZED_VIEW';

    public function walkSelectStatement(AST\SelectStatement $AST, string $result): string
    {
        if (!$this->hasMaterializedViewHint()) {
            return $result;
        }

        /** @var Query $query */
        $query = $this->getQuery();

        // Removes parameters and parameter mappings as they are already present in the backing query
        // of the materialized view.
        QueryUtil::resetParameters($query, $this->parserResult);

        return $result;
    }

    /**
     * Replaces the whole 'FROM ... table_alias ...' clause with 'FROM "your_materialized_view" table_alias'.
     */
    public function walkFromClause($fromClause, string $result): string
    {
        if (!$this->hasMaterializedViewHint()) {
            return $result;
        }

        /** @var Query $query */
        $query = $this->getQuery();

        /** @var AbstractPlatform $databasePlatform */
        $databasePlatform = $this->getConnection()->getDatabasePlatform();
        $materializedViewName = $query->getHint(self::USE_MATERIALIZED_VIEW);

        return preg_replace(
            '/^\s*(\bFROM\s+)\w+(\s+\w+\b\s*).*$/',
            sprintf('\1%s\2', $databasePlatform->quoteIdentifier($materializedViewName)),
            $result
        );
    }

    /**
     * Replaces "SELECT ..." clause with "SELECT *" to fetch all columns that are present in the backing query of
     * the materialized view.
     */
    public function walkSelectClause($selectClause, string $result): string
    {
        return $this->hasMaterializedViewHint() ? 'SELECT * ' : $result;
    }

    private function hasMaterializedViewHint(): bool
    {
        if ($this->getQuery()?->getHint(self::USE_MATERIALIZED_VIEW) === false) {
            return false;
        }

        /** @var AbstractPlatform $databasePlatform */
        $databasePlatform = $this->getConnection()->getDatabasePlatform();

        return $databasePlatform->getName() === 'postgresql';
    }

    public function walkWhereClause($whereClause, string $result): string
    {
        return $this->hasMaterializedViewHint() ? '' : $result;
    }

    public function walkGroupByClause($groupByClause, string $result): string
    {
        return $this->hasMaterializedViewHint() ? '' : $result;
    }

    public function walkHavingClause($havingClause, string $result): string
    {
        return $this->hasMaterializedViewHint() ? '' : $result;
    }

    public function walkOrderByClause($orderByClause, string $result): string
    {
        return $this->hasMaterializedViewHint() ? '' : $result;
    }
}
