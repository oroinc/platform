<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWorkflowBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_process_trigger');
        $table->changeColumn('event', ['length' => 255, 'notnull' => false]);
        $table->addColumn('cron', 'string', ['length' => 100, 'notnull' => false]);
        $table->dropIndex('process_trigger_unique_idx');
        $table->addUniqueIndex(['event', 'field', 'definition_name', 'cron'], 'process_trigger_unique_idx');
    }
}
