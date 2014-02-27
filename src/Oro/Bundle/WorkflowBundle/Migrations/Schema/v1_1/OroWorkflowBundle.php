<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class OroWorkflowBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        return [
            "ALTER TABLE oro_workflow_entity_acl_identity RENAME TO oro_workflow_entity_acl_ident;",
        ];
    }
}
