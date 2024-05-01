<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_6;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

/**
 * Change type of ARRAY columns to JSONb for Checkout bundle entities.
 */
class MigrateWorkflowConfigsToJsonb implements Migration, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new SqlMigrationQuery(
            'ALTER TABLE oro_workflow_restriction ALTER COLUMN mode_values TYPE JSONB USING mode_values::jsonb'
        ));
        $queries->addQuery(new SqlMigrationQuery(
            "COMMENT ON COLUMN oro_workflow_restriction.mode_values IS '(DC2Type:json)'"
        ));

        $this->migrateArrayToJsonb('oro_workflow_definition', 'configuration', $queries);
        $this->migrateArrayToJsonb('oro_process_definition', 'actions_configuration', $queries);
        $this->migrateArrayToJsonb('oro_process_definition', 'pre_conditions_configuration', $queries);
    }

    private function migrateArrayToJsonb(string $tableName, string $columnName, QueryBag $queries): void
    {
        // Change column type to JSONb, serialized data will be a string in JSON
        $queries->addQuery(new SqlMigrationQuery(sprintf(
            'ALTER TABLE %1$s ALTER COLUMN %2$s TYPE JSONB USING (\'"\' || %2$s || \'"\')::jsonb',
            $tableName,
            $columnName
        )));
        $queries->addQuery(new SqlMigrationQuery(sprintf(
            "COMMENT ON COLUMN %s.%s IS '(DC2Type:json)'",
            $tableName,
            $columnName
        )));

        // Iterate over data, decode it with PHP because string is a base64 encoded serialized array
        // and put it back as JSON
        $select = $this->connection->createQueryBuilder()
            ->from($tableName)
            ->select('name', $columnName);
        foreach ($this->connection->iterateKeyValue($select->getSQL()) as $name => $value) {
            $data = $this->connection->convertToPHPValue($value, Types::ARRAY);

            $update = $this->connection->createQueryBuilder()
                ->update($tableName)
                ->set($columnName, ':value')
                ->where('name = :name')
                ->setParameter('name', $name, Types::STRING)
                ->setParameter('value', $data, Types::JSON);

            $queries->addQuery(new ParametrizedSqlMigrationQuery(
                $update->getSQL(),
                $update->getParameters(),
                $update->getParameterTypes()
            ));
        }
    }
}
