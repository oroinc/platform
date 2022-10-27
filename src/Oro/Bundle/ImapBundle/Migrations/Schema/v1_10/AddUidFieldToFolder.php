<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddUidFieldToFolder implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_email_folder_imap');
        $table->addColumn('last_uid', 'integer', ['notnull' => false]);
    }
}
