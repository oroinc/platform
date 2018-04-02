<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

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
                'Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition',
                'entity',
                'plural_label',
                'oro.workflow.workflowdefinition.entity_plural_label'
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
        $queries->addQuery(
            new UpdateEntityConfigEntityValueQuery(
                'Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition',
                'entity',
                'plural_label',
                'oro.workflow.processdefinition.entity_label'
            )
        );
    }
}
