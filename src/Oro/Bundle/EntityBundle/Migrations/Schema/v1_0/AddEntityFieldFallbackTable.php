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
        $this->createOroEntityFieldFallbackValTable($schema);
    }

    /**
     * Create oro_entity_field_fallback_val table
     *
     * @param Schema $schema
     */
    protected function createOroEntityFieldFallbackValTable(Schema $schema)
    {
        $table = $schema->createTable('oro_entity_field_fallback_val');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string_value', 'string', ['notnull' => false, 'length' => 64]);
        $table->setPrimaryKey(['id']);
    }
}
