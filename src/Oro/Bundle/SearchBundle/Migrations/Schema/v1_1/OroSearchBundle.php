<?php

namespace Oro\Bundle\SearchBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSearchBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_search_update');
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->setPrimaryKey(['entity']);
    }
}
