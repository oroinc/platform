<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtension;
use Oro\Bundle\EntityBundle\Migrations\Extension\ChangeTypeExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropPrimaryKey implements Migration, OrderedMigrationInterface, ChangeTypeExtensionAwareInterface
{
    /**
     * @var ChangeTypeExtension
     */
    protected $changeTypeExtension;

    /**
     * {@inheritdoc}
     */
    public function setChangeTypeExtension(ChangeTypeExtension $changeTypeExtension)
    {
        $this->changeTypeExtension = $changeTypeExtension;
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
        $table = $schema->getTable('oro_grid_view_user');
        $table->removeForeignKey('FK_10ECBCA8BF53711B');
        if ($table->hasForeignKey('FK_10ECBCA8A76ED395')) {
            $table->removeForeignKey('FK_10ECBCA8A76ED395');
        }
        if ($table->hasForeignKey('fk_oro_grid_view_user_user_id')) {
            $table->removeForeignKey('fk_oro_grid_view_user_user_id');
        }
        $table->addColumn('id', 'integer');
        $this->changeTypeExtension->changePrimaryKeyType($schema, $queries, 'oro_grid_view_user', 'id', Type::INTEGER);
        $table->dropPrimaryKey();
    }
}
