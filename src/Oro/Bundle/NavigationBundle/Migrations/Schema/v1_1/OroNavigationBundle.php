<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroNavigationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_navigation_history');
        $table->getColumn('title')
            ->setType(Type::getType(Type::TEXT))
            ->setLength(null);

        $table = $schema->getTable('oro_navigation_item');
        $table->getColumn('title')
            ->setType(Type::getType(Type::TEXT))
            ->setLength(null);

        $table = $schema->getTable('oro_navigation_title');
        $table->getColumn('title')
            ->setType(Type::getType(Type::TEXT))
            ->setLength(null);
        $table->getColumn('short_title')
            ->setType(Type::getType(Type::TEXT))
            ->setLength(null);
    }
}
