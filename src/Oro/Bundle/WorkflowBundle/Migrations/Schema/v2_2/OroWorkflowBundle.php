<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_2;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\WorkflowBundle\Migrations\Schema\v1_14\SplitGroupsToIndividualFieldsQuery;

class OroWorkflowBundle implements Migration, DatabasePlatformAwareInterface
{
    const TABLE_NAME = 'oro_workflow_definition';

    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addIndex($schema);

        $table = $schema->getTable(self::TABLE_NAME);
        $table->addColumn('exclusive_active_groups', 'simple_array', [
            'comment' => '(DC2Type:simple_array)',
            'notnull' => false,
        ]);
        $table->addColumn('exclusive_record_groups', 'simple_array', [
            'comment' => '(DC2Type:simple_array)',
            'notnull' => false,
        ]);

        $queries->addQuery(new SplitGroupsToIndividualFieldsQuery());
        $queries->addPostQuery(sprintf('ALTER TABLE %s DROP COLUMN %s', self::TABLE_NAME, 'groups'));
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     *
     * @return array
     */
    protected function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();

        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }

    /**
     * @param Schema $schema
     */
    protected function addIndex(Schema $schema)
    {
        $table = $schema->getTable('oro_workflow_item');
        $table->addIndex(['entity_class', 'entity_id'], 'oro_workflow_item_entity_idx', []);
    }
}
