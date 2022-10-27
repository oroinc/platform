<?php

namespace Oro\Component\DoctrineUtils\DBAL\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * DBAL Schema Manager for Postgres Materialized View.
 *
 * @see https://www.postgresql.org/docs/13/rules-materializedviews.html
 */
class MaterializedViewSchemaManager
{
    private Connection $connection;

    private ?AbstractPlatform $platform = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    private function getPlatform(): AbstractPlatform
    {
        if (!$this->platform) {
            $this->platform = $this->connection->getDatabasePlatform();
        }

        return $this->platform;
    }

    /**
     * @see https://www.postgresql.org/docs/13/sql-creatematerializedview.html
     */
    public function create(MaterializedView $materializedView, bool $ifNotExists = true): void
    {
        $name = $materializedView->getQuotedName($this->getPlatform());
        $sql = sprintf(
            'CREATE MATERIALIZED VIEW %s%s AS %s %s',
            $ifNotExists ? 'IF NOT EXISTS ' : '',
            $this->getPlatform()->quoteIdentifier($name),
            $materializedView->getDefinition(),
            $materializedView->isWithData() ? 'WITH DATA' : 'WITH NO DATA'
        );

        $this->connection->executeStatement($sql);
    }

    /**
     * @see https://www.postgresql.org/docs/13/sql-dropmaterializedview.html
     */
    public function drop(MaterializedView|string $materializedView, bool $ifExists = true, bool $cascade = true): void
    {
        $name = $materializedView instanceof MaterializedView
            ? $materializedView->getQuotedName($this->getPlatform())
            : $this->getPlatform()->quoteIdentifier($materializedView);

        $this->connection->executeStatement(
            sprintf(
                'DROP MATERIALIZED VIEW %s%s%s',
                $ifExists ? 'IF EXISTS ' : '',
                $name,
                $cascade ? ' CASCADE' : ''
            )
        );
    }

    /**
     * Replaces the contents of a materialized view by executing the backing query that provides the new data.
     *
     * @param MaterializedView|string $materializedView
     * @param bool $concurrently Refresh the materialized view without locking out concurrent selects on it.
     * @param bool $withData If false - no new data will be generated and the materialized view will remain in
     *                       an unscannable state.
     *
     * @see https://www.postgresql.org/docs/13/sql-refreshmaterializedview.html
     */
    public function refresh(
        MaterializedView|string $materializedView,
        bool $concurrently = false,
        bool $withData = true
    ): void {
        $name = $materializedView instanceof MaterializedView
            ? $materializedView->getQuotedName($this->getPlatform())
            : $this->getPlatform()->quoteIdentifier($materializedView);

        $this->connection->executeStatement(
            sprintf(
                'REFRESH MATERIALIZED VIEW %s%s %s',
                $concurrently ? 'CONCURRENTLY ' : '',
                $name,
                $withData ? 'WITH DATA' : 'WITH NO DATA'
            )
        );
    }
}
