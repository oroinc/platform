<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddApplications implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_workflow_definition');
        $table->addColumn(
            'applications',
            'simple_array',
            [
                'default' => CurrentApplicationProviderInterface::DEFAULT_APPLICATION,
                'comment' => '(DC2Type:simple_array)',
            ]
        );
        $queries->addPostQuery("ALTER TABLE oro_workflow_definition ALTER COLUMN applications DROP DEFAULT;");
    }
}
