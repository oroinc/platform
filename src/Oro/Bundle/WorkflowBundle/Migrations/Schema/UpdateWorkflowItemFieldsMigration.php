<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\StringType;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateWorkflowItemFieldsMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_workflow_item');
        
        if ($table->hasColumn('entity_class')) {
            return;
        }
        
        $table->addColumn('entity_class', 'string', ['length' => 255, 'notnull' => false]);
        $table->changeColumn(
            'entity_id',
            ['type' => StringType::getType('string'), 'length' => 255, 'notnull' => false]
        );

        $queries->addPostQuery(
            'UPDATE oro_workflow_item AS wi SET entity_class = ' .
            '(SELECT related_entity FROM oro_workflow_definition AS wd WHERE wd.name = wi.workflow_name LIMIT 1)'
        );
    }
}
