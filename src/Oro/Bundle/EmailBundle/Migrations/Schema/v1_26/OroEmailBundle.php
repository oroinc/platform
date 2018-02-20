<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_26;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        static::dropEmailUserMailboxOwnerSeenIndex($schema);
        static::addEmailUserMailboxOwnerSeenIndex($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function dropEmailUserMailboxOwnerSeenIndex(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->dropIndex('mailbox_seen_idx');
    }

    /**
     * @param Schema $schema
     */
    public static function addEmailUserMailboxOwnerSeenIndex(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->addIndex(['is_seen', 'mailbox_owner_id'], 'seen_idx');
        $table->addIndex(['received', 'is_seen', 'mailbox_owner_id'], 'received_idx');
    }
}
