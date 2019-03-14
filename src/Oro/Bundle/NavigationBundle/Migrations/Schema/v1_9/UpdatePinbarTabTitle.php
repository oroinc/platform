<?php

namespace Oro\Bundle\NavigationBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdatePinbarTabTitle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_navigation_item_pinbar');

        if ($table->hasColumn('title') && !$table->getColumn('title')->getNotnull()) {
            $table->getColumn('title')->setNotnull(true);
        }
        if ($table->hasColumn('title_short') && !$table->getColumn('title_short')->getNotnull()) {
            $table->getColumn('title_short')->setNotnull(true);
        }
    }
}
