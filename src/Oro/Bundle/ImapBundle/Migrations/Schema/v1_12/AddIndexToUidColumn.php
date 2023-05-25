<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add index to uid column for the oro_email_imap table
 */
class AddIndexToUidColumn implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email_imap');

        if ($table->hasIndex('email_imap_uid_idx')) {
            return;
        }

        $table->addIndex(['uid'], 'email_imap_uid_idx');
    }
}
