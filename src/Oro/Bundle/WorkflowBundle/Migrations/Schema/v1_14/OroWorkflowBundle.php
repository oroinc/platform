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
        $this->updateReportsDefinitions($queries);
        $queries->addQuery(new UpdateEntityConfigsQuery());
        $queries->addQuery(new RemoveExtendedFieldsQuery());
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
