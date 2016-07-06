<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\PostgreSqlSchemaManager;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DropPrimaryKey implements Migration, OrderedMigrationInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /** {@inheritdoc} */
    public function getOrder()
    {
        return 10;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $em    = $this->container->get('doctrine.orm.entity_manager');
        $table = $schema->getTable('oro_grid_view_user');
        $table->removeForeignKey('FK_10ECBCA8BF53711B');
        if ($table->hasForeignKey('FK_10ECBCA8A76ED395')) {
            $table->removeForeignKey('FK_10ECBCA8A76ED395');
        }
        if ($table->hasForeignKey('fk_oro_grid_view_user_user_id')) {
            $table->removeForeignKey('fk_oro_grid_view_user_user_id');
        }
        $schemaManager = $em->getConnection()->getSchemaManager();
        if ($schemaManager instanceof PostgreSqlSchemaManager) {
            $constraint = new Index('oro_grid_view_user_pkey', ['grid_view_id', 'user_id']);
            $schemaManager->dropConstraint($constraint, $table);
        } else {
            $table->dropPrimaryKey();
        }
    }
}
