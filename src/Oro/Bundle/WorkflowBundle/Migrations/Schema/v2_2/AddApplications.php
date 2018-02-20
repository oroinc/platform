<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Schema\v2_2;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddApplications implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $preSchema = clone $schema;
        $table = $preSchema->getTable('oro_workflow_definition');
        $table->addColumn('applications', 'simple_array', ['notnull' => false, 'comment' => '(DC2Type:simple_array)']);

        foreach ($this->getSchemaDiff($schema, $preSchema) as $query) {
            $queries->addQuery($query);
        }

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_workflow_definition SET applications = :applications WHERE applications IS NULL',
                ['applications' => [CurrentApplicationProviderInterface::DEFAULT_APPLICATION]],
                ['applications' => Type::SIMPLE_ARRAY]
            )
        );

        $postSchema = clone $preSchema;

        $table = $postSchema->getTable('oro_workflow_definition');
        $table->changeColumn('applications', ['notnull' => true]);

        foreach ($this->getSchemaDiff($preSchema, $postSchema) as $query) {
            $queries->addQuery($query);
        }
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
}
