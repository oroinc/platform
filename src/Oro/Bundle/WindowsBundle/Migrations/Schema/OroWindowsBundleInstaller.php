<?php

namespace Oro\Bundle\WindowsBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWindowsBundleInstaller implements Installation
{
    /** {@inheritdoc} */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /** {@inheritdoc} */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_windows_state');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('user_id', Types::INTEGER, []);
        $table->addColumn('data', Types::JSON_ARRAY, ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('created_at', Types::DATETIME_MUTABLE, []);
        $table->addColumn('updated_at', Types::DATETIME_MUTABLE, []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id'], 'IDX_8B134CF6A76ED395', []);

        $table = $schema->getTable('oro_windows_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
