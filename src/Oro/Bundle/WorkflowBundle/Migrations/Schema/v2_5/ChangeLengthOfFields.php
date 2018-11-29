<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Changes the length of "field" fields for "oro_process_trigger", "oro_workflow_trans_trigger"
 * and "oro_workflow_restriction" tables,
 * and the length of "mode" field for "oro_workflow_restriction" table.
 */
class ChangeLengthOfFields implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPreQuery(new CheckDataLengthOfFields());

        $processTriggerTable = $schema->getTable('oro_process_trigger');
        $processTriggerTable->getColumn('field')
            ->setLength(150);

        $transitionTriggerTable = $schema->getTable('oro_workflow_trans_trigger');
        $transitionTriggerTable->getColumn('field')
            ->setLength(150);

        $restrictionTable = $schema->getTable('oro_workflow_restriction');
        $restrictionTable->getColumn('field')
            ->setLength(150);
        $restrictionTable->getColumn('mode')
            ->setLength(8);
    }
}
