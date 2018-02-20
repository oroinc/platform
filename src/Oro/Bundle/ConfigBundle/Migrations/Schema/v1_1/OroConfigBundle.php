<?php

namespace Oro\Bundle\ConfigBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroConfigBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // fill createdAt and updatedAt
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_config_value SET created_at = :date, updated_at = :date',
                ['date' => new \DateTime('now', new \DateTimeZone('UTC'))],
                ['date' => Type::DATETIME]
            )
        );

        $table = $schema->getTable('oro_config_value');
        $table->getColumn('created_at')->setOptions(['notnull' => true]);
        $table->getColumn('updated_at')->setOptions(['notnull' => true]);
    }
}
