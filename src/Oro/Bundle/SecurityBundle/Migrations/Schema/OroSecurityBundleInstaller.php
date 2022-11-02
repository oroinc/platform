<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroSecurityBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // create symfony acl tables
        $this->container->get('security.acl.dbal.schema')->addToSchema($schema);
        $schema->getTable($this->container->getParameter('security.acl.dbal.entry_table_name'))
            ->getColumn('field_name')
            ->setLength(255);

        /** Tables generation **/
        $this->createOroSecurityPermApplyEntityTable($schema);
        $this->createOroSecurityPermExclEntityTable($schema);
        $this->createOroSecurityPermissionTable($schema);
        $this->createOroSecurityPermissionEntityTable($schema);
        $this->createRememberMeTokenTable($schema);

        /** Foreign keys generation **/
        $this->addOroSecurityPermApplyEntityForeignKeys($schema);
        $this->addOroSecurityPermExclEntityForeignKeys($schema);

        $queries->addPostQuery(new LoadBasePermissionsQuery());
    }

    /**
     * Create oro_security_perm_apply_entity table
     */
    protected function createOroSecurityPermApplyEntityTable(Schema $schema)
    {
        $table = $schema->createTable('oro_security_perm_apply_entity');
        $table->addColumn('permission_id', 'integer', []);
        $table->addColumn('permission_entity_id', 'integer', []);
        $table->setPrimaryKey(['permission_id', 'permission_entity_id']);
    }

    /**
     * Create oro_security_perm_excl_entity table
     */
    protected function createOroSecurityPermExclEntityTable(Schema $schema)
    {
        $table = $schema->createTable('oro_security_perm_excl_entity');
        $table->addColumn('permission_id', 'integer', []);
        $table->addColumn('permission_entity_id', 'integer', []);
        $table->setPrimaryKey(['permission_id', 'permission_entity_id']);
    }

    /**
     * Create oro_security_permission table
     */
    protected function createOroSecurityPermissionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_security_permission');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('description', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_apply_to_all', 'boolean', []);
        $table->addColumn('group_names', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    /**
     * Create oro_security_permission_entity table
     */
    protected function createOroSecurityPermissionEntityTable(Schema $schema)
    {
        $table = $schema->createTable('oro_security_permission_entity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name']);
    }

    /**
     * Create rememberme_token table
     */
    private function createRememberMeTokenTable(Schema $schema)
    {
        $table = $schema->createTable('rememberme_token');
        $table->addColumn('series', 'string', ['fixed' => true, 'length' => 88]);
        $table->addColumn('value', 'string', ['length' => 88]);
        $table->addColumn('lastUsed', 'datetime', []);
        $table->addColumn('class', 'string', ['length' => 255]);
        $table->addColumn('username', 'string', ['length' => 255]);
        $table->setPrimaryKey(['series']);
    }

    /**
     * Add oro_security_perm_apply_entity foreign keys.
     */
    protected function addOroSecurityPermApplyEntityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_security_perm_apply_entity');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_security_permission_entity'),
            ['permission_entity_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_security_permission'),
            ['permission_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_security_perm_excl_entity foreign keys.
     */
    protected function addOroSecurityPermExclEntityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_security_perm_excl_entity');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_security_permission_entity'),
            ['permission_entity_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_security_permission'),
            ['permission_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
