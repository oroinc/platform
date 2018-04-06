<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateEmailUser implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::updateEmailUser($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function updateEmailUser(Schema $schema)
    {
        self::updateEmailUserTableFields($schema);
    }

    protected static function updateEmailUserTableFields(Schema $schema)
    {
        $emailUserTable = $schema->getTable('oro_email_user');

        /** Update columns */
        $emailUserTable->changeColumn('folder_id', ['notnull' => true]);
        $emailUserTable->changeColumn('email_id', ['notnull' => true]);

        /** Add indexes */
        $emailUserTable->addForeignKeyConstraint(
            $schema->getTable('oro_email'),
            ['email_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_91F5CFF6A832C1C9'
        );
        $emailUserTable->addForeignKeyConstraint(
            $schema->getTable('oro_email_folder'),
            ['folder_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null],
            'FK_91F5CFF6162CB942'
        );
    }
}
