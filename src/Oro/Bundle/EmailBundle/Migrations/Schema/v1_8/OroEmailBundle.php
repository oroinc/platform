<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addAttachmentRelation($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addAttachmentRelation(Schema $schema)
    {
        $table = $schema->getTable('oro_email_attachment');
        $table->addColumn('file_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['file_id'], 'UNIQ_F4427F2393CB796C');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'FK_F4427F2393CB796C'
        );
    }
}
