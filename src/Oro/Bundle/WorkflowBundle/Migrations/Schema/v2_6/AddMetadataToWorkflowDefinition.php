<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

/**
 * Add metadata column to oro_workflow_definition
 */
class AddMetadataToWorkflowDefinition implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_workflow_definition');
        $table->addColumn(
            'metadata',
            'json',
            [
                'comment' => '(DC2Type:json)',
                'notnull' => false,
                'customSchemaOptions' => ['jsonb' => true]
            ]
        );

        $queries->addQuery(new SqlMigrationQuery("UPDATE oro_workflow_definition SET metadata = '[]'::jsonb"));
        $queries->addQuery(
            new SqlMigrationQuery('ALTER TABLE oro_workflow_definition ALTER COLUMN metadata SET NOT NULL')
        );
    }
}
