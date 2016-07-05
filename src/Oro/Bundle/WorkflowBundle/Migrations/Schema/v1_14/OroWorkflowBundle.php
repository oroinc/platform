<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider;

class OroWorkflowBundle implements Migration
{
    use ContainerAwareTrait;

    const OLD_ITEMS_RELATION = 'workflowItem';
    const OLD_STEPS_RELATION = 'workflowStep';
    const NEW_ITEMS_RELATION = WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME;
    const NEW_STEPS_RELATION = WorkflowVirtualRelationProvider::STEPS_RELATION_NAME;
    
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateColumns($schema, $queries);
        $this->updateReportsDefinitions($queries);
        $queries->addQuery(new UpdateEntityConfigsQuery());
        $queries->addQuery(new RemoveExtendedFieldsQuery());
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function updateColumns(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_workflow_item');
        if (!$table->hasColumn('entity_class')) {
            $table->addColumn('entity_class', 'string', ['length' => 255, 'notnull' => false]);
        }

        $table->changeColumn('entity_id', ['string', 'length' => 255, 'notnull' => false]);

        $queries->addPostQuery(
            'UPDATE oro_workflow_item AS wi SET entity_class = ' .
            '(SELECT related_entity FROM oro_workflow_definition AS wd WHERE wd.name = wi.workflow_name LIMIT 1)'
        );
    }

    /**
     * @param QueryBag $queries
     */
    protected function updateReportsDefinitions(QueryBag $queries)
    {
        $queries->addPostQuery(
            sprintf(
                'UPDATE oro_report SET definition = REPLACE(definition, \'%s\', \'%s\')',
                self::OLD_ITEMS_RELATION,
                self::NEW_ITEMS_RELATION
            )
        );
        $queries->addPostQuery(
            sprintf(
                'UPDATE oro_report SET definition = REPLACE(definition, \'%s\', \'%s\')',
                self::OLD_STEPS_RELATION,
                self::NEW_STEPS_RELATION
            )
        );
    }
}
