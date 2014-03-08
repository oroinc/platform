<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_0\OroWorkflowBundle;

class OroWorkflowBundleInstaller extends Installation
{
    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        OroWorkflowBundle::oroWorkflowDefinitionTable($schema);
        OroWorkflowBundle::oroWorkflowItemTable($schema);
        OroWorkflowBundle::oroWorkflowStepTable($schema);
        OroWorkflowBundle::oroWorkflowTransitionLogTable($schema);
        OroWorkflowBundle::oroWorkflowEntityAclTable($schema);
        OroWorkflowBundle::oroWorkflowEntityAclIdentityTable($schema, 'oro_workflow_entity_acl_ident');

        OroWorkflowBundle::oroWorkflowDefinitionForeignKeys($schema);
        OroWorkflowBundle::oroWorkflowItemForeignKeys($schema);
        OroWorkflowBundle::oroWorkflowStepForeignKeys($schema);
        OroWorkflowBundle::oroWorkflowTransitionLogForeignKeys($schema);
        OroWorkflowBundle::oroWorkflowEntityAclForeignKeys($schema);
        OroWorkflowBundle::oroWorkflowEntityAclIdentityForeignKeys($schema, 'oro_workflow_entity_acl_ident');
    }
}
