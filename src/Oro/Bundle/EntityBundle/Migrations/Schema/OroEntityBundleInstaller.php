<?php

namespace Oro\EntityBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
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
