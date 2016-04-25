<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SecurityBundle\Migrations\Schema\LoadBasePermissionsQuery;

class OroSecurityBundle implements Migration, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroSecurityPermApplyEntityTable($schema);
        $this->createOroSecurityPermExclEntityTable($schema);
        $this->createOroSecurityPermissionTable($schema);
        $this->createOroSecurityPermissionEntityTable($schema);

        /** Foreign keys generation **/
        $this->addOroSecurityPermApplyEntityForeignKeys($schema);
        $this->addOroSecurityPermExclEntityForeignKeys($schema);

        $queries->addPostQuery(new LoadBasePermissionsQuery());
        $queries->addPostQuery(
            new UpdateAclEntriesMigrationQuery(
                $this->container->get('oro_security.acl.manager'),
                $this->container->get('security.acl.cache'),
                $this->container->getParameter('security.acl.dbal.entry_table_name'),
                $this->container->getParameter('security.acl.dbal.oid_table_name'),
                $this->container->getParameter('security.acl.dbal.class_table_name')
            )
        );
    }

    /**
     * Create oro_security_perm_apply_entity table
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     * Add oro_security_perm_apply_entity foreign keys.
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
