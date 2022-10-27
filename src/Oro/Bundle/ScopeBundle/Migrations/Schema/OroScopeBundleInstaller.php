<?php

namespace Oro\Bundle\ScopeBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroScopeBundleInstaller implements Installation
{
    const ORO_SCOPE = 'oro_scope';

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createScopeTable($schema);
    }

    protected function createScopeTable(Schema $schema): void
    {
        $table = $schema->createTable(self::ORO_SCOPE);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('row_hash', 'string', ['length' => 32, 'notnull' => false]);
        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['row_hash'], 'oro_scope_row_hash_uidx');
    }
}
