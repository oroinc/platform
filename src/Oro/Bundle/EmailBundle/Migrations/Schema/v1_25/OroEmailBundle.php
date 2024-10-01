<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_25;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_email_user')
            ->addIndex(['mailbox_owner_id', 'is_seen'], 'mailbox_seen_idx');
    }
}
