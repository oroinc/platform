<?php

namespace Oro\Bundle\TestFrameworkBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroTestFrameworkBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addStringFieldToTestActivityTarget($schema);
    }

    /**
     * Create test_nested_objects table
     *
     * @param Schema $schema
     */
    public function addStringFieldToTestActivityTarget(Schema $schema)
    {
        $table = $schema->getTable('test_activity_target');
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
    }
}
