<?php

namespace Oro\Bundle\PlatformBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class OroPlatformBundleInstaller implements Installation, DatabasePlatformAwareInterface
{
    /** @var AbstractPlatform */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->oroSessionTable($schema, $queries);
        $this->createMaterializedViewTable($schema, $queries);
    }

    /**
     * Makes sure oro_session table is up-to-date
     */
    public function oroSessionTable(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable('oro_session')) {
            $this->createOroSessionTable($schema);
        } else {
            $currentSchema  = new Schema([clone $schema->getTable('oro_session')]);
            $requiredSchema = new Schema();
            $this->createOroSessionTable($requiredSchema);

            $comparator = new Comparator();
            $changes    = $comparator->compare($currentSchema, $requiredSchema)->toSql($this->platform);
            if ($changes) {
                // force to recreate oro_session table as a result of dropTable/createTable pair
                // might be "ALTER TABLE" query rather than "DROP/CREATE" queries
                $dropTableSql   = $comparator->compare($currentSchema, new Schema())->toSql($this->platform);
                $createTableSql = $comparator->compare(new Schema(), $requiredSchema)->toSql($this->platform);
                $queries->addQuery(new SqlMigrationQuery($dropTableSql));
                $queries->addQuery(new SqlMigrationQuery($createTableSql));
            }
        }
    }

    /**
     * Create oro_session table
     */
    public function createOroSessionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_session');
        if ($this->platform instanceof MySqlPlatform) {
            $table->addColumn('id', Types::BINARY, ['length' => 128]);
            $table->addColumn('sess_data', Types::BLOB, ['length' => MySqlPlatform::LENGTH_LIMIT_BLOB]);
        } else {
            $table->addColumn('id', Types::STRING, ['length' => 128]);
            $table->addColumn('sess_data', Types::BLOB, []);
        }
        $table->addColumn('sess_time', Types::INTEGER, []);
        $table->addColumn('sess_lifetime', Types::INTEGER, []);
        $table->setPrimaryKey(['id']);
    }

    private function createMaterializedViewTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_materialized_view');
        $table->addColumn('name', Types::STRING, ['length' => 63]);
        $table->setPrimaryKey(['name']);

        $table->addColumn('with_data', Types::BOOLEAN, ['default' => false]);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE, []);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE, []);
    }
}
