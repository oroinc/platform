<?php

namespace Oro\Bundle\CurrencyBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CurrencyConfigOrganizationMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // Move organization default currency config
        self::migrateOrganizationCurrencyConfig($queries);
    }

    /**
     * @param QueryBag $queries
     */
    public static function migrateOrganizationCurrencyConfig(QueryBag $queries)
    {
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_config_value SET name = :currency_name, section = :section 
                                where  name = :search_name and section = :search_section',
                [
                    'currency_name'     => 'default_currency',
                    'section'           => 'oro_currency',
                    'search_name'       => 'currency',
                    'search_section'    => 'oro_locale'
                ],
                [
                    'currency_name'     => Type::STRING,
                    'section'           => Type::STRING,
                    'search_name'       => Type::STRING,
                    'search_section'    => Type::STRING
                ]
            )
        );
    }
}
