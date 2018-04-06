<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveFieldConfigIndexes implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config_index_value WHERE field_id IS NOT NULL AND scope IN (:scopes)',
                ['scopes' => ['dataaudit', 'extend']],
                ['scopes' => Connection::PARAM_STR_ARRAY]
            )
        );
    }
}
