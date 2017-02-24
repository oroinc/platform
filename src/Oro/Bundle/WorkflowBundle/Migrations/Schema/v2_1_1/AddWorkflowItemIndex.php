<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddWorkflowItemIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_workflow_item');
        $table->addIndex(['entity_class', 'entity_id'], 'oro_workflow_item_entity_idx', []);
    }
}
