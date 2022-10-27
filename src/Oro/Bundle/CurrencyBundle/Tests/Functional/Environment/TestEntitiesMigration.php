<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Functional\Environment;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class TestEntitiesMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_test_money_entity')) {
            return;
        }

        $this->createTestMoneyEntityTable($schema);
    }

    /**
     * Create oro_test_money_entity table
     */
    private function createTestMoneyEntityTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_test_money_entity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn(
            'money_property',
            'money',
            ['notnull' => false, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'money_value_property',
            'money_value',
            ['notnull' => false, 'comment' => '(DC2Type:money_value)']
        );
        $table->setPrimaryKey(['id']);
    }
}
