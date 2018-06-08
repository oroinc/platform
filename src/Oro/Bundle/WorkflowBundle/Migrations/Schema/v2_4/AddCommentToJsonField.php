<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds comment to mode_values field of the oro_workflow_restriction table
 */
class AddCommentToJsonField implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCommentsToJsonArrayFields($schema);
    }

    /**
     * @param Schema $schema
     */
    private function addCommentsToJsonArrayFields(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_restriction');
        $table->getColumn('mode_values')
            ->setComment('(DC2Type:json_array)');
    }
}
