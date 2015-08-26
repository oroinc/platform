<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroEmailBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable('oro_process_trigger')) {
            return;
        }

        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_process_trigger WHERE definition_name = :name',
            ['name' => 'convert_mailbox_email_to_case']
        ));
    }
}
