<?php

namespace Oro\Bundle\ScopeBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroScopeBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createScopeTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_scope');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
    }
}
