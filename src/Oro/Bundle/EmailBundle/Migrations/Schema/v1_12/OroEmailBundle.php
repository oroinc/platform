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
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        return 1;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->changeEmailToEmailBodyRelation($schema);
        $this->splitEmailEntity($schema);
        $this->updateEntityConfigs($queries);
        $this->addPostQueries($queries);
    }

    private function changeEmailToEmailBodyRelation(Schema $schema): void
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

    private function splitEmailEntity(Schema $schema): void
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

    private function updateEntityConfigs(QueryBag $queries): void
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

    private function addPostQueries(QueryBag $queries): void
    {
        $queries->addPostQuery(new UpdateEmailBodyRelationQuery());
        $queries->addPostQuery(new DeleteEmailPermissionConfig());
    }
}
