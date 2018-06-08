<?php

namespace Oro\Bundle\WindowsBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
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
        $table->addColumn('id', Type::INTEGER, ['autoincrement' => true]);
        $table->addColumn('user_id', Type::INTEGER, []);
        $table->addColumn('data', Type::JSON_ARRAY, ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('created_at', Type::DATETIME, []);
        $table->addColumn('updated_at', Type::DATETIME, []);
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
