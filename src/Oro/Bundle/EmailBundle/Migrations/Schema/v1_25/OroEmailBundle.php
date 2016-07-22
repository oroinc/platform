<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_25;

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
        static::addEmailUserMailboxOwnerSeenIndex($schema);
    }

    /**
     * @param Schema $schema
     */
    public static function addEmailUserMailboxOwnerSeenIndex(Schema $schema)
    {
        $table = $schema->getTable('oro_email_user');
        $table->addIndex(['mailbox_owner_id', 'is_seen'], 'mailbox_seen_idx');
    }
}
