<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Depends to the UserBundle
 *
 * Class AddEmailUserColumn
 * @package Oro\Bundle\UserBundle\Migrations\Schema\v1_18
 */
class AddEmailUserColumn implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::updateOroEmailUserTable($schema);
    }

    /**
     * Add origin to EmailUser
     *
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function updateOroEmailUserTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addIndex(['origin_id'], 'IDX_91F5CFF656A273CC', []);
    }
}
