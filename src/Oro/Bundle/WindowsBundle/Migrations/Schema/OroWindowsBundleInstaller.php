<?php

namespace Oro\Bundle\WindowsBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWindowsBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroWindowsStateTable($schema);

        /** Foreign keys generation **/
        $this->addOroWindowsStateForeignKeys($schema);
    }

    /**
     * Create oro_windows_state table
     *
     * @param Schema $schema
     */
    protected function createOroWindowsStateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_windows_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('data', 'text', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_8B134CF6A76ED395', []);
    }

    /**
     * Add oro_windows_state foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWindowsStateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_windows_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
