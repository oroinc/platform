<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Remove unused title field
 */
class RemoveTitleField implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_search_item');
        if ($table->hasColumn('title')) {
            $table->dropColumn('title');
        }
    }
}
