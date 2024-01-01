<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_26;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email_user');
        $table->dropIndex('mailbox_seen_idx');
        $table->addIndex(['is_seen', 'mailbox_owner_id'], 'seen_idx');
        $table->addIndex(['received', 'is_seen', 'mailbox_owner_id'], 'received_idx');
    }
}
