<?php

namespace Oro\Bundle\ScopeBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroScopeBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_1';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createScopeTable($schema);
    }

    private function createScopeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_scope');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('row_hash', 'string', ['length' => 32, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['row_hash'], 'oro_scope_row_hash_uidx');
    }
}
