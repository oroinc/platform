<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateWebsocketServerStateTable implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_sync_websocket_server_state')) {
            $table = $schema->createTable('oro_sync_websocket_server_state');
            $table->addColumn('id', 'string', ['length' => 15, 'notnull' => true]);
            $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
            $table->setPrimaryKey(['id']);
        }
    }
}
