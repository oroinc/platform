<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MutuallyExclusiveWorkflowsMigration implements Migration
{

    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_workflow_definition');
        $table->addColumn('active', 'boolean');
        $table->addColumn('priority', 'integer');


        $queries->addPostQuery(
            new MoveActiveWorkflowsToFieldQuery()
        );
    }
}
