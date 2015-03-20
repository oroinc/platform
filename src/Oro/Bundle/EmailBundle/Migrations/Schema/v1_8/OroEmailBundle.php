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
        $table->addColumn('attachment_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment'),
            ['attachment_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => 'SET NULL']
        );
    }
}
