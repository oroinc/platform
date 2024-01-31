<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddEmailUserColumn implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        return 2;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addIndex(['origin_id'], 'IDX_91F5CFF656A273CC');
    }
}
