<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareInterface;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareTrait;

class OroTestFrameworkBundle implements Migration, ScopeExtensionAwareInterface
{
    use ScopeExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createTestDefaultAndNullTable($schema);

        /** Entity extensions generation */
        $this->extendScopeForTestActivity($schema);
    }

    /**
     * Create test_default_and_null table
     *
     * @param Schema $schema
     */
    protected function createTestDefaultAndNullTable(Schema $schema)
    {
        $table = $schema->createTable('test_default_and_null');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('with_default_value_string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('without_default_value_string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('with_default_value_boolean', 'boolean', ['notnull' => false]);
        $table->addColumn('without_default_value_boolean', 'boolean', ['notnull' => false]);
        $table->addColumn('with_default_value_integer', 'integer', ['notnull' => false]);
        $table->addColumn('without_default_value_integer', 'integer', ['notnull' => false]);
        $table->addColumn('with_df_not_blank', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('with_df_not_null', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('with_not_blank', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('with_not_null', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function extendScopeForTestActivity($schema)
    {
        $this->scopeExtension->addScopeAssociation($schema, 'test_activity', 'test_activity', 'id');
    }
}
