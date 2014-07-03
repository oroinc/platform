<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAttachmentBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::createAttachmentTable($schema);
    }

    public static function createAttachmentTable(Schema $schema)
    {
        $table = $schema->createTable('oro_attachment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('file_id', 'integer', ['notnull' => false]);
        $table->addColumn('comment', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['file_id'], 'IDX_FA0FE08193CB796C', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
