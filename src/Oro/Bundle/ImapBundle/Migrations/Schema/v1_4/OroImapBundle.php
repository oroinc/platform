<?php

namespace Oro\Bundle\ImapBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroImapBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_email_origin');
        $table->addColumn('access_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('refresh_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('access_token_expires_at', 'datetime', ['notnull' => false]);
    }
}
