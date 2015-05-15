<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveOldSchema implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::removeOldRelation($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public static function removeOldRelation(Schema $schema)
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

/*        $emailTable = $schema->getTable('oro_email');

        $emailTable->removeForeignKey('FK_2A30C17132C8A3DE');
        $emailTable->removeForeignKey('FK_2A30C1719EB185F9');

        $emailTable->dropIndex('oro_email_is_head');
        $emailTable->dropIndex('IDX_2A30C17132C8A3DE');
        $emailTable->dropIndex('IDX_2A30C1719EB185F9');

        $emailTable->dropColumn('organization_id');
        $emailTable->dropColumn('user_owner_id,');
        $emailTable->dropColumn('received');
        $emailTable->dropColumn('is_head');
        $emailTable->dropColumn('is_seen');*/
    }
}
