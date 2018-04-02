<?php

namespace Oro\Bundle\EntityBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEntityBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

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
