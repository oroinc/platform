<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWorkflowBundle implements Migration, RenameExtensionAwareInterface
{
    use RenameExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $schema->getTable('oro_workflow_step'),
            'final',
            'is_final'
        );
    }
}
