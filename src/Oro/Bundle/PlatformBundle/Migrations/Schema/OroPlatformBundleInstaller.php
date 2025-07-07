<?php

namespace Oro\Bundle\PlatformBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class OroPlatformBundleInstaller implements Installation, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v6_1_3_0';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->oroSessionTable($schema, $queries);
        $this->createMaterializedViewTable($schema);
        $this->createNumberSequenceTable($schema);
    }

    /**
     * Makes sure oro_session table is up-to-date
     */
    private function oroSessionTable(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_session')) {
            $this->createOroSessionTable($schema);
        } else {
            $currentSchema = new Schema([clone $schema->getTable('oro_session')]);
            $requiredSchema = new Schema();
            $this->createOroSessionTable($requiredSchema);

            $comparator = new Comparator();
            $changes = $comparator->compare($currentSchema, $requiredSchema)->toSql($this->platform);
            if ($changes) {
                // force to recreate oro_session table as a result of dropTable/createTable pair
                // might be "ALTER TABLE" query rather than "DROP/CREATE" queries
                $dropTableSql = $comparator->compare($currentSchema, new Schema())->toSql($this->platform);
                $createTableSql = $comparator->compare(new Schema(), $requiredSchema)->toSql($this->platform);
                $queries->addQuery(new SqlMigrationQuery($dropTableSql));
                $queries->addQuery(new SqlMigrationQuery($createTableSql));
            }
        }
    }

    /**
     * Create oro_session table
     */
    private function createOroSessionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_session');
        if ($this->platform instanceof MySqlPlatform) {
            $table->addColumn('id', Types::BINARY, ['length' => 128]);
            $table->addColumn('sess_data', Types::BLOB, ['length' => MySqlPlatform::LENGTH_LIMIT_BLOB]);
        } else {
            $table->addColumn('id', Types::STRING, ['length' => 128]);
            $table->addColumn('sess_data', Types::BLOB);
        }
        $table->addColumn('sess_time', Types::INTEGER);
        $table->addColumn('sess_lifetime', Types::INTEGER);
        $table->setPrimaryKey(['id']);
    }

    private function createMaterializedViewTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_materialized_view');
        $table->addColumn('name', Types::STRING, ['length' => 63]);
        $table->addColumn('with_data', Types::BOOLEAN, ['default' => false]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE);
        $table->setPrimaryKey(['name']);
    }

    private function createNumberSequenceTable(Schema $schema): void
    {
        if (!$schema->hasTable('oro_number_sequence')) {
            $table = $schema->createTable('oro_number_sequence');
            $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
            $table->setPrimaryKey(['id']);

            $table->addColumn('sequence_type', Types::STRING, ['length' => 255]);
            $table->addColumn('discriminator_type', Types::STRING, ['length' => 255]);
            $table->addColumn('discriminator_value', Types::STRING, ['length' => 255]);
            $table->addColumn('number', Types::INTEGER, []);
            $table->addColumn('created_at', Types::DATETIME_MUTABLE, ['comment' => '(DC2Type:datetime)']);
            $table->addColumn('updated_at', Types::DATETIME_MUTABLE, ['comment' => '(DC2Type:datetime)']);

            $table->addUniqueIndex(['sequence_type', 'discriminator_type', 'discriminator_value'], 'oro_sequence_uidx');
        }
    }
}
