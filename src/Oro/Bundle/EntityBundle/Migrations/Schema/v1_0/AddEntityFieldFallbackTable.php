<?php

namespace Oro\Bundle\EntityBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddEntityFieldFallbackTable
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroEntityFallbackValueTable($schema);
    }

    /**
     * Create oro_entity_fallback_value table
     *
     * @param Schema $schema
     */
    protected function createOroEntityFallbackValueTable(Schema $schema)
    {
        $table = $schema->createTable('oro_entity_fallback_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('scalar_value', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('array_value', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
    }
}
