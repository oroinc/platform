<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_31;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_email_auto_response_rule')
            ->addColumn('definition', 'text', ['notnull' => false]);

        $queries->addPostQuery(new MigrateAutoresponseRuleConditionsQuery());
        $queries->addPostQuery('DROP TABLE oro_email_response_rule_cond');
    }
}
