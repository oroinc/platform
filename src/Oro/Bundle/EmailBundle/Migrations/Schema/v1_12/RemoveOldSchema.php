<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveOldSchema implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::removeOldSchema($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function removeOldSchema(Schema $schema)
    {
        self::removeOldRelations($schema);
        self::updateEmailUserTableFields($schema);
    }

    protected function removeOldRelations(Schema $schema)
    {
        $emailBodyTable = $schema->getTable('oro_email_body');

        if ($emailBodyTable->hasForeignKey('fk_oro_email_body_email_id')) {
            $emailBodyTable->removeForeignKey('fk_oro_email_body_email_id');
        }
        if ($emailBodyTable->hasIndex('IDX_C7CE120DA832C1C9')) {
            $emailBodyTable->dropIndex('IDX_C7CE120DA832C1C9');
        }
        if ($emailBodyTable->hasColumn('email_id')) {
            $emailBodyTable->dropColumn('email_id');
        }

        $emailTable = $schema->getTable('oro_email');

        if ($emailTable->hasForeignKey('FK_2A30C17132C8A3DE')) {
            $emailTable->removeForeignKey('FK_2A30C17132C8A3DE');
        }
        if ($emailTable->hasForeignKey('FK_2A30C1719EB185F9')) {
            $emailTable->removeForeignKey('FK_2A30C1719EB185F9');
        }

        if ($emailTable->hasIndex('oro_email_is_head')) {
            $emailTable->dropIndex('oro_email_is_head');
        }
        if ($emailTable->hasIndex('IDX_2A30C17132C8A3DE')) {
            $emailTable->dropIndex('IDX_2A30C17132C8A3DE');
        }
        if ($emailTable->hasIndex('IDX_2A30C1719EB185F9')) {
            $emailTable->dropIndex('IDX_2A30C1719EB185F9');
        }

        if ($emailTable->hasColumn('organization_id')) {
            $emailTable->dropColumn('organization_id');
        }
        if ($emailTable->hasColumn('user_owner_id')) {
            $emailTable->dropColumn('user_owner_id');
        }
        if ($emailTable->hasColumn('received')) {
            $emailTable->dropColumn('received');
        }
        if ($emailTable->hasColumn('is_seen')) {
            $emailTable->dropColumn('is_seen');
        }
    }

    protected function updateEmailUserTableFields(Schema $schema)
    {
        $emailUserTable = $schema->getTable('oro_email_user');

        $emailUserTable->changeColumn('folder_id', ['notnull' => true]);
        $emailUserTable->changeColumn('email_id', ['notnull' => true]);
    }
}
