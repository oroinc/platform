<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPreConditions implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_process_definition');
        $table->addColumn(
            'pre_conditions_configuration',
            'array',
            ['notnull' => false, 'comment' => '(DC2Type:array)']
        );
    }
}
