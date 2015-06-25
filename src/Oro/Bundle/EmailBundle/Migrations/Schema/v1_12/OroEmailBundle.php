<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::changeEmailToEmailBodyRelation($schema);
        self::splitEmailEntity($schema);
        self::updateEntityConfigs($queries);
        self::addPostQueries($queries);
    }

    /**
     * @param Schema   $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function changeEmailToEmailBodyRelation(Schema $schema)
    {
        $emailTable = $schema->getTable('oro_email');
        $emailBodyTable = $schema->getTable('oro_email_body');

        $emailTable->addColumn('email_body_id', 'integer', ['notnull' => false]);
        $emailTable->addForeignKeyConstraint(
            $emailBodyTable,
            ['email_body_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null],
            'FK_2A30C17126A2754B'
        );
        $emailTable->addUniqueIndex(['email_body_id'], 'UNIQ_2A30C17126A2754B');
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function splitEmailEntity(Schema $schema)
    {
        $emailUserTable = $schema->createTable('oro_email_user');
        $emailUserTable->addColumn('id', 'integer', ['autoincrement' => true]);
        $emailUserTable->addColumn('folder_id', 'integer', ['notnull' => false]);
        $emailUserTable->addColumn('email_id', 'integer', ['notnull' => false]);
        $emailUserTable->addColumn('created_at', 'datetime');
        $emailUserTable->addColumn('received', 'datetime');
        $emailUserTable->addColumn('is_seen', 'boolean', ['default' => true]);
        $emailUserTable->setPrimaryKey(['id']);

        $emailUserTable->addIndex(['folder_id'], 'IDX_91F5CFF6162CB942');
        $emailUserTable->addIndex(['email_id'], 'IDX_91F5CFF6A832C1C9');

        $emailUserTable->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_91F5CFF6162CB942'
        );
        $emailUserTable->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_91F5CFF6A832C1C9'
        );
    }

    /**
     * @param QueryBag $queries
     */
    public static function updateEntityConfigs(QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Oro\Bundle\EmailBundle\Entity\EmailUser',
                'security',
                'permissions',
                'VIEW;CREATE;EDIT'
            )
        );
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Oro\Bundle\EmailBundle\Entity\Email',
                'activity',
                'route',
                'oro_email_activity_view'
            )
        );
    }

    /**
     * @param QueryBag $queries
     */
    public static function addPostQueries(QueryBag $queries)
    {
        $queries->addPostQuery(new UpdateEmailBodyRelationQuery());
        $queries->addPostQuery(new DeleteEmailPermissionConfig());
    }
}
