<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_9;

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
        self::changeAttachmentRelation($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function changeAttachmentRelation(Schema $schema)
    {
        $table = $schema->getTable('oro_email_attachment');
        $table->removeForeignKey('FK_F4427F2393CB796C');
        $table->dropIndex('UNIQ_F4427F2393CB796C');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['file_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'FK_F4427F2393CB796C'
        );
    }
}
