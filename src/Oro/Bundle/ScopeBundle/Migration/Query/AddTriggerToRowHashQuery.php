<?php

namespace Oro\Bundle\ScopeBundle\Migration\Query;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Psr\Log\LoggerInterface;

/**
 * Execute sql query for adding trigger to oro_scope table for filling row_hash column.
 */
class AddTriggerToRowHashQuery extends AbstractScopeQuery
{
    /**
     * {@inheritDoc}
     */
    protected function getInfo(): string
    {
        return 'Add row_hash fill trigger to oro_scope';
    }

    /**
     * {@inheritDoc}
     */
    public function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $this->addTriggers($logger, $dryRun);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function addTriggers(LoggerInterface $logger, bool $dryRun): void
    {
        if ($this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $this->executePgSqlTriggerQuery($logger, $dryRun);
        } else {
            $this->executeMySqlTriggerQuery($logger, $dryRun);
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function executePgSqlTriggerQuery(LoggerInterface $logger, bool $dryRun): void
    {
        $columnHashExpression = $this->getColumnsHashExpressionForPgSql('NEW.');

        $updateProcedure = <<<FUNCTOIN
CREATE OR REPLACE FUNCTION oro_scope_fill_row_hash()
  RETURNS trigger AS
$$
BEGIN
    NEW.row_hash = $columnHashExpression;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql
FUNCTOIN;

        $triggerSql = <<<TRIGGER
DROP TRIGGER IF EXISTS oro_scope_fill_row_hash_trigger ON oro_scope;
CREATE TRIGGER oro_scope_fill_row_hash_trigger
BEFORE INSERT ON oro_scope
FOR EACH ROW
EXECUTE PROCEDURE oro_scope_fill_row_hash();
TRIGGER;

        $this->logQuery($logger, $updateProcedure);
        $this->logQuery($logger, $triggerSql);
        if (!$dryRun) {
            $this->connection->executeQuery($updateProcedure);
            $this->connection->executeQuery($triggerSql);
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function executeMySqlTriggerQuery(LoggerInterface $logger, bool $dryRun): void
    {
        $columnHashExpression = $this->getColumnsHashExpressionForMySql('NEW.');

        $triggerSql = <<<TRIGGER
DROP TRIGGER IF EXISTS oro_scope_fill_row_hash_trigger;
CREATE TRIGGER oro_scope_fill_row_hash_trigger BEFORE INSERT ON oro_scope 
FOR EACH ROW
 BEGIN
    SET NEW.row_hash = $columnHashExpression ;
 END;
TRIGGER;

        $this->logQuery($logger, $triggerSql);
        if (!$dryRun) {
            $this->connection->executeQuery($triggerSql);
        }
    }
}
