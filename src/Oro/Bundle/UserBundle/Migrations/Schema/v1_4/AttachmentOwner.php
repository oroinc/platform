<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AttachmentOwner implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addOwnerToAttachment($schema);
    }

    public static function addOwnerToAttachment(Schema $schema)
    {
        $table = $schema->getTable('oro_attachment');
        $table->addColumn('owner_user_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_user_id'], 'IDX_FA0FE0812B18554A', []);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
