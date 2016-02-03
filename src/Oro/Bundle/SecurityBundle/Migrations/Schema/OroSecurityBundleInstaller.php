<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSecurityBundleInstaller implements Installation, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // create symfony acl tables
        $this->container->get('security.acl.dbal.schema')->addToSchema($schema);

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
        $table = $schema->createTable('oro_security_permission');
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
