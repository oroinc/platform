<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\ChangeColumnTypeToJsonQuery;
use Oro\Bundle\MigrationBundle\Migration\MigrateColumnToJsonQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Migrate workflow bundle columns of type ARRAY to JSON type.
 */
class MigrateArrayFieldsToJson implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new MigrateColumnToJsonQuery(
            'oro_process_definition',
            'pre_conditions_configuration',
            $schema
        ));
        $queries->addQuery(new MigrateColumnToJsonQuery(
            'oro_process_definition',
            'actions_configuration',
            $schema
        ));

        $queries->addQuery(new MigrateColumnToJsonQuery(
            'oro_workflow_definition',
            'configuration',
            $schema
        ));

        $queries->addQuery(new ChangeColumnTypeToJsonQuery(
            'oro_workflow_restriction',
            'mode_values',
            $schema
        ));
    }
}
