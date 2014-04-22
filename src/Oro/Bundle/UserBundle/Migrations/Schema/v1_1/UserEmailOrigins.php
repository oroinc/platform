<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UserEmailOrigins implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::oroUserEmailOriginTable($schema);
        self::oroUserEmailOriginForeignKeys($schema);
    }

    /**
     * Generate table oro_user_email_origin
     *
     * @param Schema $schema
     */
    public static function oroUserEmailOriginTable(Schema $schema)
    {
        /** Generate table oro_user_email_origin **/
        $table = $schema->createTable('oro_user_email_origin');
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('origin_id', 'integer', []);
        $table->setPrimaryKey(['user_id', 'origin_id']);
        $table->addIndex(['user_id'], 'IDX_CB3E838BA76ED395', []);
        $table->addIndex(['origin_id'], 'IDX_CB3E838B56A273CC', []);
        /** End of generate table oro_user_email_origin **/
    }

    /**
     * Generate foreign keys for table oro_user_email_origin
     *
     * @param Schema $schema
     */
    public static function oroUserEmailOriginForeignKeys(Schema $schema)
    {
        /** Generate foreign keys for table oro_user_email_origin **/
        $table = $schema->getTable('oro_user_email_origin');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_origin'),
            ['origin_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        /** End of generate foreign keys for table oro_user_email_origin **/
    }
}
