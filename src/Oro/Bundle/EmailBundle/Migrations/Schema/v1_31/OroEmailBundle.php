<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_31;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        static::oroAutoResponseRuleTable($schema);
        $queries->addPostQuery(new MigrateAutoresponseRuleConditionsQuery());
        $queries->addPostQuery('DROP TABLE oro_email_response_rule_cond');
    }

    /**
     * @param Schema $schema
     */
    public static function oroAutoResponseRuleTable(Schema $schema)
    {
        $table = $schema->getTable('oro_email_auto_response_rule');
        $table->addColumn('definition', 'text', ['notnull' => false]);
    }
}
