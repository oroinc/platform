<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSecurityBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroSecurityPermissionTable($schema);
    }

    /**
     * Create oro_security_permission table
     *
     * @param Schema $schema
     */
    protected function createOroSecurityPermissionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_security_perm_definition');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('description', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_apply_to_all', 'boolean', []);
        $table->addColumn('apply_to_entities', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->addColumn('exclude_entities', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->addColumn('group_names', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_83424D0F5E237E06');
    }

}
