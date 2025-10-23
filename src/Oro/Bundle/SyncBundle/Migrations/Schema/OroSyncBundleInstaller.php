<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSyncBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v6_1_0_6';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createStateTable($schema);
    }

    private function createStateTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_sync_websocket_server_state');
        $table->addColumn('id', 'string', ['length' => 15, 'notnull' => true]);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }
}
