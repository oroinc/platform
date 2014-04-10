<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddModuleAndEntityFields implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_entity_config');
        $table->addColumn('module_name', 'string', array('length' => 255));
        $table->addColumn('entity_name', 'string', array('length' => 255));
    }
}
