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
        $this->createOroSecurityPermDefinitionTable($schema);
    }

    /**
     * Create oro_security_perm_definition table
     *
     * @param Schema $schema
     */
    protected function createOroSecurityPermDefinitionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_security_perm_definition');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_83424D0F5E237E06');
    }

}
