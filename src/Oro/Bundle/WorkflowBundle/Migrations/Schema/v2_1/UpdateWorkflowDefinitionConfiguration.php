<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateWorkflowDefinitionConfiguration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateWorkflowDefinitionConfigurationQuery());
    }
}
