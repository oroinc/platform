<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Depends to the UserBundle
 *
 * Class DropEmailUserColumn
 * @package Oro\Bundle\UserBundle\Migrations\Schema\v1_18
 */
class DropEmailUserColumn implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 4;
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
        $table->dropColumn('folder_id');
        $table = $schema->getTable('oro_email_user_folders');
        $table->dropIndex('IDX_origin');
        $table->dropColumn('origin_id');
        $table->dropIndex('IDX_email');
        $table->dropColumn('email_id');
    }
}
