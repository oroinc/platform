<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddProcessExcludeDefinitions implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_process_definition');
        $table->addColumn(
            'exclude_definitions',
            'simple_array',
            ['notnull' => false, 'comment' => '(DC2Type:simple_array)']
        );
    }
}
