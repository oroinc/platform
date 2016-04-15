<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroUserBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $configIndexValueSql = <<<'SQL'
DELETE FROM oro_entity_config_index_value
WHERE field_id  = (
    SELECT id FROM oro_entity_config_field
    WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
    AND field_name = :field_name
)
SQL;

        $configFieldSql = <<<'SQL'
DELETE FROM oro_entity_config_field
WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
AND field_name = :field_name
SQL;

        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            $configIndexValueSql,
            ['class' => 'Oro\Bundle\UserBundle\Entity\User', 'field_name' => 'image']
        ));
        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            $configFieldSql,
            ['class' => 'Oro\Bundle\UserBundle\Entity\User', 'field_name' => 'image']
        ));
    }
}
