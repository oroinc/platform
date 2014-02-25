<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schemas\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroWorkflowBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "RENAME TABLE oro_workflow_entity_acl_identity TO oro_workflow_entity_acl_ident;",
        ];
    }
}
