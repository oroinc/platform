<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;

class UpdateEntityLabel implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition',
                'entity',
                'label',
                'oro.workflow.workflowdefinition.entity_label'
            )
        );
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition',
                'entity',
                'label',
                'oro.workflow.processdefinition.entity_label'
            )
        );
    }
}
