<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addEmailFolderFields($schema);
        self::addEmailOriginFields($schema);
        self::updateMailboxName($schema, $queries);
        self::updateEmailRecipientConstraint($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addEmailFolderFields(Schema $schema)
    {
        $emailFolderTable = $schema->getTable('oro_email_folder');

        $emailFolderTable->addColumn('sync_enabled', 'boolean', ['default' => false]);
        $emailFolderTable->addColumn('parent_folder_id', 'integer', ['notnull' => false]);
        $emailFolderTable->addForeignKeyConstraint(
            $emailFolderTable,
            ['parent_folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_EB940F1C421FFFC'
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function addEmailOriginFields(Schema $schema)
    {
        $table = $schema->getTable('oro_email_origin');

        $table->addColumn('mailbox_name', 'string', ['length' => 64, 'notnull' => true, 'default' => '']);
        $table->addIndex(['mailbox_name'], 'IDX_mailbox_name', []);
    }

    /**
     * @param Schema   $schema
     * @param QueryBag $queries
     */
    public static function updateMailboxName(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email_origin');
        $sql = 'UPDATE oro_email_origin SET mailbox_name = %s WHERE name = :name';

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                sprintf($sql, "'" . InternalEmailOrigin::MAILBOX_NAME . "'"),
                ['name' => 'internalemailorigin'],
                ['name' => Type::STRING]
            )
        );

        if ($table->hasColumn('imap_user')) {
            $queries->addQuery(new ParametrizedSqlMigrationQuery(
                sprintf($sql, 'imap_user'),
                ['name' => 'imapemailorigin'],
                ['name' => Type::STRING]
            ));
            $queries->addQuery(new ParametrizedSqlMigrationQuery(
                sprintf($sql, 'imap_user'),
                ['name' => 'useremailorigin'],
                ['name' => Type::STRING]
            ));
        }

        if ($table->hasColumn('ews_user_email')) {
            $queries->addQuery(new ParametrizedSqlMigrationQuery(
                sprintf($sql, 'ews_user_email'),
                ['name' => 'ewsemailorigin'],
                ['name' => Type::STRING]
            ));
        }
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function updateEmailRecipientConstraint(Schema $schema)
    {
        $table = $schema->getTable('oro_email_recipient');

        $table->removeForeignKey('FK_7DAF9656A832C1C9');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_7DAF9656A832C1C9'
        );
    }
}
