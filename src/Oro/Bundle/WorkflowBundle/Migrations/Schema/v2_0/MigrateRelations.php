<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\InstallerBundle\Migration\UpdateTableFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateRelations implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_workflow_entity_acl',
            'entity_class',
            'OroCRM',
            'Oro'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_workflow_entity_acl_ident',
            'entity_class',
            'OroCRM',
            'Oro'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_workflow_restriction',
            'entity_class',
            'OroCRM',
            'Oro'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_workflow_definition',
            'related_entity',
            'OroCRM',
            'Oro',
            'name'
        ));
    }
}
