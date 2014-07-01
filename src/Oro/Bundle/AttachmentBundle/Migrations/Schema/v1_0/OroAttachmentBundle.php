<?php

namespace Oro\Bundle\AttachmentBundle\Migrations\Schema\v1_0;

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
        $table = $schema->createTable('oro_attachment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('filename', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('extension', 'string', ['length' => 10, 'notnull' => false]);
        $table->addColumn('mime_type', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('file_size', 'integer', ['notnull' => false]);
        $table->addColumn('original_filename', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);

        $table->setPrimaryKey(['id']);
    }
}
